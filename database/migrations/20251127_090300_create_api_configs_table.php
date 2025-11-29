<?php

require_once __DIR__ . '/../../app/core/Database.php';

class CreateApiConfigsTable
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            CREATE TABLE IF NOT EXISTS api_configs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                provider VARCHAR(50) NOT NULL,
                api_key TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();
        $pdo->exec('DROP TABLE IF EXISTS api_configs;');
    }
}
