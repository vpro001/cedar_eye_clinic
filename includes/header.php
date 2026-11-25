<?php

/**
 * The public site's <head> and header. Set these before including it:
 *   $page_title, $page_description   the <title> and meta description
 *   $canonical                       the page's own path for the canonical link ('' for the home page)
 *   $nav                             the current nav item: services|shop|corporate|about|contact ('' for none)
 *   $home                            true on the home page (the book button jumps to #book)
 *   $noindex                         true for pages search engines should leave out
 *   $wizard                          true on book.php: loads the booking wizard's script
 *   $og_image                        a site-relative image for share previews (defaults to share.jpg)
 */
require_once __DIR__ . '/functions.php';

$page_title       = $page_title ?? APP_NAME;
$page_description = $page_description ?? '';
$nav              = $nav ?? '';
$home             = $home ?? false;
$noindex          = $noindex ?? false;
$wizard           = $wizard ?? false;
$book_href        = $home ? '#book' : url('book.php');
$og_image         = $og_image ?? 'share.jpg';
$canonical        = $canonical ?? null;

$links = [
  'services'  => ['Services', 'services.php'],
  'shop'      => ['Shop', 'shop.php'],
  'corporate' => ['For organisations', 'corporate.php'],
  'about'     => ['About', 'about.php'],
  'contact'   => ['Contact', 'contact.php'],
];
$cur = fn(string $k) => $nav === $k ? ' aria-current="page"' : '';
?>
<!doctype html>
<html lang="en-NG">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_description) ?>">
  <?php if ($noindex): ?>
    <meta name="robots" content="noindex"><?php endif; ?>
  <?php if ($canonical !== null): ?>
    <link rel="canonical" href="<?= e(abs_url($canonical)) ?>"><?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
  <meta property="og:title" content="<?= e($page_title) ?>">
  <meta property="og:description" content="<?= e($page_description) ?>">
  <meta property="og:image" content="<?= e(abs_url($og_image)) ?>">
  <?php if ($canonical !== null): ?>
    <meta property="og:url" content="<?= e(abs_url($canonical)) ?>"><?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#f4f2ec">
  <link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="32x32">
  <link rel="icon" href="<?= e(url('img/icon-192.png')) ?>" type="image/png" sizes="192x192">
  <link rel="apple-touch-icon" href="<?= e(url('img/apple-touch-icon.png')) ?>">
  <link rel="preload" href="<?= e(url('assets/fonts/newsreader-latin-opsz-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= e(url('assets/fonts/ibm-plex-sans-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
  <script src="<?= e(asset('js/boot.js')) ?>"></script>
  <script src="<?= e(asset('js/site.js')) ?>" defer></script>
  <?php if (!empty($wizard)): ?><script src="<?= e(asset('js/wizard.js')) ?>" defer></script><?php endif ?>
  <?php if ($home): ?><?= clinic_jsonld() ?><?php endif; ?>
</head>

<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-head">
    <div class="wrap site-head__bar">
      <a class="brand" href="<?= e(url('')) ?>" <?= $home ? ' aria-current="page"' : '' ?>><img src="<?= e(url('img/mark-104.png')) ?>" alt="" width="48" height="22"><span>Cedar Eye Clinic</span></a>
      <nav class="nav" id="site-nav" aria-label="Main">
        <?php foreach ($links as $k => [$label, $href]): ?>
          <a href="<?= e(url($href)) ?>" <?= $cur($k) ?>><?= e($label) ?></a>
        <?php endforeach; ?>
        <span class="nav__account"><a href="<?= e(url(current_patient() ? 'account.php' : 'login.php')) ?>"><?= current_patient() ? 'My account' : 'Sign in' ?></a></span>
        <span class="nav__book"><a class="btn btn--primary" href="<?= e($book_href) ?>"><?= MAIN_ACTION ?></a></span>
      </nav>
      <div class="head-actions">
        <a class="head-phone" href="<?= e(phone_href()) ?>"><?= e(phone_display()) ?></a>
        <a class="btn btn--primary btn--sm" href="<?= e($book_href) ?>"><?= MAIN_ACTION ?></a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
          <svg class="i-open" viewBox="0 0 18 18" aria-hidden="true">
            <path d="M2 6h14M2 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          </svg>
          <svg class="i-close" viewBox="0 0 18 18" aria-hidden="true">
            <path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          </svg>
          Menu
        </button>
      </div>
    </div>
  </header>
  <main id="main">