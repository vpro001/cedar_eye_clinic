<?php
/**
 * Patient accounts: signing in and out, and the session that keeps "My bookings" together.
 * Staff accounts live in admins and are handled by current_admin() in functions.php.
 */

/** The patient signed in for this request, or null. Worked out once and remembered. */
function current_patient(): ?array
{
    if (array_key_exists('cedar_patient', $GLOBALS)) {
        return $GLOBALS['cedar_patient'];
    }
    $id = (int) ($_SESSION['patient_id'] ?? 0);
    $me = $id ? row('SELECT id, name, email, phone FROM patients WHERE id = ?', 'i', [$id]) : null;
    if (!$me) {
        unset($_SESSION['patient_id']);
    }
    return $GLOBALS['cedar_patient'] = $me;
}

/** Signs in and remembers where the visitor was heading before they were asked to sign in. */
function require_patient(): array
{
    $me = current_patient();
    if (!$me) {
        $_SESSION['after_patient_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('login.php');
    }
    return $me;
}

function patient_login(array $p): void
{
    session_regenerate_id(true);
    $_SESSION['patient_id'] = (int) $p['id'];
    $GLOBALS['cedar_patient'] = ['id' => (int) $p['id'], 'name' => $p['name'], 'email' => $p['email'], 'phone' => $p['phone'] ?? ''];
    q('UPDATE patients SET last_login = NOW() WHERE id = ?', 'i', [(int) $p['id']]);
}

function patient_logout(): void
{
    unset($_SESSION['patient_id'], $_SESSION['after_patient_login']);
    $GLOBALS['cedar_patient'] = null;
    session_regenerate_id(true);
}

/** Where to send a patient after signing in: the page they asked for, or their account. */
function patient_home(): string
{
    $to = $_SESSION['after_patient_login'] ?? '';
    unset($_SESSION['after_patient_login']);
    // only return to a page inside this site
    if ($to !== '' && str_starts_with($to, url('')) && !str_contains($to, '//')) {
        return $to;
    }
    return 'account.php';
}
