<?php

require_once __DIR__ . '/../../app/core/Database.php';

class AddIsActiveToUsers
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                DROP COLUMN is_active;
        ";

        $pdo->exec($sql);
    }
}
