<?php

require_once __DIR__ . '/../../app/core/Database.php';

class AddRoleAndLastLoginToUsers
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                ADD COLUMN role ENUM('user','super_admin') NOT NULL DEFAULT 'user' AFTER password_hash,
                ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                DROP COLUMN last_login_at,
                DROP COLUMN role;
        ";

        $pdo->exec($sql);
    }
}
