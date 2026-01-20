<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_login($config);

$q = normalize_car_number($_GET['q'] ?? '');
$pdo = db($config);
$stmt = $pdo->prepare('SELECT car_number, car_model, comment, date_added FROM cars WHERE car_number LIKE :q ORDER BY date_added DESC LIMIT 20');
$stmt->execute(['q' => "%{$q}%"]);
$results = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode(['results' => $results]);
