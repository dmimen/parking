<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login($config);
require_role(['admin'], $user['role']);

$pdo = db($config);
$stmt = $pdo->query('SELECT remote_cars.*, added.name AS added_by_name, added.phone AS added_by_phone, deleted.name AS deleted_by_name, deleted.phone AS deleted_by_phone FROM remote_cars LEFT JOIN users AS added ON remote_cars.who_added = added.id LEFT JOIN users AS deleted ON remote_cars.who_deleted = deleted.id ORDER BY date_deleted DESC');
$rows = $stmt->fetchAll();

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1">История удалений</h1>
        <p class="text-muted mb-0">Полный журнал удаленных автомобилей.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Удаленные автомобили</div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Номер</th>
                    <th>Модель</th>
                    <th>Комментарий</th>
                    <th>Добавлен</th>
                    <th>Добавил</th>
                    <th>Удален</th>
                    <th>Удалил</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($row['car_number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['car_model'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="text-truncate-2" title="<?= htmlspecialchars($row['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($row['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['date_added'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($row['added_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($row['added_by_phone'])): ?>
                                <span class="text-muted">(<?= htmlspecialchars($row['added_by_phone'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['date_deleted'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($row['deleted_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($row['deleted_by_phone'])): ?>
                                <span class="text-muted">(<?= htmlspecialchars($row['deleted_by_phone'], ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'История удалений';
$current = 'remote';
require __DIR__ . '/../templates/layout.php';
