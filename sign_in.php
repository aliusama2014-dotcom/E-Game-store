<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Very light brute-force throttle per session.
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] > 10) {
        $error = 'Too many attempts. Please wait a moment and try again.';
    } else {
        $email = post_str('email');
        $password = $_POST['password'] ?? '';

        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($_SESSION['login_attempts']);
            login_user($user);
            flash_set('success', 'Welcome back, ' . $user['first_name'] . '!');
            redirect('index.php');
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}

$pageTitle = APP_NAME . ' — Sign In';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center pt-4 pb-16">
    <form action="<?= e(url('sign_in.php')) ?>" method="post" class="w-full max-w-md bg-surface rounded-3xl border border-accent/10 shadow-glow hover:shadow-glowLg transition p-9 flex flex-col gap-4">
        <h1 class="text-2xl font-bold text-center mb-2">Sign In</h1>

        <?php if ($error): ?>
            <div class="rounded-xl px-4 py-3 text-sm bg-red-950/40 border border-red-500/40 text-red-300"><?= e($error) ?></div>
        <?php endif; ?>

        <?= csrf_field() ?>
        <input type="email" name="email" placeholder="Email" class="input-field" required>
        <input type="password" name="password" placeholder="Password" class="input-field" required>

        <div class="flex items-center justify-between text-sm">
            <a href="<?= e(url('changepass.php')) ?>" class="text-muted hover:text-accent transition">Forgot password?</a>
        </div>

        <button class="mt-2 px-6 py-3 rounded-xl btn-gradient font-semibold">Login</button>

        <p class="text-center text-sm text-muted mt-2">
            New here? <a href="<?= e(url('sign_up.php')) ?>" class="text-accent hover:underline">Create an account</a>
        </p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
