<?php
/**
 * Sign in for patients. Redirects to account.php, or back to the page they came from when it can be.
 */
require_once __DIR__ . '/includes/functions.php';

if (current_patient()) {
    redirect('account.php');
}

$state  = ['values' => ['email' => ''], 'errors' => []];
$notice = '';
if (is_post()) {
    $state['values']['email'] = clip(post('email'), 190);
    $pass = (string) ($_POST['password'] ?? '');
    if (!csrf_ok()) {
        $notice = 'The form expired. Reload the page and try again.';
    } elseif (throttled('login')) {
        $notice = 'Too many attempts. Wait ten minutes and try again.';
    } else {
        $error = null;
        $p = patient_authenticate($state['values']['email'], $pass, $error);
        if ($p) {
            patient_login($p);
            $to = get('next');
            if ($to !== '' && str_starts_with($to, url('')) && !str_contains($to, '//')) {
                redirect($to);
            }
            redirect(patient_home());
        }
        throttle_hit('login');
        $notice = $error;
    }
}

$page_title       = 'Sign in to your Cedar Eye Clinic account';
$page_description = 'Sign in to see and manage the eye test bookings you have made.';
$canonical        = 'login.php';
$noindex          = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="stack-lg">
        <div class="page-head__inner">
          <h1 id="page-h" class="rise">Sign in</h1>
          <p class="lead rise rise-2">Your bookings live here: sign in with the email you booked with.</p>
        </div>
        <div class="slip">
          <div class="slip__head"><span class="rx" aria-hidden="true">Rx</span><span class="label">Your account</span></div>
          <div class="slip__body">
            <form class="form" method="post" action="<?= e(url('login.php')) ?>" novalidate>
              <?= csrf_field() ?><?= honeypot() ?>
              <div class="form__grid">
                <?= field('email', 'Email address', 'email', $state, ['required' => 1, 'autocomplete' => 'username', 'error' => 'Write the email you booked with.']) ?>
                <?= field('password', 'Password', 'password', $state, ['required' => 1, 'autocomplete' => 'current-password', 'error' => 'Write your password.']) ?>
              </div>
              <div class="form__foot">
                <button class="btn btn--primary" type="submit">Sign in</button>
                <p class="form__note">Or <a class="link" href="<?= e(url('register.php')) ?>">create an account</a>. Forgot your password? <a class="link" href="<?= e(url('forgot.php')) ?>">Reset it</a>.</p>
              </div>
              <?php if ($notice): ?><p role="alert" class="notice notice--err" style="margin-top: -8px"><?= e($notice) ?></p><?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
