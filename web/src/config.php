<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'name' => getenv('DB_NAME') ?: 'parking',
        'user' => getenv('DB_USER') ?: 'parking',
        'password' => getenv('DB_PASSWORD') ?: 'parking_pass',
        'port' => getenv('DB_PORT') ?: '3306',
    ],
    'app' => [
        'secret' => getenv('APP_SECRET') ?: 'change_me',
        'session_cookie' => getenv('SESSION_COOKIE_NAME') ?: 'park_sess',
        'base_url' => getenv('WEB_BASE_URL') ?: 'http://localhost:8080',
    ],
    'admin' => [
        'phone' => getenv('ADM_PHONE') ?: '',
        'name' => getenv('ADM_NAME') ?: 'Administrator',
    ],
];
