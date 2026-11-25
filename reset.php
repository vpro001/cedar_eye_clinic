<?php
/**
 * Where a reset link from an email leads: choose a new password. Works with the token in the URL or posted back.
 */
require_once __DIR__ . '/includes/functions.php';

$token   = clip(get('token'), 100);
$state   = ['values' => ['password' => '', 'password2' => ''], 'errors' => []];
$changed = false;

if (is_post()) {
    $token = clip(post('token'), 100);
    $state['values'] = [
        'password'  => (string) ($_POST['password'] ?? ''),
        'password2' => (string) ($_POST['password2'] ?? ''),
    ];
    if (!csrf_ok()) {
        $state['errors']['form'] = 'The form expired. Reload the page and try again.';
    } elseif (throttled('login')) {
        $state['errors']['form'] = 'Too many requests. Wait ten minutes and try again.';
    } elseif (password_reset_finish($token, $state['values']['password'], $state['values']['password2'], $state['errors'])) {
        $changed = true;
    }
    if (!$changed) {
        // the token in the page they were sent to also works, in case the form was resubmitted
    }
}

$page_title       = 'Choose a new Cedar Eye Clinic password';
$page_description = 'A new password for your eye test account. The link works for an hour.';
$canonical        = 'reset.php';
$noindex          = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="page-head__inner">
        <h1 id="page-h" class="rise">Choose a new password</h1>
        <p class="lead rise rise-2"><?= $changed ? 'Your password is now set. You can <a class="link" href="' . e(url('login.php')) . '">sign in</a>.' : (empty($state['errors']) ? 'Enter two new passwords. They need ten characters or more.' : '') ?></p>
      </div>
      <?php if (!$changed): ?>
      <div class="slip"><div class="slip__body">
        <form class="form" method="post" action="<?= e(url('reset.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <?= honeypot() ?>
          <div class="form__grid">
            <?= field('password', 'New password', 'password', $state, ['required' => 1, 'autocomplete' => 'new-password', 'error' => 'Use at least 10 characters.']) ?>
            <?= field('password2', 'Password again', 'password', $state, ['required' => 1, 'autocomplete' => 'new-password', 'error' => 'The two passwords are not the same.']) ?>
          </div>
          <div class="form__foot"><button class="btn btn--primary" type="submit">Set the new password</button></div>
          <p class="form__note">No luck? Ask again from <a class="link" href="<?= e(url('forgot.php')) ?>">Reset your password</a>.</p>
          <?= form_status($state) ?>
        </form>
      </div></div>
      <?php endif; ?>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
