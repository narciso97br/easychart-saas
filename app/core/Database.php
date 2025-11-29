<?php

require_once __DIR__ . '/../config/database.php';

class Database
{
    private static ?PDO $conn = null;

    public static function getConnection(): PDO
    {
        if (self::$conn === null) {
            $db_config = db_config();
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
            self::$conn = new PDO($dsn, $db_config['user'], $db_config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

        }

        return self::$conn;
    }
}