<?php

require_once __DIR__ . '/../../app/core/Database.php';

class CreateChartsTable
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            CREATE TABLE IF NOT EXISTS charts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                spreadsheet_id INT UNSIGNED NULL,
                prompt TEXT NOT NULL,
                chart_type VARCHAR(100) NULL,
                data_json LONGTEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE,
                FOREIGN KEY (spreadsheet_id) REFERENCES spreadsheets(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();
        $pdo->exec('DROP TABLE IF EXISTS charts;');
    }
}
