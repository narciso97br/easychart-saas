<?php

require_once __DIR__ . '/../../app/core/Database.php';

class CreateUserSettingsTable
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            CREATE TABLE IF NOT EXISTS user_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                notification_email_enabled TINYINT(1) NOT NULL DEFAULT 1,
                notification_weekly_summary TINYINT(1) NOT NULL DEFAULT 1,
                notification_product_updates TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                UNIQUE KEY uniq_user_settings_user (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();
        $pdo->exec('DROP TABLE IF EXISTS user_settings;');
    }
}
