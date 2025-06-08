<?php

namespace App\Domains\User\Usecases;

use App\Domains\User\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\DomainInfra\Exceptions\BaseException;

class Auth {

    const int JWT_EXPIRATION = 3600 * 24;

    public function __construct(
        private string $jwtKey,
        private string $jwtIssuer,
    ) {}

    public function register(string $email, string $password, string $name, string $ipAddress): array {
        $model = new User();
        $model->setLogin($name);
        $model->setEmail($email);
        $model->setPassword($password);
        $model->setRole(User::ROLE_USER);
        $model->setStatus(User::STATUS_ACTIVE);
        $model->setIpAddress($ipAddress);
        $model->save();
        return $this->generateTokenForUser($model);
    }

    public function login(string $login, string $password, string $ipAddress): array {
        $user = User::authenticate($login, $password);

        if( !$user ) {
            throw new BaseException('Неверный логин или пароль', 401);
        }

        if( $user->getStatus() !== User::STATUS_ACTIVE ) {
            throw new BaseException('Учетная запись заблокирована', 403);
        }

        $user->setIpAddress($ipAddress);
        $user->save();

        return $this->generateTokenForUser($user);
    }

    private function generateTokenForUser(User $user): array {
        $now = time();
        $payload = [
            'iss' => $this->jwtIssuer,           // Издатель токена
            'aud' => $this->jwtIssuer,           // Аудитория токена
            'iat' => $now,                       // Время создания
            'nbf' => $now,                       // Токен действителен с
            'exp' => $now + self::JWT_EXPIRATION, // Время истечения
            'sub' => $user->getId(),             // Идентификатор пользователя
            'user_data' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'status' => $user->getStatus()
            ]
        ];

        $token = JWT::encode($payload, $this->jwtKey, 'HS256');

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::JWT_EXPIRATION,
            'user' => $payload['user_data']
        ];
    }

    public function validateToken(string $token): ?User {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            $userData = $decoded->user_data;
            $user = User::findById($userData->id);

            if( !$user || $user->getStatus() !== User::STATUS_ACTIVE ) {
                return null;
            }

            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function refreshToken(string $oldToken): array {
        $user = $this->validateToken($oldToken);

        if( !$user ) {
            throw new BaseException('Недействительный токен', 401);
        }

        return $this->generateTokenForUser($user);
    }
}
