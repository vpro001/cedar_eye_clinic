<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/forms.php';

$state = ['values' => ['name' => '', 'phone' => '', 'email' => '', 'message' => ''], 'errors' => []];
if (is_post() && enquiry_handle('contact', $state)) {
    redirect('contact.php?sent=1#message');
}
$sent   = get('sent') === '1';
$status = clinic_status();

$page_title       = 'Contact Cedar Eye Clinic: ' . setting('street') . ', ' . setting('district');
$page_description = 'Address, opening hours, phone and WhatsApp for Cedar Eye Clinic in Port Harcourt, and a form to send the clinic a message.';
$canonical        = 'contact.php';
$noindex          = $sent;
$nav              = 'contact';
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="stack-lg">
        <div class="page-head__inner">
          <h1 id="page-h" class="rise">Contact the clinic</h1>
          <p class="lead rise rise-2">Call, send a WhatsApp message or write to us here. We reply during opening hours.</p>
        </div>
        <div class="stack">
          <p class="label">Find us</p>
          <address class="h3" style="font-family: var(--serif); font-size: 1.75rem; font-weight: 400; line-height: 1.2"><?= address_html() ?>, <?= e(setting('city')) ?></address>
          <p class="muted">Inside <?= e(setting('building')) ?>.</p>
          <p><a class="link arrow" href="<?= e(map_link()) ?>" rel="noopener" target="_blank">Get directions</a></p>
        </div>
        <div class="stack">
          <p class="label">Opening hours</p>
          <?= hours_dl() ?>
          <p class="small"><span class="dot<?= $status['open'] ? ' dot--open' : '' ?>" aria-hidden="true"></span><?= e($status['text']) ?></p>
        </div>
        <div class="contact-lines">
          <a href="<?= e(phone_href()) ?>"><span>Call</span><span class="mono"><?= e(phone_display()) ?></span></a>
          <a href="<?= e(wa_link('Hello Cedar Eye Clinic, ')) ?>" rel="noopener" target="_blank"><span>WhatsApp</span><span class="mono">&rarr;</span></a>
          <?php if (setting('email')): ?><a href="mailto:<?= e(setting('email')) ?>"><span>Email</span><span class="mono"><?= e(setting('email')) ?></span></a><?php endif; ?>
        </div>
      </div>
      <div id="message">
        <?php if ($sent): ?>
          <div class="notice notice--ok" role="status"><h2>Your message is in.</h2><p>We reply on the phone number you gave, during opening hours.</p></div>
        <?php else: ?>
        <div class="slip">
          <div class="slip__head"><span class="label">Send a message</span></div>
          <div class="slip__body">
          <form class="form" method="post" action="<?= e(url('contact.php')) ?>#message" novalidate>
            <?= csrf_field() ?><?= honeypot() ?>
            <div class="form__grid">
              <?= field('name', 'Your name', 'text', $state, ['required' => 1, 'autocomplete' => 'name', 'maxlength' => 120, 'error' => 'Write your name.']) ?>
              <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write a phone number we can call, like 0803 123 4567.']) ?>
              <?= field('email', 'Email (optional)', 'email', $state, ['full' => 1, 'autocomplete' => 'email', 'inputmode' => 'email', 'maxlength' => 190, 'error' => 'Write an email address with an @, or leave it empty.']) ?>
              <?= field('message', 'Your message', 'textarea', $state, ['required' => 1, 'full' => 1, 'maxlength' => 2000, 'error' => 'Write your message in a few words.']) ?>
            </div>
            <div class="form__foot"><button class="btn btn--primary" type="submit">Send the message</button></div>
            <?= form_status($state) ?>
          </form>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php
$close_h = 'Rather come in?';
$close_p = 'Walk in during opening hours, or book a time. ' . hours_line() . '.';
include __DIR__ . '/includes/footer.php';
