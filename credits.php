<?php
require_once __DIR__ . '/includes/functions.php';

// the licensed photographs the site uses: every entry in credits.json whose file is still on disk
$data   = json_decode((string) @file_get_contents(__DIR__ . '/assets/img/credits.json'), true);
$photos = array_values(array_filter($data['photos'] ?? [], fn($p) => is_file(__DIR__ . '/assets/img/' . $p['file'])));

$page_title       = 'Photograph credits: Cedar Eye Clinic';
$page_description = 'Sources and licences of the photographs on the Cedar Eye Clinic website that were not taken at the clinic.';
$canonical        = 'credits.php';
include __DIR__ . '/includes/header.php';
?>
  <section class="page-head" aria-labelledby="page-h">
    <div class="wrap">
      <div class="prose">
        <h1 id="page-h">Photo credits</h1>
        <p class="lead">The shop photographs are the clinic's own. These come from Wikimedia Commons under the licences named.</p>
        <dl class="spec">
          <?php foreach ($photos as $p): ?>
          <div style="display: grid; gap: 4px"><dt><a class="link" href="<?= e($p['page']) ?>" rel="noopener" target="_blank"><?= e($p['alt']) ?></a></dt><dd style="text-align: left; font-weight: 400" class="muted"><?= e($p['by']) ?>, <a href="<?= e($p['licenceUrl']) ?>" rel="noopener license" target="_blank"><?= e($p['licence']) ?></a>, via <?= e($p['source']) ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>
  </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
