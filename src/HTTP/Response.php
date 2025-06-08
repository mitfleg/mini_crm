<?php

namespace App\HTTP;

use App\DomainInfra\ContractResponse;

class Response {

    private array $headers;
    private string $body;

    public function __construct() {
        $this->headers = [];
        $this->body = '';
    }

    public function object(ContractResponse $data, int $status = 200): void {
        $this->json($data->toArray(), $status);
    }

    public function json(array $data, int $status = 200): void {
        $this->setHeader('Content-Type', 'application/json');
        $data = $status === 200 ? ['result' => true, 'data' => $data] : ['result' => false, 'data' => $data];
        $this->setBody(json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->setStatus($status);
        $this->setHeader('Access-Control-Allow-Origin', '*');
        $this->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        $this->send();
    }

    private function setHeader(string $name, string $value): void {
        $this->headers[$name] = $value;
    }

    private function setBody(string $body): void {
        $this->body = $body;
    }

    private function setStatus(int $status): void {
        http_response_code($status);
    }

    private function send(): void {
        foreach($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
        exit;
    }
}