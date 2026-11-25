<?php
require_once __DIR__ . '/includes/functions.php';

$services = services_list();

$page_title       = 'Eye care services at Cedar Eye Clinic, Port Harcourt';
$page_description = 'Eye examinations, refraction, glasses, contact lenses, glaucoma screening, children\'s eye tests, dry-eye and low-vision care, and HMO eye care in GRA Phase 3.';
$canonical        = 'services.php';
$nav              = 'services';
include __DIR__ . '/includes/header.php';
?>
  <header class="page-head page-head--split">
    <div class="wrap">
      <div class="page-head__inner">
        <h1 class="rise">Services</h1>
        <p class="lead rise rise-2"><?= count($services) ?> services for adults, children, companies and HMO members, all in our clinic on <?= e(setting('street')) ?>. Not sure which one you need? Book an eye examination and we take it from there.</p>
      </div>
      <div class="btn-row rise rise-3"><a class="btn btn--primary btn--lg" href="<?= e(url('book.php')) ?>"><?= MAIN_ACTION ?></a></div>
    </div>
  </header>

  <section class="section" aria-label="All services">
    <div class="wrap grid">
      <nav class="svc-nav sticky" aria-label="Services on this page">
        <ol>
          <?php foreach ($services as $s): ?><li><a href="#<?= e($s['slug']) ?>"><?= e($s['name']) ?></a></li><?php endforeach; ?>
        </ol>
      </nav>
      <div class="svc-list">
        <?php foreach ($services as $i => $s): ?>
        <article class="svc" id="<?= e($s['slug']) ?>" aria-labelledby="svc-<?= (int) $s['id'] ?>">
          <div class="svc__head"><span class="index__num"><?= sprintf('%02d', $i + 1) ?></span><h2 id="svc-<?= (int) $s['id'] ?>"><?= e($s['name']) ?></h2></div>
          <div class="svc__body">
            <p class="lead"><?= e($s['summary']) ?></p>
            <?php foreach (preg_split('/\n\s*\n/', trim($s['body'])) as $para): if ($para === '') continue; ?>
            <p><?= nl2br(e($para)) ?></p>
            <?php endforeach; ?>
            <div class="svc__foot">
              <a class="link arrow" href="<?= e(url('book.php?service=' . $s['slug'])) ?>">Book this</a>
              <?php if ($s['price_note'] !== ''): ?><span class="mono muted"><?= e($s['price_note']) ?></span><?php endif; ?>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
