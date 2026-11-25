<?php
require_once __DIR__ . '/includes/functions.php';
if (!headers_sent()) {
    http_response_code(404);
}
$page_title       = 'Page not found: Cedar Eye Clinic';
$page_description = 'This page is not on the Cedar Eye Clinic website.';
$noindex          = true;
$nav              = '';
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h" style="padding-bottom: var(--section)">
    <div class="wrap split">
      <div class="page-head__inner">
        <p class="label">404</p>
        <h1 id="page-h">This page is out of focus.</h1>
        <p class="lead">The address may be old or mistyped. Everything on the site is one step from the home page.</p>
        <div class="btn-row"><a class="btn btn--primary" href="<?= e(url('')) ?>">Go to the home page</a><a class="btn btn--ghost" href="<?= e(url('book.php')) ?>"><?= MAIN_ACTION ?></a></div>
      </div>
      <div aria-hidden="true" class="lost">
        <span>E</span><span>F P</span><span>T O Z</span>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
