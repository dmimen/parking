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
<h1>Cars</h1>
<div class="form-row">
    <input type="text" data-car-search placeholder="Search by number">
</div>

<?php if (can_manage_cars($user['role'])): ?>
<form method="post" action="/api/cars_crud.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row">
        <input type="text" name="car_model" placeholder="Model" required>
        <input type="text" name="car_number" placeholder="Number" required>
        <input type="text" name="comment" placeholder="Comment">
        <button type="submit">Add</button>
    </div>
</form>
<?php endif; ?>

<table class="table">
    <thead>
        <tr>
            <th>Number</th>
            <th>Model</th>
            <th>Comment</th>
            <th>Date added</th>
            <?php if (can_manage_cars($user['role'])): ?>
                <th>Actions</th>
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
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="form-row">
    <span class="badge">Page <?= $page ?> / <?= $pages ?></span>
    <?php if ($page > 1): ?>
        <a href="/cars.php?page=<?= $page - 1 ?>">Prev</a>
    <?php endif; ?>
    <?php if ($page < $pages): ?>
        <a href="/cars.php?page=<?= $page + 1 ?>">Next</a>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$title = 'Cars';
require __DIR__ . '/../templates/layout.php';
