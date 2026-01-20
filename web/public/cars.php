<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login($config);
$pdo = db($config);

$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$totalStmt = $pdo->query('SELECT COUNT(*) as cnt FROM cars');
$total = (int) $totalStmt->fetch()['cnt'];
$pages = max(1, (int) ceil($total / $limit));

$stmt = $pdo->prepare('SELECT * FROM cars ORDER BY date_added DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cars = $stmt->fetchAll();
if ($user['role'] === 'admin') {
    $stmt = $pdo->prepare('SELECT cars.*, users.name AS added_by_name, users.phone AS added_by_phone FROM cars LEFT JOIN users ON cars.who_added = users.id ORDER BY date_added DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cars = $stmt->fetchAll();
}

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1">Автомобили</h1>
        <p class="text-muted mb-0">Поиск по номеру и управление списком.</p>
    </div>
    <?php if (can_manage_cars($user['role'])): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCarModal">
            <i class="bi bi-plus-lg me-1"></i>Добавить авто
        </button>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Поиск</span>
        <span class="search-indicator" data-search-indicator></span>
    </div>
    <div class="card-body">
        <input type="text" class="form-control" data-car-search placeholder="Введите номер автомобиля">
        <div class="mt-3" data-search-results></div>
    </div>
</div>

<div class="card">
    <div class="card-header">Список автомобилей</div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle" data-admin-columns="<?= $user['role'] === 'admin' ? '1' : '0' ?>" data-actions="<?= can_manage_cars($user['role']) ? '1' : '0' ?>">
            <thead>
                <tr>
                    <th>Номер</th>
                    <th>Модель</th>
                    <th>Комментарий</th>
                    <th>Добавлен</th>
                    <?php if ($user['role'] === 'admin'): ?>
                        <th>Добавил</th>
                    <?php endif; ?>
                    <?php if (can_manage_cars($user['role'])): ?>
                        <th class="text-end">Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody data-car-results>
                <?php foreach ($cars as $car): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($car['car_number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($car['car_model'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="text-truncate-2" title="<?= htmlspecialchars($car['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($car['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($car['date_added'], ENT_QUOTES, 'UTF-8') ?></td>
                        <?php if ($user['role'] === 'admin'): ?>
                            <td>
                                <?= htmlspecialchars($car['added_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($car['added_by_phone'])): ?>
                                    <span class="text-muted">(<?= htmlspecialchars($car['added_by_phone'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if (can_manage_cars($user['role'])): ?>
                            <td class="text-end">
                                <form id="delete-car-<?= (int) $car['id'] ?>" method="post" action="/api/cars_crud.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="car_id" value="<?= (int) $car['id'] ?>">
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCarModal" data-confirm="deleteCarModal" data-form="delete-car-<?= (int) $car['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mt-3">
    <span class="badge text-bg-light">Страница <?= $page ?> / <?= $pages ?></span>
    <div class="d-flex gap-2">
        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/cars.php?page=<?= $page - 1 ?>">Назад</a>
        <?php endif; ?>
        <?php if ($page < $pages): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/cars.php?page=<?= $page + 1 ?>">Вперед</a>
        <?php endif; ?>
    </div>
</div>

<?php if (can_manage_cars($user['role'])): ?>
<div class="modal fade" id="addCarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить автомобиль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/api/cars_crud.php">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Модель</label>
                        <input type="text" name="car_model" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Номер</label>
                        <input type="text" name="car_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Комментарий</label>
                        <input type="text" name="comment" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteCarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Удаление автомобиля</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Удалить автомобиль из активного списка?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" data-confirm-submit>Удалить</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (can_manage_cars($user['role'])): ?>
<div class="card mt-4">
    <div class="card-header">Импорт автомобилей (Excel)</div>
    <div class="card-body">
        <form method="post" action="/api/cars_crud.php" enctype="multipart/form-data" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import">
            <div class="col-md-8">
                <input type="file" name="import_file" class="form-control" accept=".xlsx,.csv" required>
                <div class="form-note mt-1">Файл: 3 колонки (Марка, Номер, Комментарий).</div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-upload me-1"></i>Загрузить
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php
$content = ob_get_clean();
$title = 'Автомобили';
$current = 'cars';
require __DIR__ . '/../templates/layout.php';
