<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$old = ['fn' => '', 'ln' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old['fn'] = post_str('fn');
    $old['ln'] = post_str('ln');
    $old['email'] = post_str('email');
    $pass = $_POST['pass'] ?? '';
    $confirm = $_POST['cp'] ?? '';

    if ($old['fn'] === '' || $old['ln'] === '') {
        $errors[] = 'First and last name are required.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($pass) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($pass !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = get_db();

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$old['email']]);
        if ($check->fetch()) {
            $errors[] = 'An account with that email already exists.';
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash, role)
                 VALUES (?, ?, ?, ?, "customer")'
            );
            $insert->execute([
                $old['fn'],
                $old['ln'],
                $old['email'],
                password_hash($pass, PASSWORD_DEFAULT),
            ]);

            flash_set('success', 'Account created! You can now sign in.');
            redirect('sign_in.php');
        }
    }
}

$pageTitle = APP_NAME . ' — Sign Up';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center pt-4 pb-16">
    <form action="<?= e(url('sign_up.php')) ?>" method="post" class="w-full max-w-md bg-surface rounded-3xl border border-accent/10 shadow-glow hover:shadow-glowLg transition p-9 flex flex-col gap-4">
        <h1 class="text-2xl font-bold text-center mb-2">Create your account</h1>

        <?php if (!empty($errors)): ?>
            <div class="rounded-xl px-4 py-3 text-sm bg-red-950/40 border border-red-500/40 text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= csrf_field() ?>
        <input type="text" name="fn" placeholder="First Name" value="<?= e($old['fn']) ?>" class="input-field" required>
        <input type="text" name="ln" placeholder="Last Name" value="<?= e($old['ln']) ?>" class="input-field" required>
        <input type="email" name="email" placeholder="Email" value="<?= e($old['email']) ?>" class="input-field" required>
        <input type="password" name="pass" placeholder="Password (min. 8 characters)" class="input-field" minlength="8" required>
        <input type="password" name="cp" placeholder="Confirm Password" class="input-field" minlength="8" required>
        <button class="mt-2 px-6 py-3 rounded-xl btn-gradient font-semibold">Sign Up</button>

        <p class="text-center text-sm text-muted mt-2">
            Already have an account? <a href="<?= e(url('sign_in.php')) ?>" class="text-accent hover:underline">Sign in</a>
        </p>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
