<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_login($config);
require_role(['admin', 'manager'], $user['role']);
csrf_validate();

$pdo = db($config);
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $phone = normalize_phone($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'guard';
    if ($user['role'] === 'manager' && $role === 'admin') {
        header('Location: /users.php');
        exit;
    }
    if ($name && $phone) {
        $stmt = $pdo->prepare('INSERT INTO users (name, phone, role, status) VALUES (:name, :phone, :role, "active")');
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'role' => $role,
        ]);
    }
} elseif ($action === 'toggle_status') {
    $targetId = (int) ($_POST['user_id'] ?? 0);
    if ($targetId) {
        $stmt = $pdo->prepare('SELECT id, role, status FROM users WHERE id = :id');
        $stmt->execute(['id' => $targetId]);
        $target = $stmt->fetch();
        if ($target) {
            if ($user['role'] === 'manager' && $target['role'] === 'admin') {
                header('Location: /users.php');
                exit;
            }
            $newStatus = $target['status'] === 'active' ? 'blocked' : 'active';
            $update = $pdo->prepare('UPDATE users SET status = :status WHERE id = :id');
            $update->execute([
                'status' => $newStatus,
                'id' => $targetId,
            ]);
        }
    }
} elseif ($action === 'delete') {
    $targetId = (int) ($_POST['user_id'] ?? 0);
    if ($targetId) {
        $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = :id');
        $stmt->execute(['id' => $targetId]);
        $target = $stmt->fetch();
        if ($target) {
            if ($user['role'] === 'manager' && $target['role'] === 'admin') {
                header('Location: /users.php');
                exit;
            }
            $delete = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $delete->execute(['id' => $targetId]);
        }
    }
}

header('Location: /users.php');
exit;
