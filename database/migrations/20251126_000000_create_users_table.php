<?php

require_once __DIR__ . '/../../app/core/Database.php';

class CreateUsersTable
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();
        $pdo->exec('DROP TABLE IF EXISTS users;');
    }
}