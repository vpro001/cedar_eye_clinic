<?php
/**
 * GET api/slots.php?date=YYYY-MM-DD — what is still free on one day, for the booking wizard.
 * Read-only and unauthenticated, so no CSRF: it only reports what the booking form would tell you.
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$date = (string) ($_GET['date'] ?? '');
$av   = slot_availability($date);
if (!$av['valid']) {
    $av['error'] = 'Write a day as YYYY-MM-DD.';
}
echo json_encode($av, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
