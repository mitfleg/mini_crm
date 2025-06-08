<?php

namespace App\HTTP;

use App\DomainInfra;
use App\Domains\User\User;
use App\DomainInfra\Exceptions\BaseException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class Request {

    public array $query;
    public array $request;
    public array $files;
    public array $server;
    public array $headers;
    public string $token;
    public array $uriParams;
    public array $cookie;
    public string $method;
    public string $path;
    private ?User $user = null;

    public function __construct() {
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->cookie = $_COOKIE;
        $this->headers = $this->getAllHeaders();
        $this->loadServerInfo();
        $this->handleRequest();
        $this->loadUser();
    }

    private function loadServerInfo(): void {
        $env = ['DB_'];

        foreach($_SERVER as $key => $value) {
            $include = true;

            foreach($env as $prefix) {
                if( strpos($key, $prefix) !== false ) {
                    $include = false;
                    break;
                }
            }

            if( $include ) {
                $this->server[$key] = $value;
            }
        }

        if( !isset($this->server['REMOTE_ADDR']) ) {
            $this->server['REMOTE_ADDR'] = '127.0.0.1';
        }
    }

    private function handleRequest() {
        $this->method = $this->server['REQUEST_METHOD'];
        $this->path = $this->server['REQUEST_URI'];

        if( ($this->server['REQUEST_METHOD'] === 'POST' || $this->server['REQUEST_METHOD'] === 'PUT') && isset($this->server['CONTENT_TYPE']) ) {
            if( strpos($this->server['CONTENT_TYPE'], 'application/json') !== false ) {
                $jsonData = json_decode(file_get_contents('php://input'), true);
                $this->request = is_array($jsonData) ? $jsonData : [];
            }
            elseif( $this->server['REQUEST_METHOD'] === 'POST' ) {
                $this->request = $_POST;
            }
        }
    }

    private function loadUser(): void {
        if( isset($this->headers['Authorization']) || isset($this->headers['authorization']) ) {
            $this->token = str_replace('Bearer ', '', $this->headers['Authorization'] ?? $this->headers['authorization']);
        }
        else {
            $this->token = '';
        }

        try {
            $decoded = JWT::decode($this->token, new Key($_ENV['JWT_KEY'], 'HS256'));
        } catch (SignatureInvalidException $e) {
            throw new BaseException('Неверный токен', 401);
        } catch (\Exception $e) {
            throw new BaseException('Ошибка при обработке токена', 500);
        }

        $userData = $decoded?->user_data;

        if( !$userData ) {
            throw new BaseException('Неверный токен', 401);
        }

        $user = User::findById($userData->id);

        if( $user ) {
            if( $user->getStatus() === User::STATUS_ACTIVE ) {
                $this->user = $user;
            }
            else {
                throw new BaseException('Пользователь заблокирован', 403);
            }
        }
    }

    public function getUser(): User {
        if( is_null($this->user) ) {
            throw new BaseException('Пользователь не найден', 401);
        }

        return $this->user;
    }

    public function isAuth(): bool {
        if( is_object($this->user) ) return true;
        return false;
    }

    private function getAllHeaders(): array {
        if( function_exists('getallheaders') ) {
            return getallheaders();
        }
        else {
            $headers = array();

            foreach($_SERVER as $name => $value) {
                if( substr($name, 0, 5) == 'HTTP_' ) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }

            return $headers;
        }
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function setUriParams(array $params) {
        $this->uriParams = $params;
    }

    public function getUriParams($key, $default = null) {
        return $this->uriParams[$key] ?? $default;
    }

    public function getRequestedPage(): string {
        $requestUri = $this->server['REQUEST_URI'] ?? '/';
        $segments = explode('/', trim($requestUri, '/'));
        return end($segments) ?: 'index';
    }

    public function getVersionRequested(): ?string {
        return isset($this->headers['Version']) ? $this->headers['Version'] : null;
    }

    public function getIpAddress(): string {
        return $this->server['REMOTE_ADDR'];
    }

    public function getUserAgent(): ?string {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    public function buildResponse(string $contract, array $data = []): DomainInfra\ContractResponse {
        return new $contract($data);
    }

    public function loadFromContract(string $contract): DomainInfra\ContractRequest {
        return new $contract($this);
    }
}
