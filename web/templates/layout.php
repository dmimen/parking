<?php
/** @var array|null $user */
/** @var string $title */
/** @var string $content */
/** @var string $current */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Parking', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/navbar.php'; ?>
<main class="main-content">
    <div class="container-xl py-4">
        <?php require __DIR__ . '/partials/flash.php'; ?>
        <?= $content ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
