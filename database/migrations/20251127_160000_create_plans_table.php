<?php

require_once __DIR__ . '/../../app/core/Database.php';

class CreatePlansTable
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            CREATE TABLE IF NOT EXISTS plans (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(50) NOT NULL UNIQUE,
                price_cents INT UNSIGNED NOT NULL DEFAULT 0,
                currency CHAR(3) NOT NULL DEFAULT 'BRL',
                monthly_spreadsheet_limit INT NULL,
                monthly_chart_limit INT NULL,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();
        $pdo->exec('DROP TABLE IF EXISTS plans;');
    }
}
