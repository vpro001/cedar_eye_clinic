<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/forms.php';

$state = ['values' => ['name' => '', 'phone' => '', 'email' => '', 'organisation' => '', 'staff_count' => '', 'message' => ''], 'errors' => []];
if (is_post() && enquiry_handle('corporate', $state)) {
    redirect('corporate.php?sent=1#request');
}
$sent = get('sent') === '1';

$page_title       = 'Staff eye screening for companies, schools and HMOs in Port Harcourt';
$page_description = 'Cedar Eye Clinic screens staff and pupils on site or at the clinic, runs eye-health talks and supplies prescription safety eyewear.';
$canonical        = 'corporate.php';
$noindex          = $sent;
$nav              = 'corporate';
include __DIR__ . '/includes/header.php';
?>
  <header class="page-head page-head--split">
    <div class="wrap">
      <div class="page-head__inner">
        <h1 class="rise">Eye screening for your staff</h1>
        <p class="lead rise rise-2">Screens, machines and sun all strain the eyes. We set up eye-health programmes that fit your people, the work they do and where they do it, on site or at the clinic.</p>
      </div>
      <div class="btn-row rise rise-3"><a class="btn btn--primary btn--lg" href="#request">Request a staff screening</a></div>
    </div>
  </header>

  <section class="section" aria-labelledby="what-h">
    <div class="wrap split">
      <div class="section-head sticky">
        <h2 id="what-h">What a programme includes</h2>
        <p>Pick the parts you need. We quote after a short call about your team.</p>
      </div>
      <ol class="index">
        <?php foreach ([
            ['Workplace vision screening', 'Sight checks for your staff on site or at the clinic, with a referral for anyone who needs a full examination.'],
            ['Occupational eye-health assessments', 'For screen-based, industrial and field roles, matched to the risks of each job.'],
            ['Staff eye-health education', 'Short talks on screen use, eye safety at work and the warning signs to act on.'],
            ['Protective and prescription safety eyewear', 'Safety glasses and goggles, fitted with prescription lenses where needed.'],
        ] as $i => [$name, $sum]): ?>
        <li class="index__item"><div class="index__link"><span class="index__num"><?= sprintf('%02d', $i + 1) ?></span><span class="index__name"><?= e($name) ?></span><span class="index__sum"><?= e($sum) ?></span></div></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="section" aria-labelledby="who-h">
    <div class="wrap split split--rev">
      <div class="stack-lg">
        <div class="section-head"><h2 id="who-h">Who we work with</h2></div>
        <dl class="spec">
          <div><dt>Companies</dt><dd>Staff screening and programmes</dd></div>
          <div><dt>Schools</dt><dd>Pupil vision screening</dd></div>
          <div><dt>HMOs</dt><dd>Eye care for enrollees</dd></div>
          <div><dt>Hospitals and providers</dt><dd>Referral partnerships</dd></div>
          <div><dt>Industrial sites</dt><dd>Eye safety at work</dd></div>
        </dl>
        <p class="muted">Booking for yourself or your family? <a class="link" href="<?= e(url('book.php')) ?>"><?= MAIN_ACTION ?></a>.</p>
      </div>
      <figure>
        <div class="photo photo--land"><?= pic('img/safety-6.jpg', 'Padded black safety goggles on a desk', 640, '', '(max-width: 959px) 100vw, 520px') ?></div>
        <figcaption>Padded safety goggles, from the clinic's shop.</figcaption>
      </figure>
    </div>
  </section>

  <section class="section section--band" id="request" aria-labelledby="request-h">
    <div class="wrap split">
      <div class="section-head">
        <h2 id="request-h">Request a staff screening</h2>
        <p>Tell us about your organisation and we call you to plan the programme.</p>
        <p><a class="link" href="<?= e(phone_href()) ?>">Or call <?= e(phone_display()) ?></a></p>
      </div>
      <div>
        <?php if ($sent): ?>
          <div class="notice notice--ok" role="status"><h3>Your screening request is in.</h3><p>We call the number you gave during opening hours to plan it with you.</p></div>
        <?php else: ?>
        <div class="slip"><div class="slip__body">
        <form class="form" method="post" action="<?= e(url('corporate.php')) ?>#request" novalidate>
          <?= csrf_field() ?><?= honeypot() ?>
          <div class="form__grid">
            <?= field('organisation', 'Company, school or HMO', 'text', $state, ['required' => 1, 'full' => 1, 'autocomplete' => 'organization', 'maxlength' => 160, 'error' => 'Write the name of your company, school or HMO.']) ?>
            <?= field('name', 'Your name', 'text', $state, ['required' => 1, 'autocomplete' => 'name', 'maxlength' => 120, 'error' => 'Write your name.']) ?>
            <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write a phone number we can call, like 0803 123 4567.']) ?>
            <?= field('email', 'Work email (optional)', 'email', $state, ['autocomplete' => 'email', 'inputmode' => 'email', 'maxlength' => 190, 'error' => 'Write an email address with an @, or leave it empty.']) ?>
            <?= field('staff_count', 'People to screen (optional)', 'text', $state, ['inputmode' => 'numeric', 'maxlength' => 40]) ?>
            <?= field('message', 'What you need (optional)', 'textarea', $state, ['full' => 1, 'maxlength' => 2000]) ?>
          </div>
          <div class="form__foot"><button class="btn btn--primary" type="submit">Request a staff screening</button></div>
          <?= form_status($state) ?>
        </form>
        </div></div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php
$close_h = 'Looking after your own eyes?';
$close_p = 'Individuals and families can walk in or book. ' . hours_line() . '.';
include __DIR__ . '/includes/footer.php';
