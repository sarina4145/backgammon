<?php
declare(strict_types=1);

return [
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'backgammon',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: 'mysql',
        'charset' => 'utf8mb4',
    ],
];
