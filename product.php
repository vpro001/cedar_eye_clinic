<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/forms.php';

$id = (int) get('id');
$p  = $id ? row('SELECT * FROM products WHERE id = ? AND active = 1', 'i', [$id]) : null;
if (!$p) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$state = ['values' => ['name' => '', 'phone' => '', 'email' => '', 'message' => 'Is the ' . $p['name'] . ' in stock, and what does it cost?'], 'errors' => []];
if (is_post() && enquiry_handle('product', $state, (int) $p['id'])) {
    redirect('product.php?id=' . $p['id'] . '&sent=1#ask');
}
$sent    = get('sent') === '1';
$related = rows('SELECT * FROM products WHERE active = 1 AND category = ? AND id <> ? ORDER BY featured DESC, sort LIMIT 4', 'si', [$p['category'], $p['id']]);
$cat     = CATEGORIES[$p['category']];
$price   = money($p['price']);

$page_title       = $p['name'] . ': Cedar Eye Clinic optical shop';
$page_description = $p['description'];
$canonical        = 'product.php?id=' . $p['id'];
$og_image         = $p['image'];
$noindex          = $sent;
$nav              = 'shop';
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap item">
      <div class="item__pic"><?= pic($p['image'], $p['name'], 760, 'fetchpriority="high" loading="eager"', '(max-width: 959px) 100vw, 720px') ?></div>
      <div class="item__text">
        <p class="crumbs"><a href="<?= e(url('shop.php')) ?>">Optical shop</a><span aria-hidden="true">/</span><a href="<?= e(url('shop.php?cat=' . $p['category'])) ?>"><?= e($cat['short']) ?></a></p>
        <h1 id="page-h" style="font-size: clamp(2rem, 1.5rem + 2vw, 3.2rem)"><?= e($p['name']) ?></h1>
        <?php if ($p['description'] !== ''): ?><p class="lead"><?= e($p['description']) ?></p><?php endif; ?>
        <dl class="spec">
          <div><dt>Price</dt><dd><?= e($price ?: 'Ask for price') ?></dd></div>
          <div><dt>Stock</dt><dd<?= $p['in_stock'] ? '' : ' class="out"' ?>><?= $p['in_stock'] ? 'In the clinic' : 'Out of stock' ?></dd></div>
          <?php if ($p['category'] === 'frames' || $p['category'] === 'safety'): ?><div><dt>Lenses</dt><dd>Fitted to your prescription</dd></div><?php endif; ?>
        </dl>
        <div class="btn-row">
          <a class="btn btn--primary" href="<?= e(wa_link('Hello Cedar Eye Clinic, I saw "' . $p['name'] . '" on your website. Is it available, and how much is it?')) ?>" rel="noopener" target="_blank">Ask on WhatsApp</a>
          <a class="btn btn--ghost" href="#ask">Send a question</a>
        </div>
      </div>
    </div>
  </section>

  <section class="section section--band" id="ask" aria-labelledby="ask-h">
    <div class="wrap split">
      <div class="section-head">
        <h2 id="ask-h">Ask about this piece</h2>
        <p>Send your question and the clinic calls or messages you back with the price and stock, during opening hours.</p>
      </div>
      <div>
        <?php if ($sent): ?>
          <div class="notice notice--ok" role="status"><h3>Your question is in.</h3><p>We reply on the phone number you gave, during opening hours.</p></div>
        <?php else: ?>
        <div class="slip"><div class="slip__body">
        <form class="form" method="post" action="<?= e(url('product.php?id=' . $p['id'])) ?>#ask" novalidate>
          <?= csrf_field() ?><?= honeypot() ?>
          <div class="form__grid">
            <?= field('name', 'Your name', 'text', $state, ['required' => 1, 'autocomplete' => 'name', 'maxlength' => 120, 'error' => 'Write your name.']) ?>
            <?= field('phone', 'Phone number', 'tel', $state, ['required' => 1, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 20, 'error' => 'Write a phone number we can call, like 0803 123 4567.']) ?>
            <?= field('message', 'Your question', 'textarea', $state, ['required' => 1, 'full' => 1, 'maxlength' => 2000, 'error' => 'Write your question in a few words.']) ?>
          </div>
          <div class="form__foot"><button class="btn btn--primary" type="submit">Send the question</button></div>
          <?= form_status($state) ?>
        </form>
        </div></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($related): ?>
  <section class="section" aria-labelledby="more-h">
    <div class="wrap">
      <div class="section-head section-head--row"><div><h2 id="more-h">More <?= e(strtolower($cat['name'])) ?></h2></div><a class="link arrow" href="<?= e(url('shop.php?cat=' . $p['category'])) ?>">See all <?= e(strtolower($cat['short'])) ?></a></div>
      <div class="products" style="margin-top: 40px">
        <?php foreach ($related as $r): ?>
        <a class="product" href="<?= e(url('product.php?id=' . $r['id'])) ?>">
          <div class="photo"><?= pic($r['image'], $r['name'], 320, '', '(max-width: 759px) 50vw, (max-width: 1099px) 33vw, 300px') ?></div>
          <span class="product__name"><?= e($r['name']) ?></span>
          <span class="product__meta"><span><?= e($cat['short']) ?></span><?= $r['in_stock'] ? '<span>' . e(money($r['price']) ?: 'Ask for price') . '</span>' : '<span class="out">Out of stock</span>' ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
<?php
$close_h = 'Get your prescription first, then your frames.';
$close_p = 'Frames are fitted to your eye test on the same visit. ' . hours_line() . '.';
include __DIR__ . '/includes/footer.php';
