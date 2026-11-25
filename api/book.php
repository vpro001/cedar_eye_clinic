<?php
/**
 * POST api/book.php — the booking wizard's submit, as JSON. It runs the same server-side checks
 * as the plain form in book.php (CSRF, honeypot, throttle, capacity), so a browser with scripts
 * off posts to book.php instead and gets the same result.
 *
 * Response: {"ok": true, "ref": "CE-7K4Q2M", "html": "<div class=\"slip\">..."}
 *      or:  {"ok": false, "errors": {...}}
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/forms.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'errors' => ['form' => 'Send this as a form.']]);
    return;
}

$state = ['values' => [], 'errors' => []];
$ref   = booking_handle($state);

if ($ref) {
    // the confirmation slip is rendered on the server, so the page says the same thing with scripts off
    $appt = row('SELECT * FROM appointments WHERE ref = ?', 's', [$ref]);
    echo json_encode([
        'ok'   => true,
        'ref'  => $ref,
        'html' => $appt ? booking_slip($appt) : '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}
http_response_code(422);
echo json_encode(['ok' => false, 'errors' => $state['errors']], JSON_UNESCAPED_UNICODE);
