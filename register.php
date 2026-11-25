<?php
/**
 * Create a patient account, so bookings collect with the patient.
 */
require_once __DIR__ . '/includes/functions.php';

if (current_patient()) {
    redirect('account.php');
}

$state = ['values' => ['name' => '', 'phone' => '', 'email' => '', 'password' => '', 'password2' => ''], 'errors' => []];
if (is_post()) {
    $state['values'] = [
        'name'  => clip(post('name'), 120),
        'phone' => clip(post('phone'), 40),
        'email' => clip(post('email'), 190),
    ];
    if (!csrf_ok()) {
        $state['errors']['form'] = 'The form expired. Reload the page and try again.';
    } elseif (throttled('login')) {
        $state['errors']['form'] = 'Too many attempts. Wait ten minutes and try again.';
    } else {
        $newPatient = null;
        if (patient_register(['name' => $state['values']['name'], 'phone' => $state['values']['phone'],
                'email' => $state['values']['email'], 'password' => post('password'), 'password2' => post('password2')],
                $state['errors'], $newPatient)) {
            patient_login($newPatient);
            throttle_hit('login');
            redirect('account.php');
        }
    }
}

$page_title       = 'Create an account with Cedar Eye Clinic';
$page_description = 'Sign up to book an eye test and keep your bookings together.';
$canonical        = 'register.php';
$noindex          = true;
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap split">
      <div class="stack-lg">
        <div class="page-head__inner">
          <h1 id="page-h" class="rise">Create an account</h1>
          <p class="lead rise rise-2">One account for your eye test bookings, at Cedar Eye Clinic.</p>
        </div>
        <?php
        // success always redirects above; if we reach here there are errors to fix
        ?>
        <div class="slip"><div class="slip__body">
          <form class="form" method="post" action="<?= e(url('register.php')) ?>" novalidate>
            <?= csrf_field() ?><?= honeypot() ?>
            <div class="form__grid">
              <?= field('name', 'Your name', 'text', $state, ['required' => 1, 'autocomplete' => 'name', 'maxlength' => 120]) ?>
              <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write a phone number we can call, like 0803 123 4567.']) ?>
              <?= field('email', 'Email address', 'email', $state, ['required' => 1, 'autocomplete' => 'username', 'maxlength' => 190]) ?>
              <?= field('password', 'Password', 'password', $state, ['required' => 1, 'autocomplete' => 'new-password', 'error' => 'Use at least 10 characters.']) ?>
              <?= field('password2', 'Password again', 'password', $state, ['required' => 1, 'autocomplete' => 'new-password', 'error' => 'The two passwords are not the same.']) ?>
            </div>
            <div class="form__foot"><button class="btn btn--primary" type="submit">Create account</button></div>
            <p class="form__note">Already have an account? <a class="link" href="<?= e(url('login.php')) ?>">Sign in</a>.</p>
            <?= form_status($state) ?>
          </form>
        </div></div>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
