<?php
require_once __DIR__ . '/../src/bootstrap.php';

$user = require_login($config);
require_role(['admin', 'manager'], $user['role']);

$pdo = db($config);

if ($user['role'] === 'manager') {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE role != "admin" ORDER BY created_at DESC');
    $stmt->execute();
} else {
    $stmt = $pdo->prepare('SELECT * FROM users ORDER BY created_at DESC');
    $stmt->execute();
}
$users = $stmt->fetchAll();

ob_start();
?>
<h1>Пользователи</h1>
<form method="post" action="/api/users_crud.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row">
        <input type="text" name="name" placeholder="Имя" required>
        <input type="text" name="phone" placeholder="Телефон" required>
        <select name="role">
            <?php if ($user['role'] === 'admin'): ?>
                <option value="admin">admin</option>
            <?php endif; ?>
            <option value="manager">manager</option>
            <option value="guard">guard</option>
        </select>
        <button type="submit">Добавить</button>
    </div>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Имя</th>
            <th>Телефон</th>
            <th>Роль</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <form method="post" action="/api/users_crud.php" style="display:inline-block">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit">Сменить статус</button>
                    </form>
                    <?php if ($row['id'] !== $user['id']): ?>
                        <form method="post" action="/api/users_crud.php" style="display:inline-block">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                            <button type="submit">Удалить</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$content = ob_get_clean();
$title = 'Пользователи';
require __DIR__ . '/../templates/layout.php';
