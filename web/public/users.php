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
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h3 mb-1">Пользователи</h1>
        <p class="text-muted mb-0">Управление ролями и статусами доступа.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">Добавить пользователя</div>
    <div class="card-body">
        <form method="post" action="/api/users_crud.php" class="row g-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="col-md-4">
                <label class="form-label">Имя</label>
                <input type="text" name="name" class="form-control" placeholder="Иван Иванов" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон</label>
                <input type="text" name="phone" class="form-control" placeholder="+7 (900) 000-00-00" required>
                <div class="form-note mt-1">Формат произвольный: 7/8/+7 — все варианты подойдут.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Роль</label>
                <select name="role" class="form-select">
                    <?php if ($user['role'] === 'admin'): ?>
                        <option value="admin">admin</option>
                    <?php endif; ?>
                    <option value="manager">manager</option>
                    <option value="guard">guard</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Список пользователей</div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Телефон</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th class="text-end">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge text-bg-light badge-role"><?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'active'): ?>
                                <span class="badge text-bg-success">Активен</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Заблокирован</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form id="toggle-<?= (int) $row['id'] ?>" method="post" action="/api/users_crud.php" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-confirm="statusModal" data-form="toggle-<?= (int) $row['id'] ?>">
                                    <i class="bi bi-shield"></i>
                                    <?= $row['status'] === 'active' ? 'Заблокировать' : 'Разблокировать' ?>
                                </button>
                            </form>
                            <?php if ($row['id'] !== $user['id']): ?>
                                <form id="delete-<?= (int) $row['id'] ?>" method="post" action="/api/users_crud.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-confirm="deleteModal" data-form="delete-<?= (int) $row['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтвердите действие</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Изменить статус пользователя?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" data-confirm-submit>Подтвердить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Удаление пользователя</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Вы действительно хотите удалить пользователя?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" data-confirm-submit>Удалить</button>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Пользователи';
$current = 'users';
require __DIR__ . '/../templates/layout.php';
