<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

if (is_post()) {
    admin_post_guard();
    $id = (int) post('id');
    $act = post('action');
    if ($id && in_array($act, ['up', 'down'], true)) {
        // swap sort order with the neighbour
        $cur = row('SELECT id, sort FROM services WHERE id = ?', 'i', [$id]);
        $nb  = $act === 'up'
            ? row('SELECT id, sort FROM services WHERE (sort, id) < (?, ?) ORDER BY sort DESC, id DESC LIMIT 1', 'ii', [$cur['sort'], $cur['id']])
            : row('SELECT id, sort FROM services WHERE (sort, id) > (?, ?) ORDER BY sort, id LIMIT 1', 'ii', [$cur['sort'], $cur['id']]);
        if ($cur && $nb) {
            $a = (int) $nb['sort']; $b = (int) $cur['sort'];
            if ($a === $b) { $a = $act === 'up' ? $b - 1 : $b + 1; }
            q('UPDATE services SET sort = ? WHERE id = ?', 'ii', [$a, $cur['id']]);
            q('UPDATE services SET sort = ? WHERE id = ?', 'ii', [$b, $nb['id']]);
        }
    } elseif ($id && in_array($act, ['show', 'hide'], true)) {
        q('UPDATE services SET active = ? WHERE id = ?', 'ii', [$act === 'show' ? 1 : 0, $id]);
        flash('ok', $act === 'show' ? 'The service is back on the website.' : 'The service is hidden from the website and the booking form.');
    }
    redirect('admin/services.php');
}

$list = services_list(false);
$booked = array_column(rows('SELECT service_id, COUNT(*) n FROM appointments GROUP BY service_id'), 'n', 'service_id');

$admin_title = 'Services';
$admin_nav   = 'services';
require __DIR__ . '/_header.php';
?>
<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
  <div><h1 class="text-4xl tracking-tight">Services</h1><p class="text-ink-muted mt-1">The services page, the booking form's list and the home page all read from here. Up to six marked “On the home page” show there.</p></div>
  <a class="btn btn-primary" href="<?= e(url('admin/service-edit.php')) ?>">Add a service</a>
</header>
<div class="rounded-md bg-white border border-line overflow-hidden">
  <ul class="divide-y divide-line">
    <?php foreach ($list as $i => $s): ?>
    <li class="flex flex-wrap items-center gap-4 px-6 py-4 <?= $s['active'] ? '' : 'opacity-60' ?>">
      <form method="post" class="flex flex-col">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
        <button class="w-8 h-6 grid place-items-center rounded hover:bg-paper disabled:opacity-20" name="action" value="up" aria-label="Move <?= e($s['name']) ?> up"<?= $i === 0 ? ' disabled' : '' ?>>▲</button>
        <button class="w-8 h-6 grid place-items-center rounded hover:bg-paper disabled:opacity-20" name="action" value="down" aria-label="Move <?= e($s['name']) ?> down"<?= $i === count($list) - 1 ? ' disabled' : '' ?>>▼</button>
      </form>
      <div class="min-w-0 flex-1">
        <a href="<?= e(url('admin/service-edit.php?id=' . $s['id'])) ?>" class="font-medium hover:text-sea"><?= e($s['name']) ?></a>
        <p class="text-sm text-ink-muted truncate"><?= e($s['summary']) ?></p>
      </div>
      <div class="flex flex-wrap gap-1.5">
        <?php if ($s['featured']): ?><span class="pill s-confirmed">On the home page</span><?php endif; ?>
        <?php if (!$s['active']): ?><span class="pill s-cancelled">Hidden</span><?php endif; ?>
        <span class="pill s-read"><?= (int) ($booked[$s['id']] ?? 0) ?> booked</span>
      </div>
      <form method="post" class="flex gap-2">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
        <button class="btn btn-ghost h-9" name="action" value="<?= $s['active'] ? 'hide' : 'show' ?>"><?= $s['active'] ? 'Hide' : 'Show' ?></button>
        <a class="btn btn-ghost h-9" href="<?= e(url('admin/service-edit.php?id=' . $s['id'])) ?>">Edit</a>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
