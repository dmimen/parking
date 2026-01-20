<?php

declare(strict_types=1);

require_once __DIR__ . '/validators.php';

function otp_alphabet(): string
{
    return 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
}

function generate_otp(): string
{
    $alphabet = otp_alphabet();
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $code;
}

function hash_otp(string $code, string $secret): string
{
    return hash_hmac('sha256', $code, $secret);
}

function create_otp_session(PDO $pdo, int $userId, string $codeHash): void
{
    $stmt = $pdo->prepare('INSERT INTO otp_sessions (user_id, code_hash, expires_at) VALUES (:user_id, :code_hash, DATE_ADD(NOW(), INTERVAL 60 SECOND))');
    $stmt->execute([
        'user_id' => $userId,
        'code_hash' => $codeHash,
    ]);
}

function enqueue_otp(PDO $pdo, int $userId, string $message): void
{
    $stmt = $pdo->prepare('INSERT INTO otp_outbox (user_id, message) VALUES (:user_id, :message)');
    $stmt->execute([
        'user_id' => $userId,
        'message' => $message,
    ]);
}

function verify_otp(PDO $pdo, int $userId, string $inputCode, string $secret): bool
{
    $stmt = $pdo->prepare('SELECT * FROM otp_sessions WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
    $stmt->execute(['user_id' => $userId]);
    $session = $stmt->fetch();
    if (!$session) {
        return false;
    }
    if ($session['blocked_until'] && strtotime($session['blocked_until']) > time()) {
        return false;
    }
    if (strtotime($session['expires_at']) < time()) {
        return false;
    }

    $hash = hash_otp($inputCode, $secret);
    if (!hash_equals($session['code_hash'], $hash)) {
        $attempts = (int) $session['attempts'] + 1;
        $blockedUntil = null;
        if ($attempts >= 10) {
            $blockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
            $attempts = 0;
        }
        $update = $pdo->prepare('UPDATE otp_sessions SET attempts = :attempts, blocked_until = :blocked_until WHERE id = :id');
        $update->execute([
            'attempts' => $attempts,
            'blocked_until' => $blockedUntil,
            'id' => $session['id'],
        ]);
        return false;
    }

    $delete = $pdo->prepare('DELETE FROM otp_sessions WHERE id = :id');
    $delete->execute(['id' => $session['id']]);
    return true;
}
