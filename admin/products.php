<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin();

if (is_post()) {
    admin_post_guard();
    $id = (int) post('id');
    $act = post('action');
    if ($id && in_array($act, ['show', 'hide', 'stock', 'nostock', 'feature', 'unfeature'], true)) {
        $map = ['show' => ['active', 1], 'hide' => ['active', 0], 'stock' => ['in_stock', 1], 'nostock' => ['in_stock', 0], 'feature' => ['featured', 1], 'unfeature' => ['featured', 0]];
        [$col, $v] = $map[$act];
        q("UPDATE products SET $col = ? WHERE id = ?", 'ii', [$v, $id]);
        flash('ok', 'Saved.');
    }
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? url('admin/products.php')), true, 303);
    exit;
}

$cat    = get('cat');
$search = clip(get('q'), 80);
$where = []; $types = ''; $args = [];
if (isset(CATEGORIES[$cat])) { $where[] = 'category = ?'; $types .= 's'; $args[] = $cat; }
if ($search !== '') { $where[] = 'name LIKE ?'; $types .= 's'; $args[] = '%' . $search . '%'; }
$list = rows('SELECT * FROM products ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY active DESC, category, sort, id', $types, $args);

$admin_title = 'Shop items';
$admin_nav   = 'products';
require __DIR__ . '/_header.php';
?>
<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
  <div><h1 class="text-4xl tracking-tight">Shop items</h1><p class="text-ink-muted mt-1"><?= count($list) ?> items. Hidden items stay here but leave the website.</p></div>
  <a class="btn btn-primary" href="<?= e(url('admin/product-edit.php')) ?>">Add an item</a>
</header>

<form class="flex flex-wrap gap-3 mb-6 items-end" method="get">
  <div class="flex-1 min-w-[220px]"><label class="lbl" for="q">Search</label><input class="inp" id="q" name="q" value="<?= e($search) ?>" placeholder="Item name"></div>
  <div><label class="lbl" for="cat">Category</label><select class="inp" id="cat" name="cat"><option value="">All categories</option><?php foreach (CATEGORIES as $k => $c): ?><option value="<?= e($k) ?>"<?= $cat === $k ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn-primary h-11">Filter</button>
</form>

<div class="grid sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5">
  <?php foreach ($list as $p): ?>
  <article class="rounded-md bg-white border border-line overflow-hidden flex flex-col <?= $p['active'] ? '' : 'opacity-60' ?>">
    <a href="<?= e(url('admin/product-edit.php?id=' . $p['id'])) ?>" class="block aspect-[4/3] bg-paper-2 overflow-hidden"><img src="<?= e(img_url($p['image'])) ?>" alt="" class="w-full h-full object-cover hover:scale-[1.03] transition duration-500" loading="lazy"></a>
    <div class="p-5 flex-1 flex flex-col gap-3">
      <div class="flex items-start justify-between gap-3">
        <a href="<?= e(url('admin/product-edit.php?id=' . $p['id'])) ?>" class="font-medium hover:text-sea"><?= e($p['name']) ?></a>
        <span class="text-sm whitespace-nowrap"><?= e(money($p['price']) ?: '—') ?></span>
      </div>
      <p class="text-sm text-ink-muted"><?= e(CATEGORIES[$p['category']]['name']) ?></p>
      <div class="flex flex-wrap gap-1.5">
        <?php if (!$p['active']): ?><span class="pill s-cancelled">Hidden</span><?php endif; ?>
        <?php if (!$p['in_stock']): ?><span class="pill s-pending">Out of stock</span><?php endif; ?>
        <?php if ($p['featured']): ?><span class="pill s-confirmed">Shown first</span><?php endif; ?>
      </div>
      <form method="post" class="mt-auto flex flex-wrap gap-2 pt-2">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <button class="btn btn-ghost h-9 text-sm" name="action" value="<?= $p['in_stock'] ? 'nostock' : 'stock' ?>"><?= $p['in_stock'] ? 'Mark sold out' : 'Back in stock' ?></button>
        <button class="btn btn-ghost h-9 text-sm" name="action" value="<?= $p['active'] ? 'hide' : 'show' ?>"><?= $p['active'] ? 'Hide' : 'Show' ?></button>
        <a class="btn btn-ghost h-9 text-sm" href="<?= e(url('admin/product-edit.php?id=' . $p['id'])) ?>">Edit</a>
      </form>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
