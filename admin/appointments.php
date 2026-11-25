<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

// quick status change from the list
if (is_post()) {
    admin_post_guard();
    $id = (int) post('id');
    $st = post('status');
    if ($id && isset(APPT_STATUSES[$st])) {
        q('UPDATE appointments SET status = ? WHERE id = ?', 'si', [$st, $id]);
        flash('ok', 'Booking ' . val('SELECT ref FROM appointments WHERE id = ?', 'i', [$id]) . ' is now: ' . APPT_STATUSES[$st] . '.');
    }
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? url('admin/appointments.php')), true, 303);
    exit;
}

// filters
$status = get('status');
$date   = get('date');
$range  = get('range');
$search = clip(get('q'), 80);
$page   = max(1, (int) get('page', '1'));
$per    = 25;

$where = [];
$types = '';
$args  = [];
if (isset(APPT_STATUSES[$status])) { $where[] = 'status = ?'; $types .= 's'; $args[] = $status; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $where[] = 'COALESCE(DATE(slot_at), preferred_date) = ?'; $types .= 's'; $args[] = $date; }
if ($range === 'week') { $where[] = 'COALESCE(DATE(slot_at), preferred_date) BETWEEN CURDATE() AND CURDATE() + INTERVAL 6 DAY'; }
if ($range === 'past') { $where[] = 'COALESCE(DATE(slot_at), preferred_date) < CURDATE()'; }
if ($search !== '') {
    $where[] = '(name LIKE ? OR phone LIKE ? OR ref LIKE ? OR service_name LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ssss';
    array_push($args, $like, $like, $like, $like);
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// CSV export of the filtered list
if (get('export') === 'csv') {
    $all = rows("SELECT ref, name, phone, service_name, preferred_date, preferred_time, slot_at, status, notes, admin_note, created_at FROM appointments $sqlWhere ORDER BY created_at DESC", $types, $args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cedar-appointments-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Reference', 'Name', 'Phone', 'Service', 'Preferred day', 'Time of day', 'Confirmed time', 'Status', 'Patient note', 'Staff note', 'Received']);
    foreach ($all as $r) {
        // guard against spreadsheet formula injection
        $r = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $r);
        $r['status'] = APPT_STATUSES[$r['status']] ?? $r['status'];
        fputcsv($out, array_values($r));
    }
    exit;
}

$total = (int) val("SELECT COUNT(*) FROM appointments $sqlWhere", $types, $args);
$pages = max(1, (int) ceil($total / $per));
$page  = min($page, $pages);
$off   = ($page - 1) * $per;
$list  = rows("SELECT * FROM appointments $sqlWhere ORDER BY (status = 'pending') DESC, COALESCE(slot_at, preferred_date) DESC, id DESC LIMIT $per OFFSET $off", $types, $args);
$counts = array_column(rows('SELECT status, COUNT(*) n FROM appointments GROUP BY status'), 'n', 'status');

function qs(array $change): string
{
    $q = array_merge($_GET, $change);
    unset($q['export']);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return url('admin/appointments.php' . ($q ? '?' . http_build_query($q) : ''));
}

$admin_title = 'Appointments';
$admin_nav   = 'appointments';
require __DIR__ . '/_header.php';
?>
<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
  <div><h1 class="text-4xl tracking-tight">Appointments</h1><p class="text-ink-muted mt-1"><?= $total ?> <?= $total === 1 ? 'booking' : 'bookings' ?><?= $date ? ' on ' . e(date('l j F', strtotime($date))) : '' ?></p></div>
  <div class="flex gap-2">
    <?php $csv = qs(['page' => '']); ?><a class="btn btn-ghost" href="<?= e($csv . (str_contains($csv, '?') ? '&' : '?') . 'export=csv') ?>">Download CSV</a>
    <a class="btn btn-primary" href="<?= e(url('admin/appointment.php?new=1')) ?>">Add a booking</a>
  </div>
</header>

<nav class="flex flex-wrap gap-2 mb-5" aria-label="Status">
  <a href="<?= e(qs(['status' => '', 'page' => ''])) ?>" class="btn <?= $status === '' ? 'bg-ink text-white' : 'btn-ghost' ?> h-9">All <span class="opacity-60"><?= array_sum($counts) ?></span></a>
  <?php foreach (APPT_STATUSES as $k => $l): ?>
  <a href="<?= e(qs(['status' => $k, 'page' => ''])) ?>" class="btn <?= $status === $k ? 'bg-ink text-white' : 'btn-ghost' ?> h-9"><?= e($l) ?> <span class="opacity-60"><?= (int) ($counts[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>

<form class="flex flex-wrap gap-3 mb-6 items-end" method="get">
  <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
  <div class="flex-1 min-w-[220px]"><label for="q" class="lbl">Search</label><input id="q" name="q" value="<?= e($search) ?>" class="inp" placeholder="Name, phone or reference"></div>
  <div><label for="date" class="lbl">Day</label><input id="date" name="date" type="date" value="<?= e($date) ?>" class="inp"></div>
  <div><label for="range" class="lbl">When</label><select id="range" name="range" class="inp"><option value="">Any time</option><option value="week"<?= $range === 'week' ? ' selected' : '' ?>>Next 7 days</option><option value="past"<?= $range === 'past' ? ' selected' : '' ?>>Past</option></select></div>
  <button class="btn btn-primary h-11">Filter</button>
  <?php if ($search || $date || $range): ?><a class="btn btn-ghost h-11" href="<?= e(qs(['q' => '', 'date' => '', 'range' => '', 'page' => ''])) ?>">Clear</a><?php endif; ?>
</form>

<div class="rounded-md bg-white border border-line overflow-hidden">
  <?php if (!$list): ?>
    <p class="p-8 text-ink-muted">No bookings match. Website bookings appear here as soon as they are sent.</p>
  <?php else: ?>
  <div class="overflow-x-auto">
  <table class="w-full text-left text-[15px]">
    <thead class="bg-paper text-sm text-ink-muted"><tr>
      <th class="px-5 py-3 font-medium">Patient</th><th class="px-5 py-3 font-medium">Service</th><th class="px-5 py-3 font-medium">When</th><th class="px-5 py-3 font-medium">Status</th><th class="px-5 py-3 font-medium sr-only">Actions</th>
    </tr></thead>
    <tbody class="divide-y divide-line">
      <?php foreach ($list as $a): $past = ($a['slot_at'] ? substr($a['slot_at'], 0, 10) : $a['preferred_date']) < date('Y-m-d'); ?>
      <tr class="row-link align-top">
        <td class="px-5 py-4"><a href="<?= e(url('admin/appointment.php?id=' . $a['id'])) ?>" class="font-medium hover:text-sea"><?= e($a['name']) ?></a><div class="text-sm text-ink-muted"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $a['phone'])) ?>" class="hover:text-ink"><?= e($a['phone']) ?></a> · <?= e($a['ref']) ?></div></td>
        <td class="px-5 py-4 max-w-[260px]"><?= e($a['service_name']) ?><?php if ($a['notes']): ?><div class="text-sm text-ink-muted truncate" title="<?= e($a['notes']) ?>">“<?= e($a['notes']) ?>”</div><?php endif; ?></td>
        <td class="px-5 py-4 whitespace-nowrap <?= $past ? 'text-ink-muted' : '' ?>">
          <?php if ($a['slot_at']): ?><?= e(date('D j M, H:i', strtotime($a['slot_at']))) ?><?php else: ?><?= e(date('D j M', strtotime($a['preferred_date']))) ?><div class="text-sm text-ink-muted"><?= e(ucfirst($a['preferred_time'])) ?> (asked)</div><?php endif; ?>
        </td>
        <td class="px-5 py-4"><span class="pill s-<?= e($a['status']) ?>"><?= e(APPT_STATUSES[$a['status']]) ?></span></td>
        <td class="px-5 py-4">
          <form method="post" class="flex gap-2 justify-end">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <?php if ($a['status'] === 'pending'): ?><a class="btn btn-primary h-9" href="<?= e(url('admin/appointment.php?id=' . $a['id'])) ?>#confirm">Confirm</a><?php endif; ?>
            <?php if ($a['status'] === 'confirmed'): ?><button class="btn btn-ghost h-9" name="status" value="completed">Mark seen</button><?php endif; ?>
            <?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?><button class="btn btn-ghost h-9" name="status" value="cancelled" data-confirm="Cancel booking <?= e($a['ref']) ?>?">Cancel</button><?php endif; ?>
            <a class="btn btn-ghost h-9" href="<?= e(url('admin/appointment.php?id=' . $a['id'])) ?>">Open</a>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
<nav class="flex items-center justify-between mt-6" aria-label="Pages">
  <span class="text-sm text-ink-muted">Page <?= $page ?> of <?= $pages ?></span>
  <span class="flex gap-2">
    <?php if ($page > 1): ?><a class="btn btn-ghost" href="<?= e(qs(['page' => $page - 1])) ?>">← Newer</a><?php endif; ?>
    <?php if ($page < $pages): ?><a class="btn btn-ghost" href="<?= e(qs(['page' => $page + 1])) ?>">Older →</a><?php endif; ?>
  </span>
</nav>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
