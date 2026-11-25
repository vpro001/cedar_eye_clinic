<?php
/**
 * The booking wizard: three steps (service, day and time with what is left, your details).
 * With scripts off it is one form on one page that posts here and lands on the same confirmation;
 * with them on, api/slots.php shows live availability and api/book.php stores it without a reload.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/forms.php';

$state = booking_defaults();

if (is_post()) {
    $ref = booking_handle($state);
    if ($ref) {
        redirect('book.php?done=' . rawurlencode($ref));
    }
}

// the confirmation is shown only for the booking made in this browser session
$done = get('done');
$appt = null;
if ($done !== '' && ($_SESSION['last_booking'] ?? '') === $done) {
    $appt = row('SELECT * FROM appointments WHERE ref = ?', 's', [$done]);
}

$page_title       = $appt ? 'Booking received: Cedar Eye Clinic' : 'Book an eye test at Cedar Eye Clinic, GRA Phase 3';
$page_description = 'Request an eye examination, glasses fitting, glaucoma screening or a child\'s eye test. The clinic calls you to confirm a time.';
$canonical        = 'book.php';
$noindex          = $done !== '';
$no_actionbar     = true;
$wizard           = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" id="book" aria-labelledby="page-h">
    <div class="wrap book-layout">
      <div class="book-layout__head">
        <div class="page-head__inner">
          <h1 id="page-h" class="rise"><?= $appt ? 'Your request is in.' : 'Book an eye test' ?></h1>
          <p class="lead rise rise-2"><?= $appt ? 'Keep your reference. We call you during opening hours to confirm the exact time.' : 'Three short steps: what you need, when suits you, and how to reach you. We call you to confirm the exact time.' ?></p>
        </div>
      </div>
      <div class="book-layout__info stack-lg">
        <address class="lead" style="font-size: 1.0625rem">
          <?= address_html() ?><br><span class="muted">Inside <?= e(setting('building')) ?>, <?= e(setting('city')) ?>.</span>
          <a class="link arrow" href="<?= e(map_link()) ?>" rel="noopener" target="_blank">Directions</a>
        </address>
        <?= hours_dl() ?>
        <div class="contact-lines">
          <a href="<?= e(phone_href()) ?>"><span>Book by phone</span><span class="mono"><?= e(phone_display()) ?></span></a>
          <a href="<?= e(wa_link('Hello Cedar Eye Clinic, I would like to book an eye test.')) ?>" rel="noopener" target="_blank"><span>Book on WhatsApp</span><span class="mono">&rarr;</span></a>
        </div>
      </div>
      <div class="book-layout__form">
        <?php if ($appt): ?>
          <?= booking_slip($appt) ?>
        <?php else: ?>
          <div class="slip">
            <div class="slip__head"><span class="rx" aria-hidden="true">Rx</span><span class="label">Booking request</span></div>
            <div class="slip__body"><?= booking_wizard($state) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php
$close_h = 'Questions before you book?';
$close_p = 'Call or WhatsApp the clinic. ' . hours_line() . '.';
include __DIR__ . '/includes/footer.php';
