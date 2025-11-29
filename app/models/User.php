<?php

require_once __DIR__ . '/../core/Database.php';

class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $fullName, string $email, string $password, ?string $cpf = null): bool
    {
        try {
            $pdo = Database::getConnection();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, cpf) VALUES (:full_name, :email, :password_hash, :cpf)'
            );

            return $stmt->execute([
                'full_name'     => $fullName,
                'email'         => $email,
                'password_hash' => $hash,
                'cpf'           => $cpf,
            ]);
        } catch (PDOException $e) {
            // Em ambiente de desenvolvimento, mostre o erro para facilitar debug
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                echo '<pre>Database error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
            return false;
        }
    }

    public static function findByCpf(string $cpf): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE cpf = :cpf LIMIT 1');
        $stmt->execute(['cpf' => $cpf]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}