<?php

require_once __DIR__ . '/../../app/core/Database.php';

class CreateSpreadsheetsTable
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            CREATE TABLE IF NOT EXISTS spreadsheets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size_bytes BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();
        $pdo->exec('DROP TABLE IF EXISTS spreadsheets;');
    }
}
