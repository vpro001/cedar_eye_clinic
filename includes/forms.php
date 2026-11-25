<?php
/**
 * The booking form, shared by the home page (#book) and book.php, and its server-side handling.
 * It posts to book.php as a normal form: the request is stored, then the visitor lands on a
 * confirmation that shows the booking reference. site.js checks the fields first; the server always checks again.
 */

require_once __DIR__ . '/functions.php';

function booking_defaults(): array
{
    $p = current_patient();
    return ['values' => [
        'name'    => $p ? $p['name'] : '',
        'phone'   => $p ? $p['phone'] : '',
        'service' => get('service'),
        'date'    => '',
        'time'    => '',
        'notes'   => '',
    ], 'errors' => []];
}

/** The line under the date field: which days the clinic is closed on. */
function booking_date_hint(): string
{
    $closed = [];
    foreach (['mon-fri' => 'Monday to Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'] as $k => $l) {
        foreach (hours() as $h) {
            if ($h['days'] === $k && empty($h['open'])) $closed[] = $l === 'Monday to Friday' ? 'weekdays' : $l . 's';
        }
    }
    return 'Choose a day from today on.' . ($closed ? ' We are closed on ' . implode(' and ', $closed) . '.' : '');
}

/** One time of day as "9:00" (from minutes after midnight). */
function clock_at(int $mins): string
{
    return intdiv($mins, 60) . ':' . str_pad((string) ($mins % 60), 2, '0', STR_PAD_LEFT);
}

function booking_form(array $state, bool $compact = false): string
{
    $svc = [];
    foreach (services_list() as $s) {
        $svc[$s['slug']] = $s['name'];
    }
    $min = date('Y-m-d');
    $max = date('Y-m-d', strtotime('+90 days'));
    $dateHint = booking_date_hint();
    ob_start(); ?>
<form class="form" action="<?= e(url('book.php')) ?>" method="post" novalidate>
  <?= csrf_field() ?>
  <?= honeypot() ?>
  <div class="form__grid">
    <?= field('name', 'Full name', 'text', $state, ['required' => 1, 'autocomplete' => 'name', 'maxlength' => 120, 'error' => 'Write the name the booking is under.']) ?>
    <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write a phone number we can call, like 0803 123 4567.']) ?>
    <?= field('service', 'Service', 'select', $state, ['required' => 1, 'full' => 1, 'options' => $svc, 'placeholder' => 'Choose a service', 'error' => 'Choose the service you are booking.']) ?>
    <?= field('date', 'Preferred day', 'date', $state, ['required' => 1, 'min' => $min, 'max' => $max, 'error' => $dateHint]) ?>
    <?= field('time', 'Time of day', 'select', $state, ['required' => 1, 'options' => TIME_SLOTS, 'placeholder' => 'Choose a time', 'error' => 'Choose morning, midday or afternoon.']) ?>
    <?php if (!$compact): ?><?= field('notes', 'Anything we should know (optional)', 'textarea', $state, ['full' => 1, 'maxlength' => 1000]) ?><?php endif; ?>
  </div>
  <div class="form__foot">
    <button class="btn btn--primary" type="submit"><?= MAIN_ACTION ?></button>
    <p class="form__note">We call you to confirm the exact time.</p>
  </div>
  <?= form_status($state) ?>
</form>
<?php
    return ob_get_clean();
}

/**
 * The confirmation slip, shown after a booking is stored. Shared by book.php (no scripts) and
 * api/book.php (the wizard swaps it in without a reload), so both say the same thing.
 */
function booking_slip(array $appt): string
{
    ob_start(); ?>
<div class="slip" role="status">
  <div class="slip__head"><span class="rx" aria-hidden="true">Rx</span><span class="label">Booking reference</span></div>
  <div class="slip__body stack-lg">
    <p class="ref"><?= e($appt['ref']) ?></p>
    <dl class="spec">
      <div><dt>Service</dt><dd><?= e($appt['service_name']) ?></dd></div>
      <div><dt>Day asked for</dt><dd><?= e(date('l j F', strtotime($appt['preferred_date']))) ?></dd></div>
      <div><dt>Time of day</dt><dd><?= e(TIME_SLOTS[$appt['preferred_time']] ?? '') ?></dd></div>
      <div><dt>We call</dt><dd class="mono"><?= e($appt['phone']) ?></dd></div>
    </dl>
    <p>We have your request. Keep the reference: we call you during opening hours to confirm the exact time.</p>
    <div class="btn-row">
      <a class="btn btn--ghost" href="<?= e(url('booking-status.php?ref=' . $appt['ref'])) ?>">Check booking status</a>
      <a class="link" href="<?= e(wa_link('Hello Cedar Eye Clinic, I just booked online. My reference is ' . $appt['ref'] . '.')) ?>" rel="noopener" target="_blank">Send the reference on WhatsApp</a>
    </div>
    <?php if (current_patient()): ?>
      <p class="small muted"><a class="link" href="<?= e(url('account.php')) ?>">See this booking in your account</a></p>
    <?php endif; ?>
  </div>
</div>
<?php
    return ob_get_clean();
}

/**
 * The booking wizard: three steps in one form (service, day and time, your details).
 * Without scripts every step is on the page and the form posts to book.php as it always has;
 * scripts hide the steps that are not current, check live availability and post to api/book.php.
 */
function booking_wizard(array $state): string
{
    $v   = $state['values'];
    $err = $state['errors'];
    $min = date('Y-m-d');
    $max = date('Y-m-d', strtotime('+90 days'));
    $me  = current_patient();
    $svc = $v['service'];
    $day = $v['date'] !== '' && strtotime($v['date']) ? date('l j F Y', strtotime($v['date'])) : '';
    $when = $v['time'] !== '' && isset(TIME_SLOTS[$v['time']]) ? TIME_SLOTS[$v['time']] : '';
    $svcName = '';
    if ($svc !== '') {
        $row = row('SELECT name FROM services WHERE slug = ? AND active = 1', 's', [$svc]);
        $svcName = $row ? $row['name'] : '';
    }
    ob_start(); ?>
<form class="form wiz" action="<?= e(url('book.php')) ?>" method="post" novalidate
      data-wiz data-book="<?= e(url('api/book.php')) ?>" data-slots="<?= e(url('api/slots.php')) ?>" data-done="<?= e(url('book.php?done=')) ?>">
  <?= csrf_field() ?>
  <?= honeypot() ?>

  <ol class="wiz__rail" aria-label="Steps">
    <li data-rail="1"><span class="wiz__rail-n">1</span> Service</li>
    <li data-rail="2"><span class="wiz__rail-n">2</span> Day &amp; time</li>
    <li data-rail="3"><span class="wiz__rail-n">3</span> Your details</li>
  </ol>

  <fieldset class="wiz__step is-active" data-step="1">
    <legend class="wiz__legend">What are you coming in for?</legend>
    <div class="wiz__opts" role="radiogroup" aria-label="Service" aria-describedby="f-service-err" tabindex="-1">
      <?php foreach (services_list() as $s): ?>
      <label class="wiz__opt">
        <input type="radio" name="service" value="<?= e($s['slug']) ?>" required<?= $svc === $s['slug'] ? ' checked' : '' ?>>
        <span class="wiz__opt-body">
          <span class="wiz__opt-t"><?= e($s['name']) ?></span>
          <span class="wiz__opt-s"><?= e($s['summary']) ?></span>
        </span>
      </label>
      <?php endforeach; ?>
    </div>
    <p class="field__error" id="f-service-err"<?= isset($err['service']) ? '' : ' hidden' ?>><?= e($err['service'] ?? 'Choose the service you are booking.') ?></p>
    <div class="wiz__nav">
      <button class="btn btn--primary" type="button" data-next>Continue</button>
      <span class="form__note">Step 1 of 3</span>
    </div>
  </fieldset>

  <fieldset class="wiz__step" data-step="2">
    <legend class="wiz__legend">Which day suits you?</legend>
    <div class="form__grid">
      <?= field('date', 'Preferred day', 'date', $state, ['required' => 1, 'min' => $min, 'max' => $max, 'error' => booking_date_hint()]) ?>
    </div>
    <p class="wiz__lbl" id="f-time-lbl">Time of day</p>
    <div class="wiz__opts wiz__opts--slots" role="radiogroup" aria-labelledby="f-time-lbl" aria-describedby="f-time-err" tabindex="-1">
      <?php foreach (TIME_SLOTS as $k => $label): $span = TIME_SLOT_SPANS[$k]; ?>
      <label class="wiz__opt wiz__opt--slot" data-slot="<?= e($k) ?>">
        <input type="radio" name="time" value="<?= e($k) ?>" required<?= $v['time'] === $k ? ' checked' : '' ?>>
        <span class="wiz__opt-body">
          <span class="wiz__opt-t"><?= e(explode(' (', $label)[0]) ?></span>
          <span class="wiz__opt-note" data-note><?= e(clock_at($span[0]) . ' to ' . clock_at($span[1])) ?></span>
        </span>
      </label>
      <?php endforeach; ?>
    </div>
    <p class="field__error" id="f-time-err"<?= isset($err['time']) ? '' : ' hidden' ?>><?= e($err['time'] ?? 'Choose morning, midday or afternoon.') ?></p>
    <p class="wiz__avail" data-avail role="status" hidden></p>
    <div class="wiz__nav">
      <button class="btn btn--ghost" type="button" data-back>Back</button>
      <button class="btn btn--primary" type="button" data-next>Continue</button>
      <span class="form__note">Step 2 of 3</span>
    </div>
  </fieldset>

  <fieldset class="wiz__step" data-step="3">
    <legend class="wiz__legend">Where do we reach you?</legend>
    <dl class="spec wiz__sum" data-summary>
      <div><dt>Service</dt><dd data-sum="service"><?= e($svcName) ?></dd></div>
      <div><dt>Day</dt><dd data-sum="day"><?= e($day) ?></dd></div>
      <div><dt>Time of day</dt><dd data-sum="time"><?= e($when) ?></dd></div>
    </dl>
    <div class="form__grid">
      <?= field('name', 'Full name', 'text', $state, ['required' => 1, 'autocomplete' => 'name', 'maxlength' => 120, 'error' => 'Write the name the booking is under.']) ?>
      <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write a phone number we can call, like 0803 123 4567.']) ?>
      <?= field('notes', 'Anything we should know (optional)', 'textarea', $state, ['full' => 1, 'maxlength' => 1000]) ?>
    </div>
    <?php if ($me): ?>
      <p class="wiz__who">Booking as <b><?= e($me['name']) ?></b> · <a class="link" href="<?= e(url('account.php')) ?>">your bookings</a></p>
    <?php else: ?>
      <p class="wiz__who">Have an account? <a class="link" href="<?= e(url('login.php?next=book.php')) ?>">Sign in</a> and this booking is kept with the others.</p>
    <?php endif; ?>
    <div class="wiz__nav">
      <button class="btn btn--ghost" type="button" data-back>Back</button>
      <span class="form__note">Step 3 of 3</span>
    </div>
    <div class="form__foot">
      <button class="btn btn--primary" type="submit"><?= MAIN_ACTION ?></button>
      <p class="form__note">We call you to confirm the exact time.</p>
    </div>
    <?= form_status($state) ?>
  </fieldset>
</form>
<?php
    return ob_get_clean();
}

/** Validates and stores a booking. Returns the reference, or fills $state['errors']. */
function booking_handle(array &$state): ?string
{
    $v = [
        'name'    => clip(post('name'), 120),
        'phone'   => clip(post('phone'), 40),
        'service' => post('service'),
        'date'    => post('date'),
        'time'    => post('time'),
        'notes'   => clip(post('notes'), 1000),
    ];
    $state['values'] = $v;
    $err = [];

    if ($v['name'] === '') $err['name'] = 'Write the name the booking is under.';
    if (!csrf_ok()) $err['form'] = 'The form expired while it was open. Send it again.';
    if (!valid_phone($v['phone'])) $err['phone'] = 'Write a phone number we can call, like 0803 123 4567.';
    $service = $v['service'] !== '' ? row('SELECT id, name FROM services WHERE slug = ? AND active = 1', 's', [$v['service']]) : null;
    if (!$service) $err['service'] = 'Choose the service you are booking.';
    $d = DateTime::createFromFormat('Y-m-d', $v['date']);
    if (!$d || $d->format('Y-m-d') !== $v['date']) {
        $err['date'] = 'Choose the day you want to come.';
    } elseif ($v['date'] < date('Y-m-d') || $v['date'] > date('Y-m-d', strtotime('+90 days'))) {
        $err['date'] = 'Choose a day between today and three months from now.';
    } elseif (!is_open_day($d)) {
        $err['date'] = 'We are closed on ' . $d->format('l') . 's. Choose another day.';
    }
    if (!isset(TIME_SLOTS[$v['time']])) {
        $err['time'] = 'Choose morning, midday or afternoon.';
    } elseif (!isset($err['date']) && $d && !slot_fits($d, $v['time'])) {
        $h = hours_for($d);
        $err['time'] = 'On ' . $d->format('l') . 's we are open ' . $h['open'] . ' to ' . $h['close'] . '. Choose an earlier time of day.';
    } elseif (!isset($err['date']) && $d && slot_taken($v['date'], $v['time']) >= BOOKING_PER_SLOT) {
        $err['time'] = 'That time of day is already full. Choose another one.';
    }

    if (!$err && throttled('booking')) {
        $err['form'] = 'Too many requests from this connection. Call or WhatsApp the clinic instead.';
    }
    $state['errors'] = $err;
    if ($err) {
        return null;
    }
    if (is_bot()) {
        return 'CE-000000';
    }

    $patient = current_patient();
    $ref = new_ref();
    q('INSERT INTO appointments (ref, patient_id, name, phone, service_id, service_name, preferred_date, preferred_time, notes, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'sissssssss', [$ref, $patient ? $patient['id'] : null, $v['name'], $v['phone'], $service['id'], $service['name'], $v['date'], $v['time'], $v['notes'], client_ip()]);
    $apptId = last_id();
    throttle_hit('booking');
    $_SESSION['last_booking'] = $ref;
    notify_staff('New booking ' . $ref . ': ' . $service['name'], [
        'Reference' => $ref,
        'Name'      => $v['name'],
        'Phone'     => $v['phone'],
        'Service'   => $service['name'],
        'Day'       => $d->format('l j F Y'),
        'Time'      => TIME_SLOTS[$v['time']],
        'Note'      => $v['notes'] !== '' ? $v['notes'] : '(none)',
    ], 'admin/appointment.php?id=' . $apptId);
    return $ref;
}

/**
 * A short enquiry (contact page, product question, staff screening). $kind: contact|product|corporate.
 * Returns true when stored.
 */
function enquiry_handle(string $kind, array &$state, ?int $productId = null): bool
{
    $v = [
        'name'         => clip(post('name'), 120),
        'phone'        => clip(post('phone'), 40),
        'email'        => clip(post('email'), 190),
        'organisation' => clip(post('organisation'), 160),
        'staff_count'  => clip(post('staff_count'), 40),
        'message'      => clip(post('message'), 2000),
    ];
    $state['values'] = $v;
    $err = [];
    if ($v['name'] === '') $err['name'] = 'Write your name.';
    if (!csrf_ok()) $err['form'] = 'The form expired while it was open. Send it again.';
    if (!valid_phone($v['phone'])) $err['phone'] = 'Write a phone number we can call, like 0803 123 4567.';
    if ($v['email'] !== '' && !valid_email($v['email'])) $err['email'] = 'Write an email address with an @, or leave it empty.';
    if ($kind === 'corporate' && $v['organisation'] === '') $err['organisation'] = 'Write the name of your company, school or HMO.';
    if ($kind !== 'corporate' && mb_strlen($v['message']) < 5) $err['message'] = 'Write your question in a few words.';
    if (!$err && throttled('enquiry')) $err['form'] = 'Too many messages from this connection. Call or WhatsApp the clinic instead.';
    $state['errors'] = $err;
    if ($err) {
        return false;
    }
    if (is_bot()) {
        return true;
    }
    q('INSERT INTO enquiries (type, name, phone, email, organisation, staff_count, product_id, message, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssssiss', [$kind, $v['name'], $v['phone'], $v['email'], $v['organisation'], $v['staff_count'], $productId, $v['message'], client_ip()]);
    $enqId = last_id();
    throttle_hit('enquiry');
    $lines = ['From' => $v['name'], 'Phone' => $v['phone']];
    if ($v['email'] !== '') $lines['Email'] = $v['email'];
    if ($v['organisation'] !== '') $lines['Organisation'] = $v['organisation'];
    if ($v['staff_count'] !== '') $lines['People to screen'] = $v['staff_count'];
    if ($productId) $lines['Item'] = (string) val('SELECT name FROM products WHERE id = ?', 'i', [$productId]);
    $lines[] = '';
    $lines[] = $v['message'] !== '' ? $v['message'] : '(no message)';
    notify_staff('New ' . strtolower(ENQUIRY_TYPES[$kind]) . ' from ' . $v['name'], $lines, 'admin/enquiries.php?open=' . $enqId . '#m' . $enqId);
    return true;
}
