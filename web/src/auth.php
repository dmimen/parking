<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validators.php';

function start_session(array $config): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name($config['app']['session_cookie']);
        session_start();
    }
}

function current_user(array $config): ?array
{
    start_session($config);
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $pdo = db($config);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(array $config): array
{
    $user = current_user($config);
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function ensure_admin(array $config): void
{
    $phone = normalize_phone($config['admin']['phone']);
    if ($phone === '') {
        return;
    }
    $pdo = db($config);
    $stmt = $pdo->prepare('SELECT id, role, status FROM users WHERE phone = :phone');
    $stmt->execute(['phone' => $phone]);
    $user = $stmt->fetch();
    if ($user) {
        if ($user['role'] !== 'admin' || $user['status'] !== 'active') {
            $update = $pdo->prepare("UPDATE users SET role = 'admin', status = 'active' WHERE id = :id");
            $update->execute(['id' => $user['id']]);
        }
        return;
    }

    $insert = $pdo->prepare("INSERT INTO users (name, phone, role, status) VALUES (:name, :phone, 'admin', 'active')");
    $insert->execute([
        'name' => $config['admin']['name'] ?: 'Administrator',
        'phone' => $phone,
    ]);
}
