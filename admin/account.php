<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();
$errors = [];
$name = $me['name'];

if (is_post()) {
    admin_post_guard();
    $name    = clip(post('name'), 120);
    $current = (string) ($_POST['current'] ?? '');
    $next    = (string) ($_POST['next'] ?? '');
    $hash    = (string) val('SELECT password_hash FROM admins WHERE id = ?', 'i', [$me['id']]);
    if ($name === '') $errors['name'] = 'Write your name.';
    if ($next !== '') {
        if (!password_verify($current, $hash)) $errors['current'] = 'That is not your current password.';
        if (strlen($next) < 10) $errors['next'] = 'Use at least 10 characters.';
    }
    if (!$errors) {
        q('UPDATE admins SET name = ? WHERE id = ?', 'si', [$name, $me['id']]);
        if ($next !== '') {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', 'si', [password_hash($next, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);
        }
        flash('ok', $next !== '' ? 'Your name and password were saved.' : 'Your name was saved.');
        redirect('admin/account.php');
    }
}
$err = fn($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-sm text-red-700">' . e($errors[$k]) . '</p>' : '';

$admin_title = 'Your account';
$admin_nav   = 'account';
require __DIR__ . '/_header.php';
?>
<header class="mb-8"><h1 class="text-4xl tracking-tight">Your account</h1><p class="text-ink-muted mt-1"><?= e($me['email']) ?> · <?= $me['role'] === 'owner' ? 'Owner' : 'Staff' ?></p></header>
<form method="post" class="rounded-md bg-white border border-line p-6 lg:p-8 grid gap-5 max-w-xl" novalidate>
  <?= csrf_field() ?>
  <div><label class="lbl" for="name">Name</label><input class="inp" id="name" name="name" value="<?= e($name) ?>" autocomplete="name"><?= $err('name') ?></div>
  <h2 class="text-lg font-medium mt-2">Change your password</h2>
  <div><label class="lbl" for="current">Current password</label><input class="inp" id="current" name="current" type="password" autocomplete="current-password"><?= $err('current') ?></div>
  <div><label class="lbl" for="next">New password</label><input class="inp" id="next" name="next" type="password" autocomplete="new-password"><p class="mt-1.5 text-sm text-ink-muted">10 characters or more. Leave both empty to keep your password.</p><?= $err('next') ?></div>
  <button class="btn btn-primary justify-self-start">Save</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
