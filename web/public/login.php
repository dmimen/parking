<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/otp_service.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $phoneInput = $_POST['phone'] ?? '';
    $phone = normalize_phone($phoneInput);
    if ($phone === '') {
        $message = 'Введите корректный номер телефона.';
    } else {
        $pdo = db($config);
        // Ищем активного пользователя по телефону.
        $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = :phone AND status = "active"');
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch();
        if (!$user) {
            $message = 'Пользователь не найден или заблокирован.';
        } elseif (!$user['tg_id']) {
            $message = 'Привяжите Telegram: напишите боту /start и отправьте телефон.';
        } else {
            // Генерируем OTP, сохраняем хэш и ставим задачу в outbox.
            $code = generate_otp();
            $hash = hash_otp($code, $config['app']['secret']);
            create_otp_session($pdo, (int) $user['id'], $hash);
            enqueue_otp($pdo, (int) $user['id'], "Ваш OTP: {$code}");
            $_SESSION['pending_user_id'] = $user['id'];
            header('Location: /otp.php');
            exit;
        }
    }
}

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">Вход</h1>
                <p class="text-muted mb-4">Введите номер телефона. Мы отправим одноразовый код в Telegram.</p>
                <?php if ($message): ?>
                    <div class="alert alert-warning"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Телефон</label>
                        <input type="text" name="phone" class="form-control" placeholder="+7 (900) 000-00-00" required>
                        <div class="form-note mt-1">Формат произвольный: 7/8/+7 — все варианты подойдут.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Получить OTP</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = 'Вход';
$user = null;
$current = '';
require __DIR__ . '/../templates/layout.php';
