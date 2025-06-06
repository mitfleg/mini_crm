<?php

namespace App\HTTP;

use App\Domains\User\User;
use App\DomainInfra\Exceptions\BaseException;

class Request {

    public array $query;
    public array $request;
    public array $files;
    public array $server;
    public array $headers;
    public string $token;
    public array $uriParams;
    public array $cookie;
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
    }

    private function handleRequest() {
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
            // $sessionService = new SessionService();

            // if( isset($this->cookie['session_id']) || isset($this->headers['Authorization']) ) {
            //     if( isset($this->headers['Authorization']) ) {
            //         $token = str_replace('Bearer ', '', $this->headers['Authorization']);
            //         $session = $sessionService->findActiveSessionByToken($token);
            //         $this->cookie['session_id'] = $token;
            //     }
            //     else {
            //         $session = $sessionService->findActiveSessionByToken($this->cookie['session_id']);
            //     }

            //     if( !is_null($session) ) {
            //         $serviceUser = new UserService();
            //         $this->user = $serviceUser->findByID($session->user_id);

            //         if( !$this->user->is_active ) {
            //             $serviceUser->logout($this->cookie['session_id']);
            //             $this->user = null;
            //         }
            //     }
            // }
    }

    public function getUser(): User {
        if( is_null($this->user) ) {
            throw new BaseException('User not found', 401);
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

    public function getIpAddress(): ?string {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    public function getUserAgent(): ?string {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }
}
