<?php
/**
 * Last-resort error pages and handlers. Nothing here touches the database, so it works when that is down.
 */

/** Sends a plain, branded error page and stops. */
function fatal_page(int $code, string $title, string $text): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        if ($code === 503) {
            header('Retry-After: 300');
        }
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $p = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>{$t} · Cedar Eye Clinic</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f3ee;color:#15201f;font:17px/1.6 system-ui,sans-serif;padding:24px}
main{max-width:34rem}h1{font:400 2.25rem/1.15 Georgia,serif;margin:0 0 .75rem;letter-spacing:-.01em}p{margin:0;color:#4d5755}b{color:#15201f}</style>
</head><body><main><p style="font:500 13px ui-monospace,monospace;letter-spacing:.06em;margin-bottom:1.5rem">CEDAR EYE CLINIC</p><h1>{$t}</h1><p>{$p}</p></main></body></html>
HTML;
    exit;
}

set_exception_handler(function (Throwable $e): void {
    error_log('[cedar] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (defined('APP_DEBUG') && APP_DEBUG) {
        fatal_page(500, 'Error', $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    }
    fatal_page(500, 'Something went wrong', 'The page could not load. Try again in a minute, or call or WhatsApp the clinic. If you were sending a form, it may not have gone through.');
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[cedar] fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        if (!(defined('APP_DEBUG') && APP_DEBUG)) {
            fatal_page(500, 'Something went wrong', 'The page could not load. Try again in a minute, or call or WhatsApp the clinic.');
        }
    }
});
