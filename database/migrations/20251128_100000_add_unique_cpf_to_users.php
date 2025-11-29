<?php

require_once __DIR__ . '/../../app/core/Database.php';

class AddUniqueCpfToUsers
{
    public static function up()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                ADD UNIQUE KEY idx_users_cpf_unique (cpf);
        ";

        $pdo->exec($sql);
    }

    public static function down()
    {
        $pdo = Database::getConnection();

        $sql = "
            ALTER TABLE users
                DROP INDEX idx_users_cpf_unique;
        ";

        $pdo->exec($sql);
    }
}
