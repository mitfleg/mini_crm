<?php

namespace App\Domains\User;

use App\DomainInfra\BaseModel;
use \R;
use App\DomainInfra\Exceptions\ModelException;

class User extends BaseModel {

    const TABLE = 'users';

    const string ROLE_ADMIN = 'admin';
    const string ROLE_MANAGER = 'manager';
    const string ROLE_USER = 'user';

    const array ROLES = [
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_MANAGER => 'Менеджер',
        self::ROLE_USER => 'Пользователь'
    ];

    const string STATUS_ACTIVE = 'active';
    const string STATUS_BANNED = 'banned';

    const array STATUSES = [
        self::STATUS_ACTIVE => 'Активный',
        self::STATUS_BANNED => 'Заблокирован'
    ];

    /**
     * @unique
     * @required
     */
    private string $login;

    /**
     * @required
     * @hidden
     */
    private string $password;

    /**
     * @required
     * @unique
     */
    private string $email;

    /**
     * @required
     */
    private string $role = self::ROLE_USER;

    /**
     * @required
     */
    private string $ipAddress;

    /**
     * @required
     */
    private string $status = self::STATUS_ACTIVE;

    public function setLogin(string $login): self {
        if( empty($login) ) {
            throw new ModelException('Логин не может быть пустым', 400);
        }
        elseif( strlen($login) < 3 ) {
            throw new ModelException('Логин должен быть не менее 3 символов', 400);
        }
        elseif( $this->findOneBy(['login' => $login]) ) {
            throw new ModelException('Логин уже занят', 400);
        }

        $this->login = $login;
        return $this;
    }

    public function setEmail(string $email): self {
        if( empty($email) ) {
            throw new ModelException('Email не может быть пустым', 400);
        }
        elseif( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            throw new ModelException('Некорректный email', 400);
        }
        elseif( $this->findOneBy(['email' => $email]) ) {
            throw new ModelException('Email уже занят', 400);
        }

        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self {
        if( empty($password) ) {
            throw new ModelException('Пароль не может быть пустым', 400);
        }
        elseif( strlen($password) < 8 ) {
            throw new ModelException('Пароль должен быть не менее 8 символов', 400);
        }
        elseif( !preg_match('/[A-Z]/', $password) ) {
            throw new ModelException('Пароль должен содержать хотя бы одну заглавную букву', 400);
        }
        elseif( !preg_match('/[a-z]/', $password) ) {
            throw new ModelException('Пароль должен содержать хотя бы одну строчную букву', 400);
        }
        elseif( !preg_match('/[0-9]/', $password) ) {
            throw new ModelException('Пароль должен содержать хотя бы одну цифру', 400);
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function setRole(string $role): self {
        if( !in_array($role, array_keys(self::ROLES)) ) {
            throw new ModelException('Недопустимая роль пользователя: ' . $role, 400);
        }

        $this->role = $role;
        return $this;
    }

    public static function authenticate(string $login, string $password): ?self {
        $user = self::findOneBy(['login' => $login]);

        if( $user && password_verify($password, $user->password) ) {
            return $user;
        }

        return null;
    }

    public function setIpAddress(string $ipAddress): self {
        if( empty($ipAddress) ) {
            throw new ModelException('IP адрес не может быть пустым', 400);
        }
        elseif( !filter_var($ipAddress, FILTER_VALIDATE_IP) ) {
            throw new ModelException('Некорректный IP адрес', 400);
        }

        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function setStatus(string $status): self {
        if( !in_array($status, array_keys(self::STATUSES)) ) {
            throw new ModelException('Недопустимый статус пользователя: ' . $status, 400);
        }

        $this->status = $status;
        return $this;
    }

    public function getLogin(): string {
        return $this->login;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getIpAddress(): string {
        return $this->ipAddress;
    }
}
