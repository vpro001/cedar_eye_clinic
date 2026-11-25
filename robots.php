<?php
/** robots.txt (through .htaccess), with the sitemap's full address. */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: text/plain; charset=utf-8');
$base = base_path();
echo "User-agent: *\n";
echo "Disallow: $base/admin/\n";
echo "Disallow: $base/install.php\n";
echo "Disallow: $base/booking-status.php\n";
echo "Disallow: $base/login.php\n";
echo "Disallow: $base/account.php\n";
echo "Disallow: $base/register.php\n";
echo "Disallow: $base/reset.php\n";
echo "Disallow: $base/forgot.php\n";
echo "\nSitemap: " . abs_url('sitemap.xml') . "\n";
