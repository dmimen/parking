<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login($config);

switch ($user['role']) {
    case 'admin':
        header('Location: /users.php');
        exit;
    case 'manager':
        header('Location: /cars.php');
        exit;
    case 'guard':
    default:
        header('Location: /cars.php');
        exit;
}
