<?php

require_once __DIR__ . '/config.php';

function db_config(): array
{
    if (ENVIRONMENT === 'development') {
        return [
            'host' => '127.0.0.1',
            'dbname' => 'easychart_dev',
            'user' => 'root',
            'pass' => '',
            'charset' => 'utf8',
        ];
    }

    return [
        'host' => 'PROD_HOST',
        'dbname' => 'easychart_prod',
        'user' => 'PROD_USER',
        'pass' => 'PROD_PASS',
        'charset' => 'utf8',
    ];
}