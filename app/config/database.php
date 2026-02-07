<?php

require_once __DIR__ . '/config.php';

function db_config(): array
{
    if (ENVIRONMENT === 'development') {
        return [
            'host' => DB_DEV_HOST,
            'dbname' => DB_DEV_NAME,
            'user' => DB_DEV_USER,
            'pass' => DB_DEV_PASS,
            'charset' => DB_DEV_CHARSET,
        ];
    }

    return [
        'host' => DB_PROD_HOST,
        'dbname' => DB_PROD_NAME,
        'user' => DB_PROD_USER,
        'pass' => DB_PROD_PASS,
        'charset' => DB_PROD_CHARSET,
    ];
}