<?php
require_once __DIR__ . '/includes/functions.php';

$state  = ['values' => ['ref' => strtoupper(get('ref')), 'phone' => ''], 'errors' => []];
$appt   = null;
$looked = false;

if (is_post()) {
    $state['values']['ref']   = strtoupper(clip(post('ref'), 12));
    $state['values']['phone'] = clip(post('phone'), 40);
    if (!csrf_ok()) {
        $state['errors']['form'] = 'The form expired while it was open. Send it again.';
    } elseif (throttled('lookup')) {
        $state['errors']['form'] = 'Too many tries from this connection. Wait ten minutes or call the clinic.';
    } else {
        throttle_hit('lookup');
        $looked = true;
        $ref = preg_replace('/[^A-Z0-9-]/', '', $state['values']['ref']);
        if (!str_starts_with($ref, 'CE-')) {
            $ref = 'CE-' . ltrim($ref, '-');
        }
        $digits = substr(preg_replace('/\D/', '', $state['values']['phone']), -7);
        $cand = row('SELECT * FROM appointments WHERE ref = ?', 's', [$ref]);
        // the reference alone is not enough: the last seven digits of the phone must match too
        if ($cand && strlen($digits) === 7 && substr(preg_replace('/\D/', '', $cand['phone']), -7) === $digits) {
            $appt = $cand;
        }
    }
}

$page_title       = 'Check a booking at Cedar Eye Clinic';
$page_description = 'See whether your eye test request has been confirmed, with your booking reference and phone number.';
$canonical        = 'booking-status.php';
$noindex          = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="page-head__inner">
        <h1 id="page-h" class="rise">Booking status</h1>
        <p class="lead rise rise-2">Write the reference you got when you booked, like CE-7K4Q2M, and the phone number you booked with.</p>
        <p class="muted">Lost your reference? Call <a class="link" href="<?= e(phone_href()) ?>"><?= e(phone_display()) ?></a> and we look it up by name.</p>
      </div>
      <div>
        <?php if ($appt): ?>
          <div class="slip" role="status">
            <div class="slip__head"><span class="rx" aria-hidden="true">Rx</span><span class="label"><?= e($appt['ref']) ?></span></div>
            <div class="slip__body stack-lg">
              <h2 class="h2" style="font-size: 2rem"><?= e(APPT_STATUSES[$appt['status']]) ?></h2>
              <dl class="spec">
                <div><dt>Service</dt><dd><?= e($appt['service_name']) ?></dd></div>
                <div><dt>When</dt><dd><?= $appt['slot_at'] ? e(date('l j F, H:i', strtotime($appt['slot_at']))) : e(date('l j F', strtotime($appt['preferred_date'])) . ', ' . strtolower(strtok(TIME_SLOTS[$appt['preferred_time']], ' '))) ?></dd></div>
              </dl>
              <?php if ($appt['status'] === 'pending'): ?><p>We have your request and call you to confirm the time.</p><?php endif; ?>
              <p><a class="link arrow" href="<?= e(wa_link('Hello Cedar Eye Clinic, about my booking ' . $appt['ref'] . ':')) ?>" rel="noopener" target="_blank">Change it on WhatsApp</a></p>
            </div>
          </div>
        <?php else: ?>
          <?php if ($looked): ?><div class="notice" role="status" style="margin-bottom: 24px"><p>No booking matches that reference and phone number. Check both, or call the clinic on <?= e(phone_display()) ?>.</p></div><?php endif; ?>
          <div class="slip"><div class="slip__body">
          <form class="form" method="post" action="<?= e(url('booking-status.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="form__grid">
              <?= field('ref', 'Booking reference', 'text', $state, ['required' => 1, 'autocomplete' => 'off', 'maxlength' => 12, 'error' => 'Write the reference, like CE-7K4Q2M.']) ?>
              <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write the phone number you booked with.']) ?>
            </div>
            <div class="form__foot"><button class="btn btn--primary" type="submit">Check the booking</button></div>
            <?= form_status($state) ?>
          </form>
          </div></div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
