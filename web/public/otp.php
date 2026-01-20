<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/otp_service.php';

if (empty($_SESSION['pending_user_id'])) {
    header('Location: /login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $pdo = db($config);
    if (verify_otp($pdo, (int) $_SESSION['pending_user_id'], $code, $config['app']['secret'])) {
        $_SESSION['user_id'] = $_SESSION['pending_user_id'];
        unset($_SESSION['pending_user_id']);
        header('Location: /index.php');
        exit;
    }
    $message = 'Неверный или просроченный OTP.';
}

ob_start();
?>
<h1>Подтверждение OTP</h1>
<?php if ($message): ?>
    <div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post">
    <?= csrf_field() ?>
    <div class="form-row">
        <input type="text" name="code" placeholder="Код из Telegram" required maxlength="8">
        <button type="submit">Подтвердить</button>
    </div>
</form>
<?php
$content = ob_get_clean();
$title = 'Подтверждение';
$user = null;
require __DIR__ . '/../templates/layout.php';
