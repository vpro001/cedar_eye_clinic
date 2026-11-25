<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

$id = (int) get('id');
$s  = $id ? row('SELECT * FROM services WHERE id = ?', 'i', [$id]) : null;
if ($id && !$s) {
    flash('error', 'That service no longer exists.');
    redirect('admin/services.php');
}
$errors = [];

function slugify(string $t): string
{
    $t = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $t), '-'));
    return $t !== '' ? substr($t, 0, 80) : 'service';
}

if (is_post()) {
    admin_post_guard();
    if (post('action') === 'delete' && $s) {
        if ((int) val('SELECT COUNT(*) FROM appointments WHERE service_id = ?', 'i', [$s['id']])) {
            q('UPDATE services SET active = 0 WHERE id = ?', 'i', [$s['id']]);
            flash('ok', '"' . $s['name'] . '" has bookings, so it was hidden instead of deleted.');
        } else {
            q('DELETE FROM services WHERE id = ?', 'i', [$s['id']]);
            flash('ok', '"' . $s['name'] . '" was deleted.');
        }
        redirect('admin/services.php');
    }
    $v = [
        'name'       => clip(post('name'), 120),
        'summary'    => clip(post('summary'), 255),
        'body'       => clip(post('body'), 5000),
        'price_note' => clip(post('price_note'), 80),
        'featured'   => isset($_POST['featured']) ? 1 : 0,
        'active'     => isset($_POST['active']) ? 1 : 0,
    ];
    if ($v['name'] === '') $errors['name'] = 'Write the service\'s name.';
    if ($v['summary'] === '') $errors['summary'] = 'Write one sentence about the service.';
    if ($v['featured'] && !($s['featured'] ?? 0) && (int) val('SELECT COUNT(*) FROM services WHERE featured = 1 AND active = 1') >= 6) {
        $errors['featured'] = 'Six services are already on the home page. Take one off first.';
    }
    if (!$errors) {
        if ($s) {
            q('UPDATE services SET name = ?, summary = ?, body = ?, price_note = ?, featured = ?, active = ? WHERE id = ?', 'ssssiii',
                [$v['name'], $v['summary'], $v['body'], $v['price_note'], $v['featured'], $v['active'], $s['id']]);
            flash('ok', '"' . $v['name'] . '" was saved.');
            redirect('admin/service-edit.php?id=' . $s['id']);
        }
        $slug = slugify($v['name']);
        $base = $slug; $n = 2;
        while (val('SELECT id FROM services WHERE slug = ?', 's', [$slug])) { $slug = $base . '-' . $n++; }
        $sort = (int) val('SELECT COALESCE(MAX(sort), 0) + 10 FROM services');
        q('INSERT INTO services (slug, name, summary, body, price_note, featured, active, sort) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', 'sssssiii',
            [$slug, $v['name'], $v['summary'], $v['body'], $v['price_note'], $v['featured'], $v['active'], $sort]);
        flash('ok', '"' . $v['name'] . '" was added.');
        redirect('admin/service-edit.php?id=' . last_id());
    }
    $s = array_merge($s ?? ['id' => 0, 'slug' => ''], $v);
}
$s = $s ?? ['id' => 0, 'slug' => '', 'name' => '', 'summary' => '', 'body' => '', 'price_note' => '', 'featured' => 0, 'active' => 1];
$err = fn($k) => isset($errors[$k]) ? '<p class="mt-1.5 text-sm text-red-700">' . e($errors[$k]) . '</p>' : '';

$admin_title = $s['id'] ? 'Edit ' . $s['name'] : 'Add a service';
$admin_nav   = 'services';
require __DIR__ . '/_header.php';
?>
<p class="mb-4"><a href="<?= e(url('admin/services.php')) ?>" class="text-sm text-ink-muted hover:text-ink">← Services</a></p>
<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
  <h1 class="text-4xl tracking-tight"><?= $s['id'] ? e($s['name']) : 'Add a service' ?></h1>
  <?php if ($s['id'] && $s['active']): ?><a class="btn btn-ghost" href="<?= e(url('services.php#' . $s['slug'])) ?>" target="_blank" rel="noopener">See it on the website ↗</a><?php endif; ?>
</header>
<form method="post" class="rounded-md bg-white border border-line p-6 lg:p-8 grid gap-5 max-w-3xl" novalidate>
  <?= csrf_field() ?>
  <div><label class="lbl" for="name">Name</label><input class="inp" id="name" name="name" value="<?= e($s['name']) ?>" maxlength="120"><?= $err('name') ?></div>
  <div><label class="lbl" for="summary">One-sentence summary</label><input class="inp" id="summary" name="summary" value="<?= e($s['summary']) ?>" maxlength="255"><p class="mt-1.5 text-sm text-ink-muted">Shown on the services page in large type. Up to 25 words reads best.</p><?= $err('summary') ?></div>
  <div><label class="lbl" for="body">Full description</label><textarea class="inp min-h-[12rem]" id="body" name="body"><?= e($s['body']) ?></textarea><p class="mt-1.5 text-sm text-ink-muted">Leave an empty line between paragraphs.</p></div>
  <div><label class="lbl" for="price_note">Price line (optional)</label><input class="inp" id="price_note" name="price_note" value="<?= e($s['price_note']) ?>" maxlength="80" placeholder="For example: From ₦15,000"><p class="mt-1.5 text-sm text-ink-muted">Leave empty to show no price.</p></div>
  <fieldset class="grid gap-3"><legend class="lbl">On the website</legend>
    <label class="flex items-center gap-3"><input type="checkbox" name="active" class="w-5 h-5 accent-[#2c7469]"<?= $s['active'] ? ' checked' : '' ?>> Show on the website and in the booking form</label>
    <label class="flex items-center gap-3"><input type="checkbox" name="featured" class="w-5 h-5 accent-[#2c7469]"<?= $s['featured'] ? ' checked' : '' ?>> On the home page (six at most)</label>
    <?= $err('featured') ?>
  </fieldset>
  <div class="flex flex-wrap gap-2 justify-between">
    <button class="btn btn-primary" name="action" value="save"><?= $s['id'] ? 'Save changes' : 'Add the service' ?></button>
    <?php if ($s['id']): ?><button class="btn btn-danger" name="action" value="delete" data-confirm="Delete this service?">Delete</button><?php endif; ?>
  </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
