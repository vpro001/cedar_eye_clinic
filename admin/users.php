<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin('owner');
$errors = [];
$new = ['name' => '', 'email' => '', 'role' => 'staff'];

if (is_post()) {
    admin_post_guard();
    $act = post('action');
    $id  = (int) post('id');
    if ($act === 'add') {
        $new = ['name' => clip(post('name'), 120), 'email' => clip(post('email'), 190), 'role' => post('role') === 'owner' ? 'owner' : 'staff'];
        $pass = (string) ($_POST['password'] ?? '');
        if ($new['name'] === '') $errors['name'] = 'Write their name.';
        if (!valid_email($new['email'])) $errors['email'] = 'Write an email address with an @.';
        elseif (val('SELECT id FROM admins WHERE email = ?', 's', [$new['email']])) $errors['email'] = 'An account with this email already exists.';
        if (strlen($pass) < 10) $errors['password'] = 'Use at least 10 characters.';
        if (!$errors) {
            q('INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, ?)', 'ssss', [$new['name'], $new['email'], password_hash($pass, PASSWORD_DEFAULT), $new['role']]);
            flash('ok', $new['name'] . ' can now sign in. Give them the password in person.');
            redirect('admin/users.php');
        }
    } elseif ($id && $id !== (int) $me['id'] && in_array($act, ['disable', 'enable'], true)) {
        q('UPDATE admins SET active = ? WHERE id = ?', 'ii', [$act === 'enable' ? 1 : 0, $id]);
        flash('ok', $act === 'enable' ? 'The account can sign in again.' : 'The account can no longer sign in.');
        redirect('admin/users.php');
    } elseif ($id && $act === 'reset') {
        $pass = (string) ($_POST['new_password'] ?? '');
        if (strlen($pass) < 10) {
            flash('error', 'A new password needs at least 10 characters.');
        } else {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', 'si', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            flash('ok', 'The password was changed.');
        }
        redirect('admin/users.php');
    }
}
$list = rows('SELECT id, name, email, role, active, last_login, created_at FROM admins ORDER BY active DESC, role, name');
$err = fn($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-sm text-red-700">' . e($errors[$k]) . '</p>' : '';

$admin_title = 'Staff accounts';
$admin_nav   = 'users';
require __DIR__ . '/_header.php';
?>
<header class="mb-8"><h1 class="text-4xl tracking-tight">Staff accounts</h1><p class="text-ink-muted mt-1">Staff see bookings, messages, the shop and services. Only owners manage accounts.</p></header>
<div class="grid xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] gap-8 items-start">
  <div class="rounded-md bg-white border border-line overflow-hidden">
    <ul class="divide-y divide-line">
      <?php foreach ($list as $u): ?>
      <li class="px-6 py-5 grid gap-3 <?= $u['active'] ? '' : 'opacity-60' ?>">
        <div class="flex flex-wrap items-center gap-3">
          <span class="grid place-items-center w-10 h-10 rounded-full bg-sea-tint text-sea-dark font-medium"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
          <div class="min-w-0 flex-1"><p class="font-medium"><?= e($u['name']) ?><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="text-ink-muted font-normal">(you)</span>' : '' ?></p><p class="text-sm text-ink-muted break-all"><?= e($u['email']) ?></p></div>
          <span class="pill <?= $u['role'] === 'owner' ? 's-confirmed' : 's-read' ?>"><?= $u['role'] === 'owner' ? 'Owner' : 'Staff' ?></span>
          <?php if (!$u['active']): ?><span class="pill s-cancelled">Disabled</span><?php endif; ?>
        </div>
        <p class="text-sm text-ink-muted">Last signed in: <?= $u['last_login'] ? e(time_ago($u['last_login'])) : 'never' ?></p>
        <?php if ((int) $u['id'] !== (int) $me['id']): ?>
        <form method="post" class="flex flex-wrap gap-2 items-end">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
          <div><label class="lbl sr-only" for="np<?= (int) $u['id'] ?>">New password for <?= e($u['name']) ?></label><input class="inp h-9 w-56" id="np<?= (int) $u['id'] ?>" name="new_password" type="password" autocomplete="new-password" placeholder="New password"></div>
          <button class="btn btn-ghost h-9" name="action" value="reset">Set password</button>
          <button class="btn btn-ghost h-9" name="action" value="<?= $u['active'] ? 'disable' : 'enable' ?>"<?= $u['active'] ? ' data-confirm="Stop ' . e($u['name']) . ' from signing in?"' : '' ?>><?= $u['active'] ? 'Disable' : 'Enable' ?></button>
        </form>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <form method="post" class="rounded-md bg-white border border-line p-6 lg:p-8 grid gap-5" novalidate>
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h2 class="text-lg font-medium">Add a staff account</h2>
    <div><label class="lbl" for="name">Name</label><input class="inp" id="name" name="name" value="<?= e($new['name']) ?>"><?= $err('name') ?></div>
    <div><label class="lbl" for="email">Email</label><input class="inp" id="email" name="email" type="email" value="<?= e($new['email']) ?>"><?= $err('email') ?></div>
    <div><label class="lbl" for="password">Password</label><input class="inp" id="password" name="password" type="password" autocomplete="new-password"><?= $err('password') ?></div>
    <div><label class="lbl" for="role">Role</label><select class="inp" id="role" name="role"><option value="staff">Staff</option><option value="owner"<?= $new['role'] === 'owner' ? ' selected' : '' ?>>Owner (can manage accounts)</option></select></div>
    <button class="btn btn-primary justify-self-start">Add the account</button>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
