<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

$id    = (int) get('id');
$isNew = get('new') === '1' && !$id;
$a     = $id ? row('SELECT * FROM appointments WHERE id = ?', 'i', [$id]) : null;
if (!$a && !$isNew) {
    flash('error', 'That booking no longer exists.');
    redirect('admin/appointments.php');
}
$services = services_list(false);
$errors   = [];

if (is_post()) {
    admin_post_guard();

    if (post('action') === 'delete' && $a) {
        q('DELETE FROM appointments WHERE id = ?', 'i', [$a['id']]);
        flash('ok', 'Booking ' . $a['ref'] . ' was deleted.');
        redirect('admin/appointments.php');
    }

    $v = [
        'name'           => clip(post('name'), 120),
        'phone'          => clip(post('phone'), 40),
        'service_id'     => (int) post('service_id'),
        'preferred_date' => post('preferred_date'),
        'preferred_time' => post('preferred_time'),
        'slot_at'        => post('slot_at'),
        'status'         => post('status', 'pending'),
        'notes'          => clip(post('notes'), 1000),
        'admin_note'     => clip(post('admin_note'), 2000),
    ];
    if ($v['name'] === '') $errors['name'] = 'Write the patient\'s name.';
    if (!valid_phone($v['phone'])) $errors['phone'] = 'Write a phone number with at least 10 digits.';
    $svc = null;
    foreach ($services as $s) { if ((int) $s['id'] === $v['service_id']) $svc = $s; }
    if (!$svc) $errors['service_id'] = 'Choose a service.';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v['preferred_date'])) $errors['preferred_date'] = 'Choose a day.';
    if (!isset(TIME_SLOTS[$v['preferred_time']])) $errors['preferred_time'] = 'Choose a time of day.';
    if (!isset(APPT_STATUSES[$v['status']])) $errors['status'] = 'Choose a status.';
    $slot = null;
    if ($v['slot_at'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $v['slot_at']);
        if (!$dt) $errors['slot_at'] = 'Write the date and time, or leave it empty.';
        else $slot = $dt->format('Y-m-d H:i:00');
    }
    // confirming means a time is set
    if ($v['status'] === 'confirmed' && !$slot) $errors['slot_at'] = 'Set the confirmed time before marking the booking confirmed.';
    // a time set on a waiting booking confirms it
    if ($slot && $v['status'] === 'pending') $v['status'] = 'confirmed';

    if (!$errors) {
        if ($isNew) {
            $ref = new_ref();
            q('INSERT INTO appointments (ref, name, phone, service_id, service_name, preferred_date, preferred_time, slot_at, status, notes, admin_note, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'sssissssssss', [$ref, $v['name'], $v['phone'], $svc['id'], $svc['name'], $v['preferred_date'], $v['preferred_time'], $slot, $v['status'], $v['notes'], $v['admin_note'], 'staff:' . $me['id']]);
            flash('ok', 'Booking ' . $ref . ' was added.');
            redirect('admin/appointment.php?id=' . last_id());
        }
        q('UPDATE appointments SET name = ?, phone = ?, service_id = ?, service_name = ?, preferred_date = ?, preferred_time = ?, slot_at = ?, status = ?, notes = ?, admin_note = ? WHERE id = ?',
            'ssisssssssi', [$v['name'], $v['phone'], $svc['id'], $svc['name'], $v['preferred_date'], $v['preferred_time'], $slot, $v['status'], $v['notes'], $v['admin_note'], $a['id']]);
        flash('ok', 'Booking ' . $a['ref'] . ' was saved.');
        redirect('admin/appointment.php?id=' . $a['id']);
    }
    $a = array_merge($a ?? [], $v, ['slot_at' => $slot]);
}

$a = $a ?? ['ref' => '', 'name' => '', 'phone' => '', 'service_id' => 0, 'service_name' => '', 'preferred_date' => date('Y-m-d'), 'preferred_time' => 'morning', 'slot_at' => null, 'status' => 'pending', 'notes' => '', 'admin_note' => '', 'created_at' => null, 'ip' => ''];

// a ready-made WhatsApp confirmation to the patient
$waNum = preg_replace('/\D/', '', $a['phone']);
if (str_starts_with($waNum, '0')) $waNum = '234' . substr($waNum, 1);
$waText = $a['slot_at']
    ? 'Hello ' . strtok($a['name'], ' ') . ', your ' . strtolower($a['service_name']) . ' at Cedar Eye Clinic is confirmed for ' . date('l j F \a\t H:i', strtotime($a['slot_at'])) . '. ' . setting('street') . ', ' . setting('district') . ', inside ' . setting('building') . '. Reference ' . $a['ref'] . '.'
    : 'Hello ' . strtok($a['name'], ' ') . ', this is Cedar Eye Clinic about your booking ' . $a['ref'] . '.';

function err(array $errors, string $k): string
{
    return isset($errors[$k]) ? '<p class="mt-1.5 text-sm text-red-700">' . e($errors[$k]) . '</p>' : '';
}

$admin_title = $isNew ? 'Add a booking' : 'Booking ' . $a['ref'];
$admin_nav   = 'appointments';
require __DIR__ . '/_header.php';
?>
<p class="mb-4"><a href="<?= e(url('admin/appointments.php')) ?>" class="text-sm text-ink-muted hover:text-ink">← Appointments</a></p>
<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
  <div>
    <h1 class="text-4xl tracking-tight"><?= $isNew ? 'Add a booking' : e($a['name']) ?></h1>
    <?php if (!$isNew): ?><p class="text-ink-muted mt-2 flex flex-wrap items-center gap-3"><span class="pill s-<?= e($a['status']) ?>"><?= e(APPT_STATUSES[$a['status']]) ?></span><span><?= e($a['ref']) ?></span><span>Received <?= e(date('j M Y, H:i', strtotime($a['created_at']))) ?><?= str_starts_with((string) $a['ip'], 'staff:') ? ' (added by staff)' : ' (website)' ?></span></p><?php endif; ?>
  </div>
  <?php if (!$isNew): ?>
  <div class="flex flex-wrap gap-2">
    <a class="btn btn-ghost" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $a['phone'])) ?>">Call <?= e($a['phone']) ?></a>
    <a class="btn btn-ghost" href="https://wa.me/<?= e($waNum) ?>?text=<?= e(rawurlencode($waText)) ?>" target="_blank" rel="noopener"><?= $a['slot_at'] ? 'Send confirmation on WhatsApp' : 'Message on WhatsApp' ?></a>
  </div>
  <?php endif; ?>
</header>

<form method="post" class="grid xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-8 items-start" novalidate>
  <?= csrf_field() ?>
  <div class="rounded-md bg-white border border-line p-6 lg:p-8 grid sm:grid-cols-2 gap-5">
    <h2 class="sm:col-span-2 text-lg font-medium">Patient and request</h2>
    <div><label class="lbl" for="name">Name</label><input class="inp" id="name" name="name" value="<?= e($a['name']) ?>" required><?= err($errors, 'name') ?></div>
    <div><label class="lbl" for="phone">Phone</label><input class="inp" id="phone" name="phone" type="tel" value="<?= e($a['phone']) ?>" required><?= err($errors, 'phone') ?></div>
    <div class="sm:col-span-2"><label class="lbl" for="service_id">Service</label>
      <select class="inp" id="service_id" name="service_id"><option value="">Choose a service</option>
        <?php foreach ($services as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $s['id'] === (int) $a['service_id'] ? ' selected' : '' ?>><?= e($s['name']) ?><?= $s['active'] ? '' : ' (hidden on site)' ?></option><?php endforeach; ?>
      </select><?= err($errors, 'service_id') ?></div>
    <div><label class="lbl" for="preferred_date">Day asked for</label><input class="inp" id="preferred_date" name="preferred_date" type="date" value="<?= e($a['preferred_date']) ?>"><?= err($errors, 'preferred_date') ?></div>
    <div><label class="lbl" for="preferred_time">Time of day asked for</label><select class="inp" id="preferred_time" name="preferred_time"><?php foreach (TIME_SLOTS as $k => $l): ?><option value="<?= e($k) ?>"<?= $a['preferred_time'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><?= err($errors, 'preferred_time') ?></div>
    <div class="sm:col-span-2"><label class="lbl" for="notes">Patient's note</label><textarea class="inp" id="notes" name="notes"><?= e($a['notes']) ?></textarea></div>
  </div>

  <div class="rounded-md bg-white border border-line p-6 lg:p-8 grid gap-5 xl:sticky xl:top-8" id="confirm">
    <h2 class="text-lg font-medium">Confirm and track</h2>
    <div><label class="lbl" for="slot_at">Confirmed time</label><input class="inp" id="slot_at" name="slot_at" type="datetime-local" step="900" value="<?= $a['slot_at'] ? e(date('Y-m-d\TH:i', strtotime($a['slot_at']))) : '' ?>"><?= err($errors, 'slot_at') ?><p class="mt-1.5 text-sm text-ink-muted">Setting a time on a waiting booking confirms it.</p></div>
    <div><label class="lbl" for="status">Status</label><select class="inp" id="status" name="status"><?php foreach (APPT_STATUSES as $k => $l): ?><option value="<?= e($k) ?>"<?= $a['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><?= err($errors, 'status') ?></div>
    <div><label class="lbl" for="admin_note">Staff note</label><textarea class="inp" id="admin_note" name="admin_note" placeholder="Only staff see this"><?= e($a['admin_note']) ?></textarea></div>
    <div class="flex flex-wrap gap-2 justify-between">
      <button class="btn btn-primary" name="action" value="save"><?= $isNew ? 'Add the booking' : 'Save changes' ?></button>
      <?php if (!$isNew): ?><button class="btn btn-danger" name="action" value="delete" data-confirm="Delete booking <?= e($a['ref']) ?> for good?">Delete</button><?php endif; ?>
    </div>
  </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
