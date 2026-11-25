<?php
$admin_title = 'Dashboard';
$admin_nav   = 'dashboard';
require __DIR__ . '/_header.php';

$today    = date('Y-m-d');
$pending  = (int) val("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$todayN   = (int) val("SELECT COUNT(*) FROM appointments WHERE status IN ('pending','confirmed') AND COALESCE(DATE(slot_at), preferred_date) = ?", 's', [$today]);
$weekN    = (int) val("SELECT COUNT(*) FROM appointments WHERE status IN ('pending','confirmed') AND COALESCE(DATE(slot_at), preferred_date) BETWEEN ? AND ?", 'ss', [$today, date('Y-m-d', strtotime('+6 days'))]);
$newMsg   = (int) val("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
$seen30   = (int) val("SELECT COUNT(*) FROM appointments WHERE status = 'completed' AND updated_at >= NOW() - INTERVAL 30 DAY");

$upcoming = rows("SELECT * FROM appointments WHERE status IN ('pending','confirmed') AND COALESCE(DATE(slot_at), preferred_date) >= ? ORDER BY COALESCE(slot_at, preferred_date), FIELD(preferred_time,'morning','midday','afternoon') LIMIT 8", 's', [$today]);
$messages = rows('SELECT * FROM enquiries ORDER BY created_at DESC LIMIT 5');

// bookings per day for the next 14 days, for the strip
$load = array_column(rows("SELECT COALESCE(DATE(slot_at), preferred_date) d, COUNT(*) n FROM appointments WHERE status IN ('pending','confirmed') AND COALESCE(DATE(slot_at), preferred_date) BETWEEN ? AND ? GROUP BY d", 'ss', [$today, date('Y-m-d', strtotime('+13 days'))]), 'n', 'd');
$maxLoad = max([1] + array_values($load));

$hour = (int) date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<header class="mb-10 flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="text-ink-muted"><?= e(date('l j F Y')) ?></p>
    <h1 class="text-4xl tracking-tight mt-1"><?= e($greet) ?>, <?= e(strtok($me['name'], ' ')) ?></h1>
  </div>
  <a href="<?= e(url('admin/appointments.php?status=pending')) ?>" class="btn btn-primary">Confirm waiting bookings<?= $pending ? ' (' . $pending . ')' : '' ?></a>
</header>

<section class="grid grid-cols-2 xl:grid-cols-5 gap-4 mb-10" aria-label="Today in numbers">
  <?php foreach ([
      ['Waiting to confirm', $pending, 'admin/appointments.php?status=pending', $pending > 0],
      ['Booked for today', $todayN, 'admin/appointments.php?date=' . $today, false],
      ['Next 7 days', $weekN, 'admin/appointments.php?range=week', false],
      ['New messages', $newMsg, 'admin/enquiries.php?status=new', $newMsg > 0],
      ['Seen in 30 days', $seen30, 'admin/appointments.php?status=completed', false],
  ] as [$label, $n, $href, $hot]): ?>
  <a href="<?= e(url($href)) ?>" class="group rounded-md bg-white border border-line p-5 hover:-translate-y-0.5 hover:border-ink/30 transition">
    <p class="text-sm text-ink-muted flex items-center gap-2"><?php if ($hot): ?><span class="w-2 h-2 rounded-full bg-amber-500"></span><?php endif; ?><?= e($label) ?></p>
    <p class="text-4xl tracking-tight mt-3"><?= $n ?></p>
  </a>
  <?php endforeach; ?>
</section>

<section class="rounded-md bg-white border border-line p-6 mb-10" aria-labelledby="load-h">
  <div class="flex items-baseline justify-between mb-5"><h2 id="load-h" class="text-lg font-medium">Bookings, next 14 days</h2><span class="text-sm text-ink-muted">waiting and confirmed</span></div>
  <div class="grid grid-cols-7 md:grid-cols-14 gap-2" style="grid-template-columns: repeat(14, minmax(0, 1fr))">
    <?php for ($i = 0; $i < 14; $i++): $d = date('Y-m-d', strtotime("+$i days")); $n = (int) ($load[$d] ?? 0); $sun = date('N', strtotime($d)) === '7'; ?>
    <a href="<?= e(url('admin/appointments.php?date=' . $d)) ?>" class="flex flex-col items-center gap-2 rounded-lg py-2 hover:bg-paper" title="<?= e(date('D j M', strtotime($d)) . ': ' . $n) ?>">
      <span class="h-20 w-full flex items-end justify-center"><span class="w-3/5 max-w-[28px] rounded-md <?= $sun ? 'bg-paper-2' : ($n ? 'bg-sea' : 'bg-paper-2') ?>" style="height: <?= $sun ? 6 : max(6, (int) round($n / $maxLoad * 80)) ?>px"></span></span>
      <span class="text-xs text-ink-muted"><?= e(date('D', strtotime($d))) ?></span>
      <span class="text-sm <?= $i === 0 ? 'font-semibold' : '' ?>"><?= $sun ? '–' : $n ?></span>
    </a>
    <?php endfor; ?>
  </div>
</section>

<div class="grid xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] gap-8">
  <section class="rounded-md bg-white border border-line" aria-labelledby="up-h">
    <div class="flex items-center justify-between p-6 pb-4"><h2 id="up-h" class="text-lg font-medium">Coming up</h2><a href="<?= e(url('admin/appointments.php')) ?>" class="text-sm text-sea hover:underline">All appointments →</a></div>
    <?php if (!$upcoming): ?>
      <p class="px-6 pb-6 text-ink-muted">No bookings coming up. New requests from the website appear here.</p>
    <?php else: ?>
    <ul class="divide-y divide-line">
      <?php foreach ($upcoming as $a): ?>
      <li><a href="<?= e(url('admin/appointment.php?id=' . $a['id'])) ?>" class="row-link flex items-center gap-4 px-6 py-4">
        <span class="w-14 text-center flex-none"><span class="block text-xs text-ink-muted uppercase"><?= e(date('D', strtotime($a['slot_at'] ?: $a['preferred_date']))) ?></span><span class="block text-2xl leading-none mt-1"><?= e(date('j', strtotime($a['slot_at'] ?: $a['preferred_date']))) ?></span></span>
        <span class="min-w-0 flex-1"><span class="block font-medium truncate"><?= e($a['name']) ?></span><span class="block text-sm text-ink-muted truncate"><?= e($a['service_name']) ?> · <?= $a['slot_at'] ? e(date('H:i', strtotime($a['slot_at']))) : e(ucfirst($a['preferred_time'])) ?></span></span>
        <span class="pill s-<?= e($a['status']) ?>"><?= e(APPT_STATUSES[$a['status']]) ?></span>
      </a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="rounded-md bg-white border border-line" aria-labelledby="msg-h">
    <div class="flex items-center justify-between p-6 pb-4"><h2 id="msg-h" class="text-lg font-medium">Latest messages</h2><a href="<?= e(url('admin/enquiries.php')) ?>" class="text-sm text-sea hover:underline">All messages →</a></div>
    <?php if (!$messages): ?>
      <p class="px-6 pb-6 text-ink-muted">No messages yet.</p>
    <?php else: ?>
    <ul class="divide-y divide-line">
      <?php foreach ($messages as $m): ?>
      <li><a href="<?= e(url('admin/enquiries.php?open=' . $m['id'])) ?>#m<?= (int) $m['id'] ?>" class="row-link block px-6 py-4">
        <span class="flex items-center justify-between gap-3"><span class="font-medium truncate"><?= e($m['organisation'] ?: $m['name']) ?></span><span class="text-xs text-ink-muted flex-none"><?= e(time_ago($m['created_at'])) ?></span></span>
        <span class="flex items-center gap-2 mt-1"><span class="pill s-<?= e($m['status']) ?>"><?= e(ENQUIRY_TYPES[$m['type']]) ?></span><span class="text-sm text-ink-muted truncate"><?= e($m['message']) ?></span></span>
      </a></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
