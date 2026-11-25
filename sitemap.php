<?php
/** sitemap.xml (served at /sitemap.xml through .htaccess): the public pages and every shop item. */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$urls = [['', '1.0'], ['services.php', '0.9'], ['book.php', '0.9'], ['shop.php', '0.8'], ['corporate.php', '0.7'], ['about.php', '0.6'], ['contact.php', '0.7'], ['privacy.php', '0.2']];
foreach (CATEGORIES as $k => $_) {
    $urls[] = ['shop.php?cat=' . $k, '0.6'];
}
foreach (rows('SELECT id FROM products WHERE active = 1 ORDER BY id') as $p) {
    $urls[] = ['product.php?id=' . $p['id'], '0.5'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$path, $prio]) {
    echo '  <url><loc>' . htmlspecialchars(abs_url($path), ENT_XML1) . '</loc><priority>' . $prio . '</priority></url>' . "\n";
}
echo '</urlset>' . "\n";
