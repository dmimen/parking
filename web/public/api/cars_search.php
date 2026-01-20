<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$user = require_login($config);

$q = normalize_plate($_GET['q'] ?? '');
$pdo = db($config);
$expr = normalize_plate_sql('car_number');
$stmt = $pdo->prepare("SELECT car_number, car_model, comment, date_added FROM cars WHERE {$expr} LIKE :q ORDER BY date_added DESC LIMIT 20");
$stmt->execute(['q' => "%{$q}%"]);
$results = $stmt->fetchAll();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
