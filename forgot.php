<?php
/**
 * Ask for a password reset: emails a link that works for an hour. The answer is the same whether or
 * not the address has an account, so nothing is revealed about who is on file.
 */
require_once __DIR__ . '/includes/functions.php';

$state = ['values' => ['email' => (string) get('email', '')], 'errors' => []];
$done  = false;
if (is_post()) {
    $state['values']['email'] = clip(post('email'), 190);
    if (!csrf_ok()) {
        $state['errors']['form'] = 'The form expired. Reload the page and try again.';
    } elseif (throttled('login')) {
        $state['errors']['form'] = 'Too many requests. Wait ten minutes and try again.';
    } else {
        password_reset_start($state['values']['email']);
        $done = true;
        $state['values']['email'] = '';
    }
}

$page_title       = 'Reset your Cedar Eye Clinic password';
$page_description = 'Ask for a link to a new password, sent to the email on your account.';
$canonical        = 'forgot.php';
$noindex          = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="page-head__inner">
        <h1 id="page-h" class="rise">Reset your password</h1>
        <p class="lead rise rise-2"><?= $done ? 'We have sent you an email with a link to choose a new password.' : 'Enter the email on your account. We send a link to it (when it exists) that works for one hour.' ?></p>
      </div>
      <?php if (!$done): ?>
      <div class="slip"><div class="slip__body">
        <form class="form" method="post" action="<?= e(url('forgot.php')) ?>" novalidate>
          <?= csrf_field() ?><?= honeypot() ?>
          <div class="form__grid">
            <?= field('email', 'Email address', 'email', $state, ['required' => 1, 'autocomplete' => 'username', 'error' => 'Write an email address with an @.']) ?>
          </div>
          <div class="form__foot"><button class="btn btn--primary" type="submit">Send the reset link</button></div>
          <p class="form__note">Forgot it? You can <a class="link" href="<?= e(url('login.php')) ?>">sign in</a> again when it comes back.</p>
          <?= form_status($state) ?>
        </form>
      </div></div>
      <?php endif; ?>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
