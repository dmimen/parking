<?php
/** @var array $user */
/** @var string $title */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Parking', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="nav">
    <div class="nav-brand">Parking System</div>
    <?php if (!empty($user)): ?>
        <nav class="nav-links">
            <a href="/cars.php">Cars</a>
            <?php if (can_manage_users($user['role'])): ?>
                <a href="/users.php">Users</a>
            <?php endif; ?>
            <?php if (can_view_remote($user['role'])): ?>
                <a href="/remote_cars.php">Remote Cars</a>
            <?php endif; ?>
            <a href="/logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?= $content ?>
</main>
<script src="/assets/app.js"></script>
</body>
</html>
