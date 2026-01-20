<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login($config);
require_role(['admin'], $user['role']);

$pdo = db($config);
$stmt = $pdo->query('SELECT * FROM remote_cars ORDER BY date_deleted DESC');
$rows = $stmt->fetchAll();

ob_start();
?>
<h1>Remote Cars</h1>
<table class="table">
    <thead>
        <tr>
            <th>Number</th>
            <th>Model</th>
            <th>Comment</th>
            <th>Date added</th>
            <th>Date deleted</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['car_number'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['car_model'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['date_added'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['date_deleted'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$content = ob_get_clean();
$title = 'Remote Cars';
require __DIR__ . '/../templates/layout.php';
