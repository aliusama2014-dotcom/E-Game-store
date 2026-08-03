<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$sent = false;
$old = ['name' => '', 'email' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old['name'] = post_str('name');
    $old['email'] = post_str('email');
    $old['message'] = post_str('message');

    if ($old['name'] === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['message'] === '') {
        $errors[] = 'Please enter a message.';
    }

    if (empty($errors)) {
        $pdo = get_db();
        $pdo->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)')
            ->execute([$old['name'], $old['email'], $old['message']]);
        $sent = true;
        $old = ['name' => '', 'email' => '', 'message' => ''];
    }
}

$pageTitle = APP_NAME . ' — Contact Us';
require __DIR__ . '/includes/header.php';
?>

<section class="flex justify-center pt-4 pb-16">
    <div class="w-full max-w-md bg-surface rounded-3xl border border-accent/10 shadow-glow p-9">
        <h1 class="text-2xl font-bold text-center mb-6">Contact Us</h1>

        <?php if ($sent): ?>
            <div class="rounded-xl px-4 py-3 text-sm bg-emerald-950/40 border border-accent/40 text-accent mb-4">
                Thanks! Your message has been received — we'll get back to you soon.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="rounded-xl px-4 py-3 text-sm bg-red-950/40 border border-red-500/40 text-red-300 mb-4">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= e(url('contact_us.php')) ?>" method="post" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <input type="text" name="name" placeholder="Name" value="<?= e($old['name']) ?>" class="input-field" required>
            <input type="email" name="email" placeholder="Email" value="<?= e($old['email']) ?>" class="input-field" required>
            <textarea name="message" rows="5" placeholder="Any Question" class="input-field"><?= e($old['message']) ?></textarea>
            <button class="px-6 py-3 rounded-xl btn-gradient font-semibold">Send</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
