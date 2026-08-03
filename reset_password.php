<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = get_db();
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$tokenHash = hash('sha256', $token);
$errors = [];
$success = false;

$stmt = $pdo->prepare(
    "SELECT pr.*, u.email FROM password_resets pr
     JOIN users u ON u.id = pr.user_id
     WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()"
);
$stmt->execute([$tokenHash]);
$reset = $stmt->fetch();

if (!$reset) {
    $errors[] = 'This reset link is invalid or has expired. Please request a new one.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $newPassword = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($newPassword !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([$reset['id']]);
        $pdo->commit();

        $success = true;
    }
}

$pageTitle = APP_NAME . ' — Reset Password';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center pt-4 pb-16">
    <div class="w-full max-w-md bg-surface rounded-3xl border border-accent/10 shadow-glow p-9">
        <h1 class="text-2xl font-bold text-center mb-6">Reset Password</h1>

        <?php if ($success): ?>
            <div class="rounded-xl px-4 py-3 text-sm bg-emerald-950/40 border border-accent/40 text-accent mb-4">
                Your password has been updated.
            </div>
            <a href="<?= e(url('sign_in.php')) ?>" class="block text-center px-6 py-3 rounded-xl btn-gradient font-semibold">Sign In</a>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="rounded-xl px-4 py-3 text-sm bg-red-950/40 border border-red-500/40 text-red-300 mb-4">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($reset): ?>
                <form action="<?= e(url('reset_password.php')) ?>" method="post" class="flex flex-col gap-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="password" name="new_password" placeholder="New password (min. 8 characters)" class="input-field" minlength="8" required>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" class="input-field" minlength="8" required>
                    <button class="px-6 py-3 rounded-xl btn-gradient font-semibold">Update Password</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('changepass.php')) ?>" class="block text-center px-6 py-3 rounded-xl btn-gradient font-semibold">Request a New Link</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
