<?php
if (!empty($_SESSION['flash'])):
    foreach ($_SESSION['flash'] as $item):
        $type = $item['type'] ?? 'info';
        $message = $item['message'] ?? '';
?>
    <div class="alert alert-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php
    endforeach;
    unset($_SESSION['flash']);
endif;
?>
