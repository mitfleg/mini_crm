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
    private string $role;

    public function setLogin(string $login): self {
        if( empty($login) ) {
            throw new ModelException('Логин не может быть пустым');
        }
        elseif( strlen($login) < 3 ) {
            throw new ModelException('Логин должен быть не менее 3 символов');
        }
        elseif( $this->findOneBy(['login' => $login]) ) {
            throw new ModelException('Логин уже занят');
        }

        $this->login = $login;
        return $this;
    }

    public function setEmail(string $email): self {
        if( empty($email) ) {
            throw new ModelException('Email не может быть пустым');
        }
        elseif( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            throw new ModelException('Некорректный email');
        }
        elseif( $this->findOneBy(['email' => $email]) ) {
            throw new ModelException('Email уже занят');
        }

        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self {
        if( empty($password) ) {
            throw new ModelException('Пароль не может быть пустым');
        }
        elseif( strlen($password) < 8 ) {
            throw new ModelException('Пароль должен быть не менее 8 символов');
        }
        elseif( !preg_match('/[A-Z]/', $password) ) {
            throw new ModelException('Пароль должен содержать хотя бы одну заглавную букву');
        }
        elseif( !preg_match('/[a-z]/', $password) ) {
            throw new ModelException('Пароль должен содержать хотя бы одну строчную букву');
        }
        elseif( !preg_match('/[0-9]/', $password) ) {
            throw new ModelException('Пароль должен содержать хотя бы одну цифру');
        }

        $this->password = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function setRole(string $role): self {
        if( !in_array($role, array_keys(self::ROLES)) ) {
            throw new ModelException('Недопустимая роль пользователя: ' . $role);
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
}
