<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login($config);
require_role(['admin'], $user['role']);

$pdo = db($config);
$stmt = $pdo->query('SELECT * FROM remote_cars ORDER BY date_deleted DESC');
$rows = $stmt->fetchAll();

ob_start();
?>
<h1>История удалений</h1>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Номер</th>
            <th>Модель</th>
            <th>Комментарий</th>
            <th>Добавлен</th>
            <th>Удален</th>
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
$title = 'История удалений';
require __DIR__ . '/../templates/layout.php';
