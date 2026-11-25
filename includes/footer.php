<?php
/**
 * The closing call to action, the footer and the phone action bar. Optional before including it:
 *   $close_h, $close_p   the closing heading and line
 */
$close_h   = $close_h ?? 'Walk in, or book ahead and skip the wait.';
$close_p   = $close_p ?? hours_line() . '. Your results are explained in plain words before you leave.';
$book_href = $book_href ?? url('book.php');
$status  = clinic_status();
?>
</main>

<footer class="site-foot">
  <div class="wrap">
    <div class="foot-cta">
      <div>
        <h2><?= e($close_h) ?></h2>
        <p><?= e($close_p) ?></p>
      </div>
      <div class="btn-row">
        <a class="btn btn--light btn--lg" href="<?= e($book_href) ?>"><?= MAIN_ACTION ?></a>
      </div>
    </div>

    <div class="foot-grid">
      <div class="foot-col">
        <p class="label">Find us</p>
        <address><?= e(setting('street')) ?>, <?= e(setting('district')) ?><br><?= e(setting('city')) ?></address>
        <p>Inside <?= e(setting('building')) ?>.</p>
        <p><a class="link arrow" href="<?= e(map_link()) ?>" rel="noopener" target="_blank">Get directions</a></p>
      </div>
      <div class="foot-col">
        <p class="label">Opening hours</p>
        <?= hours_dl() ?>
        <p class="small"><span class="dot<?= $status['open'] ? ' dot--open' : '' ?>" aria-hidden="true"></span><?= e($status['text']) ?></p>
      </div>
      <div class="foot-col">
        <p class="label">Talk to us</p>
        <div class="foot-links">
          <a href="<?= e(phone_href()) ?>">Call <?= e(phone_display()) ?></a>
          <a href="<?= e(wa_link('Hello Cedar Eye Clinic, ')) ?>" rel="noopener" target="_blank">WhatsApp the clinic</a>
          <?php if (setting('email')): ?><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a><?php endif; ?>
          <a href="<?= e(url('booking-status.php')) ?>">Check a booking</a>
        </div>
      </div>
      <div class="foot-col">
        <p class="label">Site</p>
        <nav class="foot-links" aria-label="Footer">
          <a href="<?= e(url('services.php')) ?>">Services</a>
          <a href="<?= e(url('shop.php')) ?>">Shop</a>
          <a href="<?= e(url('corporate.php')) ?>">For organisations</a>
          <a href="<?= e(url('about.php')) ?>">About</a>
          <a href="<?= e(url('contact.php')) ?>">Contact</a>
        </nav>
      </div>
    </div>

    <div class="foot-legal">
      <span class="foot-brand"><img src="<?= e(url('img/mark-104.png')) ?>" alt="" width="48" height="22"><span>one vision. Yours.</span></span>
      <span>&copy; <?= date('Y') ?> Cedar Eye Clinic</span>
      <a href="<?= e(url('privacy.php')) ?>">Privacy</a>
      <a href="<?= e(url('credits.php')) ?>">Photo credits</a>
    </div>
  </div>
</footer>

<?php if (empty($no_actionbar)): ?>
<nav class="actionbar" aria-label="Quick actions">
  <a class="btn btn--ghost" href="<?= e(phone_href()) ?>">Call</a>
  <a class="btn btn--primary" href="<?= e($book_href) ?>"><?= MAIN_ACTION ?></a>
</nav>
<?php endif; ?>
</body>
</html>
