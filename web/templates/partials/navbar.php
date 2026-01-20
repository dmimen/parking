<?php
/** @var array|null $user */
/** @var string $current */
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-xl">
        <a class="navbar-brand fw-semibold" href="/index.php">
            <i class="bi bi-p-circle me-2"></i>Парковка ДПОГО
        </a>
        <?php if (!empty($user)): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'cars' ? 'active' : '' ?>" href="/cars.php">Автомобили</a>
                    </li>
                    <?php if (can_manage_users($user['role'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current === 'users' ? 'active' : '' ?>" href="/users.php">Пользователи</a>
                        </li>
                    <?php endif; ?>
                    <?php if (can_view_remote($user['role'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current === 'remote' ? 'active' : '' ?>" href="/remote_cars.php">История удалений</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-secondary text-uppercase">
                        <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="text-white-50 small">
                        <?= htmlspecialchars($user['name'] ?? 'Пользователь', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="/logout.php">Выход</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>
