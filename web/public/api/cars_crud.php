<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_login($config);
require_role(['admin', 'manager'], $user['role']);
csrf_validate();

$action = $_POST['action'] ?? '';
$pdo = db($config);

if ($action === 'create') {
    $model = trim($_POST['car_model'] ?? '');
    $number = normalize_car_number($_POST['car_number'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    if ($model && $number) {
        $stmt = $pdo->prepare('INSERT INTO cars (car_model, car_number, comment, who_added, date_added) VALUES (:model, :number, :comment, :who_added, NOW())');
        $stmt->execute([
            'model' => $model,
            'number' => $number,
            'comment' => $comment,
            'who_added' => $user['id'],
        ]);
    }
} elseif ($action === 'delete') {
    $carId = (int) ($_POST['car_id'] ?? 0);
    if ($carId) {
        $pdo->beginTransaction();
        $select = $pdo->prepare('SELECT * FROM cars WHERE id = :id');
        $select->execute(['id' => $carId]);
        $car = $select->fetch();
        if ($car) {
            $insert = $pdo->prepare('INSERT INTO remote_cars (car_model, car_number, comment, who_added, date_added, who_deleted, date_deleted) VALUES (:car_model, :car_number, :comment, :who_added, :date_added, :who_deleted, NOW())');
            $insert->execute([
                'car_model' => $car['car_model'],
                'car_number' => $car['car_number'],
                'comment' => $car['comment'],
                'who_added' => $car['who_added'],
                'date_added' => $car['date_added'],
                'who_deleted' => $user['id'],
            ]);
            $delete = $pdo->prepare('DELETE FROM cars WHERE id = :id');
            $delete->execute(['id' => $carId]);
        }
        $pdo->commit();
    }
}

header('Location: /cars.php');
exit;
