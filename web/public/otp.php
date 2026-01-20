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
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">Подтверждение</h1>
                <p class="text-muted mb-4">Введите код из Telegram. Он действует 60 секунд.</p>
                <?php if ($message): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">OTP-код</label>
                        <input type="text" name="code" class="form-control" placeholder="8 символов" required maxlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Подтвердить</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Подтверждение';
$user = null;
$current = '';
require __DIR__ . '/../templates/layout.php';
