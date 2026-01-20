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
} elseif ($action === 'import') {
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash'][] = ['type' => 'danger', 'message' => 'Не удалось загрузить файл.'];
        header('Location: /cars.php');
        exit;
    }
    $tmpPath = $_FILES['import_file']['tmp_name'];
    $filename = $_FILES['import_file']['name'] ?? '';
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $rows = [];

    if ($extension === 'csv') {
        $handle = fopen($tmpPath, 'r');
        if ($handle) {
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                if (count($data) < 2) {
                    continue;
                }
                $rows[] = $data;
            }
            fclose($handle);
        }
    } elseif ($extension === 'xlsx' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmpPath) === true) {
            $sharedStrings = [];
            $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($stringsXml) {
                $xml = simplexml_load_string($stringsXml);
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string) $si->t;
                }
            }
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml) {
                $xml = simplexml_load_string($sheetXml);
                foreach ($xml->sheetData->row as $row) {
                    $values = [];
                    foreach ($row->c as $c) {
                        $value = (string) $c->v;
                        $type = (string) $c['t'];
                        if ($type === 's') {
                            $value = $sharedStrings[(int) $value] ?? '';
                        }
                        $values[] = $value;
                    }
                    if (count($values) >= 2) {
                        $rows[] = $values;
                    }
                }
            }
            $zip->close();
        }
    }

    if (!$rows) {
        $_SESSION['flash'][] = ['type' => 'danger', 'message' => 'Файл не содержит данных.'];
        header('Location: /cars.php');
        exit;
    }

    $imported = 0;
    foreach ($rows as $row) {
        $model = trim($row[0] ?? '');
        $number = normalize_car_number($row[1] ?? '');
        $comment = trim($row[2] ?? '');
        if (!$model || !$number) {
            continue;
        }
        $stmt = $pdo->prepare('INSERT INTO cars (car_model, car_number, comment, who_added, date_added) VALUES (:model, :number, :comment, :who_added, NOW())');
        $stmt->execute([
            'model' => $model,
            'number' => $number,
            'comment' => $comment,
            'who_added' => $user['id'],
        ]);
        $imported++;
    }
    $_SESSION['flash'][] = ['type' => 'success', 'message' => "Импортировано записей: {$imported}."];
}

header('Location: /cars.php');
exit;
