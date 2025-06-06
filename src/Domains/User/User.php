<?php

namespace App\Domains\User;

use \R;
use App\DomainInfra\Exceptions\ModelException;

class User {

    const TABLE = 'users';

    const string ROLE_ADMIN = 'admin';
    const string ROLE_MANAGER = 'manager';
    const string ROLE_USER = 'user';

    const array ROLES = [
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_MANAGER => 'Менеджер',
        self::ROLE_USER => 'Пользователь'
    ];

    private int $id;
    private string $login;
    private string $password;
    private string $email;
    private string $name;
    private string $role;
    private string $created_at;
    private string $updated_at;

    public static function create(string $login, string $password, string $email, string $name, string $role = self::ROLE_USER): int {
        self::checkUnique($login, $email);
        self::validateRole($role);

        $user = R::dispense(self::TABLE);
        $user->login = $login;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->email = $email;
        $user->name = $name;
        $user->role = $role;
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');

        // Создаем индексы, если они еще не созданы
        self::setupIndexes();

        return R::store($user);
    }

    public static function getById(int $id): self {
        return self::getBy('id', $id);
    }

    public static function getByLogin(string $login): self {
        return self::getBy('login', $login);
    }

    private static function getBy(string $field, string $value): self {
        /** @var User $user */
        $user = R::findOne(self::TABLE, $field . ' = ?', [$value]);

        if( !$user->id ) {
            throw new ModelException('Пользователь не найден', 404);
        }

        return $user;
    }

    public static function getByEmail(string $email): self {
        return self::getBy('email', $email);
    }

    /**
     * @return User[]
     */
    public static function getAll(): array {
        return R::findAll(self::TABLE);
    }

    public static function update(int $id, array $data): int {
        $user = self::getById($id);

        if( isset($data['login']) || isset($data['email']) ) {
            $login = $data['login'] ?? $user->login;
            $email = $data['email'] ?? $user->email;
            self::checkUnique($login, $email, $id);
        }

        if( isset($data['role']) ) {
            self::validateRole($data['role']);
        }

        foreach($data as $key => $value) {
            if( $key === 'password' ) {
                $user->$key = password_hash($value, PASSWORD_DEFAULT);
            }
            else {
                $user->$key = $value;
            }
        }

        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    public static function delete(int $id): bool {
        $user = self::getById($id);
        R::trash($user);
        return true;
    }

    public static function authenticate(string $login, string $password): ?self {
        $user = self::getByLogin($login);

        if( $user && password_verify($password, $user->password) ) {
            return $user;
        }

        return null;
    }

    private static function checkUnique(string $login, string $email, ?int $excludeId = null): void {
        $existingLogin = R::findOne(self::TABLE, 'login = ? AND id != ?', [$login, $excludeId ?? 0]);

        if( $existingLogin ) {
            throw new ModelException('Пользователь с таким логином уже существует', 400);
        }

        $existingEmail = R::findOne(self::TABLE, 'email = ? AND id != ?', [$email, $excludeId ?? 0]);

        if( $existingEmail ) {
            throw new ModelException('Пользователь с таким email уже существует', 400);
        }
    }

    private static function setupIndexes(): void {
        if( !R::testConnection() ) return;

        $tables = R::inspect();

        if( !in_array(self::TABLE, $tables) ) {
            $user = R::dispense(self::TABLE);
            R::store($user);
            R::trash($user);
        }

        $constraints = R::getAll(
            "
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = " . self::TABLE . "
            AND CONSTRAINT_TYPE = 'UNIQUE'
        "
        );

        $hasLoginConstraint = false;
        $hasEmailConstraint = false;

        foreach($constraints as $constraint) {
            if( strpos($constraint['CONSTRAINT_NAME'], 'login') !== false ) {
                $hasLoginConstraint = true;
            }

            if( strpos($constraint['CONSTRAINT_NAME'], 'email') !== false ) {
                $hasEmailConstraint = true;
            }
        }

        // Добавляем уникальные ограничения, если их еще нет
        if( !$hasLoginConstraint ) {
            try {
                R::exec('ALTER TABLE ' . self::TABLE . ' ADD CONSTRAINT unique_login UNIQUE (login)');
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ограничение уже существует
            }
        }

        if( !$hasEmailConstraint ) {
            try {
                R::exec('ALTER TABLE ' . self::TABLE . ' ADD CONSTRAINT unique_email UNIQUE (email)');
            } catch (\Exception $e) {
                // Игнорируем ошибку, если ограничение уже существует
            }
        }
    }

    public function hasRole(string $role): bool {
        return $this->role === $role;
    }

    public function isAdmin(): bool {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isManager(): bool {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public static function validateRole(?string $role = null): void {
        if( !$role || !in_array($role, array_keys(self::ROLES)) ) {
            throw new ModelException('Недопустимая роль пользователя: ' . $role, 400);
        }
    }
}
