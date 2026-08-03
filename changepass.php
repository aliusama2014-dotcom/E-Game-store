<?php
require_once __DIR__ . '/includes/auth.php';

$sent = false;
$devResetLink = null; // only populated in development, to simulate an email

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = post_str('email');
    $pdo = get_db();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show the same message whether or not the account exists,
    // so this form can't be used to enumerate registered emails.
    $sent = true;

    if ($user) {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

        $insert = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        );
        $insert->execute([$user['id'], $tokenHash, $expiresAt]);

        $resetLink = url('reset_password.php?token=' . $rawToken);

        // In a real deployment this link would be emailed to the user
        // (e.g. via PHPMailer/SMTP) instead of ever being shown here.
        if (APP_ENV === 'development') {
            $devResetLink = $resetLink;
        }
    }
}

$pageTitle = APP_NAME . ' — Forgot Password';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center pt-4 pb-16">
    <div class="w-full max-w-md bg-surface rounded-3xl border border-accent/10 shadow-glow p-9">
        <h1 class="text-2xl font-bold text-center mb-2">Forgot Password</h1>
        <p class="text-muted text-sm text-center mb-6">Enter your email and we'll send you a reset link.</p>

        <?php if ($sent): ?>
            <div class="rounded-xl px-4 py-3 text-sm bg-emerald-950/40 border border-accent/40 text-accent mb-4">
                If that email is registered, a reset link has been sent.
            </div>
            <?php if ($devResetLink): ?>
                <div class="rounded-xl px-4 py-3 text-xs bg-black/40 border border-white/10 text-muted mb-4 break-all">
                    <strong class="text-gray-300">Dev mode only</strong> — no mail server is configured, so here's the link that would normally be emailed:<br>
                    <a href="<?= e($devResetLink) ?>" class="text-accent hover:underline"><?= e($devResetLink) ?></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="<?= e(url('changepass.php')) ?>" method="post" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <input type="email" name="email" placeholder="Your account email" class="input-field" required>
            <button class="px-6 py-3 rounded-xl btn-gradient font-semibold">Send Reset Link</button>
        </form>

        <p class="text-center text-sm text-muted mt-4">
            <a href="<?= e(url('sign_in.php')) ?>" class="text-accent hover:underline">Back to sign in</a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
