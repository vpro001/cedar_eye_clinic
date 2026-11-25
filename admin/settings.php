<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

$fields = [
    'building' => ['Building', 'The building the clinic is inside.'],
    'street'   => ['Street', 'Street name only, no plot number: it is printed large on every page.'],
    'district' => ['District', ''],
    'city'     => ['City', ''],
    'phone'    => ['Phone, as printed', 'For example 0806 709 2463.'],
    'whatsapp' => ['WhatsApp number', 'International form, digits only: 2348067092463.'],
    'email'    => ['Email', 'Shown on the contact page and in the footer.'],
    'notify_email' => ['Send new bookings and messages to', 'Each booking and message from the website is emailed here as it arrives. Leave empty to use the email above.'],
];
$errors = [];
$hours  = hours();

if (is_post()) {
    admin_post_guard();
    $vals = [];
    foreach ($fields as $k => $_) {
        $vals[$k] = clip(post($k), 190);
    }
    if ($vals['street'] === '') $errors['street'] = 'Write the street.';
    if (!valid_phone($vals['phone'])) $errors['phone'] = 'Write a phone number with at least 10 digits.';
    if (!preg_match('/^\d{10,15}$/', $vals['whatsapp'])) $errors['whatsapp'] = 'Digits only, with the country code: 2348067092463.';
    if ($vals['email'] !== '' && !valid_email($vals['email'])) $errors['email'] = 'Write an email address with an @.';
    if ($vals['notify_email'] !== '' && !valid_email($vals['notify_email'])) $errors['notify_email'] = 'Write an email address with an @, or leave it empty.';

    $newHours = [];
    foreach ($hours as $i => $h) {
        $closed = isset($_POST['closed'][$i]);
        $open   = trim((string) ($_POST['open'][$i] ?? ''));
        $close  = trim((string) ($_POST['close'][$i] ?? ''));
        if (!$closed && (!preg_match('/^\d{1,2}:\d{2}$/', $open) || !preg_match('/^\d{1,2}:\d{2}$/', $close))) {
            $errors['hours'] = 'Write times like 9:00 and 17:00, or tick Closed.';
        }
        $newHours[] = ['label' => $h['label'], 'days' => $h['days'], 'open' => $closed ? '' : ltrim($open, '0'), 'close' => $closed ? '' : $close];
    }
    if (!$errors) {
        foreach ($vals as $k => $v) save_setting($k, $v);
        save_setting('hours_json', json_encode($newHours));
        flash('ok', 'Clinic details saved. The website shows them now.');
        redirect('admin/settings.php');
    }
    $hours = $newHours;
}
$cur = fn($k) => is_post() ? post($k) : setting($k);
$err = fn($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-sm text-red-700">' . e($errors[$k]) . '</p>' : '';

$admin_title = 'Clinic details';
$admin_nav   = 'settings';
require __DIR__ . '/_header.php';
?>
<header class="mb-8"><h1 class="text-4xl tracking-tight">Clinic details</h1><p class="text-ink-muted mt-1">The address, phone, WhatsApp and opening hours shown across the website.</p></header>
<form method="post" class="grid xl:grid-cols-2 gap-8 items-start" novalidate>
  <?= csrf_field() ?>
  <section class="rounded-md bg-white border border-line p-6 lg:p-8 grid sm:grid-cols-2 gap-5" aria-labelledby="where-h">
    <h2 id="where-h" class="sm:col-span-2 text-lg font-medium">Where and how to reach you</h2>
    <?php foreach ($fields as $k => [$label, $hint]): ?>
    <div class="<?= in_array($k, ['building', 'street', 'email', 'notify_email'], true) ? 'sm:col-span-2' : '' ?>">
      <label class="lbl" for="<?= $k ?>"><?= e($label) ?></label>
      <input class="inp" id="<?= $k ?>" name="<?= $k ?>" value="<?= e($cur($k)) ?>"<?= in_array($k, ['email', 'notify_email'], true) ? ' type="email"' : '' ?>>
      <?php if ($hint): ?><p class="mt-1.5 text-sm text-ink-muted"><?= e($hint) ?></p><?php endif; ?>
      <?= $err($k) ?>
    </div>
    <?php endforeach; ?>
  </section>
  <section class="rounded-md bg-white border border-line p-6 lg:p-8 grid gap-5" aria-labelledby="hours-h">
    <h2 id="hours-h" class="text-lg font-medium">Opening hours</h2>
    <?php foreach ($hours as $i => $h): $closed = empty($h['open']); ?>
    <div class="grid grid-cols-[110px_1fr_1fr_auto] gap-3 items-end">
      <span class="h-11 flex items-center font-medium"><?= e($h['label']) ?></span>
      <div><label class="lbl" for="open<?= $i ?>">Opens</label><input class="inp" id="open<?= $i ?>" name="open[<?= $i ?>]" value="<?= e($h['open']) ?>" placeholder="9:00"></div>
      <div><label class="lbl" for="close<?= $i ?>">Closes</label><input class="inp" id="close<?= $i ?>" name="close[<?= $i ?>]" value="<?= e($h['close']) ?>" placeholder="17:00"></div>
      <label class="h-11 flex items-center gap-2"><input type="checkbox" name="closed[<?= $i ?>]" class="w-5 h-5 accent-[#2c7469]"<?= $closed ? ' checked' : '' ?>> Closed</label>
    </div>
    <?php endforeach; ?>
    <?= $err('hours') ?>
    <p class="text-sm text-ink-muted">The website marks today's row in Lagos time.</p>
  </section>
  <div class="xl:col-span-2"><button class="btn btn-primary">Save clinic details</button></div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
