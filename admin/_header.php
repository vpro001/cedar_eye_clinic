<?php
/**
 * Admin shell (top): sign-in check, <head>, sidebar and flash messages.
 * Set before including: $admin_title (page heading), $admin_nav (sidebar key), $admin_role ('owner' to restrict).
 */
require_once __DIR__ . '/../includes/functions.php';

$me          = require_admin($admin_role ?? null);
$admin_title = $admin_title ?? 'Dashboard';
$admin_nav   = $admin_nav ?? '';
$badge_appts = (int) val("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$badge_enq   = (int) val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");

$menu = [
    'dashboard'    => ['Dashboard', 'admin/', 'M3 12l9-8 9 8M5 10v10h5v-6h4v6h5V10'],
    'appointments' => ['Appointments', 'admin/appointments.php', 'M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z', $badge_appts],
    'enquiries'    => ['Messages', 'admin/enquiries.php', 'M4 6h16v11H8l-4 3z', $badge_enq],
    'products'     => ['Shop items', 'admin/products.php', 'M3 13a4 4 0 108 0 4 4 0 00-8 0zm10 0a4 4 0 108 0 4 4 0 00-8 0zM11 13h2'],
    'services'     => ['Services', 'admin/services.php', 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12zm10 3a3 3 0 100-6 3 3 0 000 6z'],
    'settings'     => ['Clinic details', 'admin/settings.php', 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.6 1.6 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.6 1.6 0 00-2.7 1.1V21a2 2 0 11-4 0v-.1a1.6 1.6 0 00-2.7-1.1l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.6 1.6 0 003 15H3a2 2 0 110-4h.1a1.6 1.6 0 001.1-2.7l-.1-.1a2 2 0 112.8-2.8l.1.1A1.6 1.6 0 009 4.6V3a2 2 0 114 0v.1a1.6 1.6 0 002.7 1.1l.1-.1a2 2 0 112.8 2.8l-.1.1a1.6 1.6 0 001.1 2.7H21a2 2 0 110 4h-.1a1.6 1.6 0 00-1.5 1z'],
];
if ($me['role'] === 'owner') {
    $menu['users'] = ['Staff accounts', 'admin/users.php', 'M16 19v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1M9 10a4 4 0 100-8 4 4 0 000 8zM22 19v-1a4 4 0 00-3-3.9M16 2.1a4 4 0 010 7.8'];
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($admin_title) ?> · Cedar admin</title>
<link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="32x32">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="bg-paper text-ink font-sans antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 btn btn-primary">Skip to content</a>
<div class="min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
  <aside class="bg-ink text-white/70 lg:sticky lg:top-0 lg:h-screen flex flex-col" aria-label="Admin">
    <div class="flex items-center justify-between px-5 h-16 lg:h-20">
      <a href="<?= e(url('admin/')) ?>" class="flex items-center gap-3 text-white">
        <span class="grid place-items-center w-10 h-10 rounded-lg bg-white"><img src="<?= e(url('img/mark.png')) ?>" alt="" class="w-7"></span>
        <span class="leading-tight"><span class="block font-medium">Cedar Eye Clinic</span><span class="block text-xs text-white/50">Admin</span></span>
      </a>
      <button type="button" class="lg:hidden btn text-white" aria-expanded="false" aria-controls="side-nav" data-menu-toggle>Menu</button>
    </div>
    <nav id="side-nav" class="hidden lg:flex flex-col gap-1 px-3 pb-4 lg:flex-1">
      <?php foreach ($menu as $key => $m): ?>
      <a href="<?= e(url($m[1])) ?>" class="nav-a flex items-center gap-3 rounded-lg px-3 h-11 hover:bg-white/5 hover:text-white transition"<?= $admin_nav === $key ? ' aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" class="w-5 h-5 flex-none" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= $m[2] ?>"/></svg>
        <span class="flex-1"><?= e($m[0]) ?></span>
        <?php if (!empty($m[3])): ?><span class="min-w-[1.5rem] h-6 px-1.5 grid place-items-center rounded-full bg-[#5fab9f] text-ink text-xs font-semibold"><?= (int) $m[3] ?></span><?php else: ?><span class="iris" aria-hidden="true"></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
      <div class="mt-auto pt-6 border-t border-white/10 grid gap-1">
        <a href="<?= e(url('')) ?>" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg px-3 h-11 hover:bg-white/5 hover:text-white">View the website ↗</a>
        <a href="<?= e(url('admin/account.php')) ?>" class="nav-a flex items-center gap-3 rounded-lg px-3 h-11 hover:bg-white/5 hover:text-white"<?= $admin_nav === 'account' ? ' aria-current="page"' : '' ?>><span class="flex-1 truncate"><?= e($me['name']) ?></span><span class="text-xs text-white/40">Account</span></a>
        <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button class="w-full text-left rounded-lg px-3 h-11 hover:bg-white/5 hover:text-white">Sign out</button></form>
      </div>
    </nav>
  </aside>
  <main id="main" class="min-w-0 px-5 py-8 lg:px-12 lg:py-12">
    <?php foreach (flashes() as $f): ?>
      <div role="status" class="mb-6 rounded px-5 py-4 text-[15px] <?= $f['type'] === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-sea-tint text-sea-dark border border-sea/20' ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
