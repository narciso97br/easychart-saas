<?php

require_once __DIR__ . '/../../app/core/Database.php';

class AddCpfToUsers
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                ADD COLUMN cpf VARCHAR(20) NULL AFTER email;
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                DROP COLUMN cpf;
        ";

        $pdo->exec($sql);
    }
}
