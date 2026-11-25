<?php
require_once __DIR__ . '/../includes/functions.php';

if (current_admin()) {
    redirect('admin/');
}

$email = '';
$error = '';
if (is_post()) {
    $email = clip(post('email'), 190);
    $pass  = (string) ($_POST['password'] ?? '');
    if (!csrf_ok()) {
        $error = 'The form expired. Try again.';
    } elseif (throttled('login')) {
        $error = 'Too many attempts. Wait ten minutes and try again.';
    } else {
        $a = row('SELECT * FROM admins WHERE email = ? AND active = 1', 's', [$email]);
        if ($a && password_verify($pass, $a['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int) $a['id'];
            $_SESSION['admin_seen'] = time();
            if (password_needs_rehash($a['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE admins SET password_hash = ? WHERE id = ?', 'si', [password_hash($pass, PASSWORD_DEFAULT), $a['id']]);
            }
            q('UPDATE admins SET last_login = NOW() WHERE id = ?', 'i', [$a['id']]);
            $to = $_SESSION['after_login'] ?? '';
            unset($_SESSION['after_login']);
            // only return to a page inside the admin
            if ($to !== '' && str_starts_with($to, url('admin/')) && !str_contains($to, '//')) {
                header('Location: ' . $to, true, 303);
                exit;
            }
            redirect('admin/');
        }
        throttle_hit('login');
        $error = 'That email and password do not match an active account.';
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sign in · Cedar admin</title>
<link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="32x32">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="min-h-screen bg-[#f4f2ec] text-[#142120] lg:grid lg:grid-cols-2">
  <div class="hidden lg:flex flex-col justify-between bg-[#142120] text-white p-14 relative overflow-hidden">
    <a href="<?= e(url('')) ?>" class="flex items-center gap-3"><span class="grid place-items-center w-11 h-11 rounded-lg bg-white"><img src="<?= e(url('img/mark.png')) ?>" alt="" class="w-8"></span><span class="text-lg">Cedar Eye Clinic</span></a>
    <div class="chart text-center select-none" aria-hidden="true">
      <span class="text-[96px] blur-[0px]">E</span>
      <span class="text-[64px] blur-[.4px]">F P</span>
      <span class="text-[46px] blur-[.8px]">T O Z</span>
      <span class="text-[34px] blur-[1.2px] text-white/90">L P E D</span>
      <span class="text-[26px] blur-[1.6px] text-white/70">P E C F D</span>
      <span class="text-[20px] blur-[2px] text-white/50">E D F C Z P</span>
      <span class="text-[15px] blur-[2.4px] text-white/35">F E L O P Z D</span>
    </div>
    <p class="text-white/60 max-w-sm">Appointments, messages, the shop and clinic details for <?= e(setting('street', 'Sani Abacha Road')) ?>.</p>
  </div>
  <main class="flex items-center justify-center p-6 min-h-screen">
    <div class="w-full max-w-sm">
      <img src="<?= e(url('img/mark.png')) ?>" alt="Cedar Eye Clinic" class="h-9 mb-10 lg:hidden">
      <h1 class="text-3xl tracking-tight mb-2">Sign in</h1>
      <p class="text-[#56605e] mb-8">Clinic staff only.</p>
      <?php if (get('timeout')): ?><p class="mb-5 rounded-lg bg-[#dceae5] p-4 text-[#1f5a51]">You were signed out after two hours without activity.</p><?php endif; ?>
      <?php if ($error): ?><p role="alert" class="mb-5 rounded-lg bg-red-50 border border-red-200 p-4 text-red-800"><?= e($error) ?></p><?php endif; ?>
      <form method="post" class="space-y-5">
        <?= csrf_field() ?>
        <div><label for="email" class="block text-sm font-medium mb-1.5">Email</label><input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" class="inp h-12 w-full rounded-lg border border-[#bdb7a9] bg-white px-4 text-base"></div>
        <div><label for="password" class="block text-sm font-medium mb-1.5">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required class="inp h-12 w-full rounded-lg border border-[#bdb7a9] bg-white px-4 text-base"></div>
        <button class="h-12 w-full rounded-lg bg-[#2c7469] text-white font-medium hover:bg-[#1f5a51] active:translate-y-px transition">Sign in</button>
      </form>
      <p class="mt-8 text-sm"><a href="<?= e(url('')) ?>" class="text-[#56605e] hover:text-[#142120]">← Back to the website</a></p>
    </div>
  </main>
</body>
</html>
