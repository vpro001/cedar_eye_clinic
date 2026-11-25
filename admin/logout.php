<?php
require_once __DIR__ . '/../includes/functions.php';

// sign out only on a POST with a valid token, so a link elsewhere cannot sign staff out
if (is_post() && csrf_ok()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
redirect('admin/login.php');
