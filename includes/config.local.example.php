<?php
/**
 * Copy this file to config.local.php on the live server and fill it in.
 * config.local.php is never served to browsers (the includes/ folder is blocked).
 * Any key left out keeps the default from config.php.
 */
return [
    'DB_HOST'      => 'localhost',
    'DB_USER'      => 'cedar',
    'DB_PASS'      => 'a-long-random-password',
    'DB_NAME'      => 'cedar_clinic',
    'DB_PORT'      => 3306,

    'SITE_URL'     => 'https://www.example.com',
    'FORCE_HTTPS'  => true,
    'TRUST_PROXY'  => false,   // true only behind Cloudflare or a load balancer

    'NOTIFY_EMAIL' => '',      // empty: the clinic email from Admin > Clinic details
    'MAIL_FROM'    => 'website@example.com',

    'DEBUG'        => false,
];
