<?php
/**
 * My account: the patient's bookings. Also the way out: sign out.
 */
require_once __DIR__ . '/includes/functions.php';

$me = require_patient();

if (is_post() && post('action') === 'logout') {
    patient_logout();
    redirect('index.php');
}

$appts = rows('SELECT * FROM appointments WHERE patient_id = ? ORDER BY preferred_date DESC, id DESC', 'i', [$me['id']]);

$page_title       = 'My bookings — Cedar Eye Clinic';
$page_description = 'Your eye test bookings with Cedar Eye Clinic.';
$canonical        = 'account.php';
$noindex          = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="page-head__inner">
        <h1 id="page-h" class="rise">Your bookings</h1>
        <p class="lead rise rise-2">Sign out here, or check a booking by its reference on <a class="link arrow" href="<?= e(url('booking-status.php')) ?>">Check a booking</a>.</p>
        <form method="post" action="<?= e(url('account.php')) ?>">
          <?= csrf_field() ?><button class="btn btn--ghost" type="submit">Sign out</button>
          <input type="hidden" name="action" value="logout">
        </form>
      </div>
      <div id="panel" class="stack-lg">
        <?php if (!$appts): ?>
          <div class="slip"><div class="slip__body"><p>No bookings yet. <a class="link" href="<?= e(url('book.php')) ?>">Book an eye test</a>.</p></div></div>
        <?php else: ?>
          <div class="slip">
            <div class="slip__head"><span class="rx" aria-hidden="true">Rx</span><span class="label">Your bookings</span></div>
            <div class="slip__body stack-lg">
            <?php foreach ($appts as $a): ?>
              <div class="booking">
                <div class="booking__head">
                  <span class="booking__ref"><?= e($a['ref']) ?></span>
                  <span class="booking__status"><?= e(APPT_STATUSES[$a['status']]) ?></span>
                </div>
                <dl class="spec">
                  <div><dt>Service</dt><dd><?= e($a['service_name']) ?></dd></div>
                  <div><dt>Day</dt><dd><?= e(date('l j F', strtotime($a['preferred_date']))) ?></dd></div>
                  <div><dt>Time of day</dt><dd><?= e(TIME_SLOTS[$a['preferred_time']]) ?></dd></div>
                </dl>
                <a class="link arrow" href="<?= e(url('booking-status.php?ref=' . $a['ref'])) ?>">Check this booking</a>
              </div>
            <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
