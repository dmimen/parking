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
<h1>Вход</h1>
<?php if ($message): ?>
    <div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<form method="post">
    <?= csrf_field() ?>
    <div class="form-row">
        <input type="text" name="phone" class="form-control" placeholder="Телефон (любой формат)" required>
        <button type="submit" class="btn btn-primary">Получить OTP</button>
    </div>
</form>
<?php
$content = ob_get_clean();
$title = 'Вход';
$user = null;
require __DIR__ . '/../templates/layout.php';
