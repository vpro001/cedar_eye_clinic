<?php
require_once __DIR__ . '/includes/functions.php';

$cat = get('cat');
if ($cat !== '' && !isset(CATEGORIES[$cat])) {
    $cat = '';
}
$counts = array_column(rows('SELECT category, COUNT(*) n FROM products WHERE active = 1 GROUP BY category'), 'n', 'category');
$all    = array_sum($counts);
$items  = $cat === ''
    ? rows('SELECT * FROM products WHERE active = 1 ORDER BY featured DESC, sort, id')
    : rows('SELECT * FROM products WHERE active = 1 AND category = ? ORDER BY featured DESC, sort, id', 's', [$cat]);

$heading = $cat === '' ? 'Optical shop' : CATEGORIES[$cat]['name'];

$page_title       = $heading . ' at Cedar Eye Clinic, Port Harcourt';
$page_description = 'Prescription frames, sunglasses, safety eyewear, cases and chains at Cedar Eye Clinic, GRA Phase 3. Ask about any piece for its price.';
$canonical        = 'shop.php' . ($cat !== '' ? '?cat=' . $cat : '');
$nav              = 'shop';
include __DIR__ . '/includes/header.php';
?>
  <header class="page-head">
    <div class="wrap">
      <div class="page-head__inner">
        <?php if ($cat !== ''): ?><p class="crumbs"><a href="<?= e(url('shop.php')) ?>">Optical shop</a><span aria-hidden="true">/</span><span><?= e(CATEGORIES[$cat]['short']) ?></span></p><?php endif; ?>
        <h1 class="rise"><?= e($heading) ?></h1>
        <p class="lead rise rise-2">What is in the clinic now. Stock changes often, so ask about a piece and we reply with its price and whether it is in. Frames are fitted with your lenses here, after your eye test.</p>
      </div>
      <nav class="filters rise rise-3" aria-label="Categories">
        <a href="<?= e(url('shop.php')) ?>"<?= $cat === '' ? ' aria-current="page"' : '' ?>>All <span class="mono"><?= $all ?></span></a>
        <?php foreach (CATEGORIES as $k => $c): ?>
        <a href="<?= e(url('shop.php?cat=' . $k)) ?>"<?= $cat === $k ? ' aria-current="page"' : '' ?>><?= e($c['short']) ?> <span class="mono"><?= (int) ($counts[$k] ?? 0) ?></span></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>

  <section class="section" aria-label="Pieces in the shop">
    <div class="wrap">
      <?php if (!$items): ?>
        <div class="notice"><h2>Nothing here right now.</h2><p>New stock arrives often. <a class="link" href="<?= e(wa_link('Hello Cedar Eye Clinic, when will you have new ' . strtolower($heading) . '?')) ?>" rel="noopener" target="_blank">Ask on WhatsApp</a> what is coming.</p></div>
      <?php else: ?>
      <div class="products">
        <?php foreach ($items as $i => $p): ?>
        <a class="product" href="<?= e(url('product.php?id=' . $p['id'])) ?>">
          <div class="photo"><?= pic($p['image'], $p['name'], 320, $i < 4 ? 'loading="eager"' : '', '(max-width: 759px) 50vw, (max-width: 1099px) 33vw, 300px') ?></div>
          <span class="product__name"><?= e($p['name']) ?></span>
          <span class="product__meta"><span><?= e(CATEGORIES[$p['category']]['short']) ?></span><?= $p['in_stock'] ? '<span>' . e(money($p['price']) ?: 'Ask for price') . '</span>' : '<span class="out">Out of stock</span>' ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
<?php
$close_h = 'Get your prescription first, then your frames.';
$close_p = 'Frames are fitted to your eye test on the same visit. ' . hours_line() . '.';
include __DIR__ . '/includes/footer.php';
