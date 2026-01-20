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

ob_start();
?>
<h1>Автомобили</h1>
<div class="form-row">
    <input type="text" class="form-control" data-car-search placeholder="Поиск по номеру">
</div>

<?php if (can_manage_cars($user['role'])): ?>
<form method="post" action="/api/cars_crud.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row">
        <input type="text" name="car_model" class="form-control" placeholder="Модель" required>
        <input type="text" name="car_number" class="form-control" placeholder="Номер" required>
        <input type="text" name="comment" class="form-control" placeholder="Комментарий">
        <button type="submit" class="btn btn-primary">Добавить</button>
    </div>
</form>
<?php endif; ?>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Номер</th>
            <th>Модель</th>
            <th>Комментарий</th>
            <th>Добавлен</th>
            <?php if (can_manage_cars($user['role'])): ?>
                <th>Действия</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody data-car-results>
        <?php foreach ($cars as $car): ?>
            <tr>
                <td><?= htmlspecialchars($car['car_number'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($car['car_model'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($car['comment'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($car['date_added'], ENT_QUOTES, 'UTF-8') ?></td>
                <?php if (can_manage_cars($user['role'])): ?>
                    <td>
                        <form method="post" action="/api/cars_crud.php" style="display:inline-block">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="car_id" value="<?= (int) $car['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="form-row">
    <span class="badge">Страница <?= $page ?> / <?= $pages ?></span>
    <?php if ($page > 1): ?>
        <a href="/cars.php?page=<?= $page - 1 ?>">Назад</a>
    <?php endif; ?>
    <?php if ($page < $pages): ?>
        <a href="/cars.php?page=<?= $page + 1 ?>">Вперед</a>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$title = 'Автомобили';
require __DIR__ . '/../templates/layout.php';
