<?php

namespace App\Domains\User;

class FacadeAuth extends Builder {

    public function register(string $email, string $password, string $name): array {
        $auth = $this->buildAuthUseCase();
        return $auth->register($email, $password, $name, $this->request->getIpAddress());
    }

    public function login(string $login, string $password): array {
        $auth = $this->buildAuthUseCase();
        return $auth->login($login, $password, $this->request->getIpAddress());
    }

    public function validateToken(string $token): ?User {
        $auth = $this->buildAuthUseCase();
        return $auth->validateToken($token);
    }

    public function refreshToken(string $token): array {
        $auth = $this->buildAuthUseCase();
        return $auth->refreshToken($token);
    }
}