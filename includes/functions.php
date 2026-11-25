<?php
/**
 * Shared helpers: escaping, sessions, CSRF, flash messages, settings, auth, forms.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/migrate.php';
require_once __DIR__ . '/auth.php';

date_default_timezone_set(APP_TZ);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/** True when the visitor reached the site over https (directly, or through a trusted proxy). */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return TRUST_PROXY && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (PHP_SAPI !== 'cli') {
    // buffer the page, so an error part-way through replaces it with a clean error page
    ob_start();

    if (FORCE_HTTPS && !is_https()) {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_name(is_https() ? '__Host-cedar' : 'cedar_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => is_https(),
        ]);
        session_start();
    }

    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

/* ---------------------------------------------------------------- output */

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** The site's base path ("/cedar"), worked out from where this folder sits under the web root. */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $app  = realpath(__DIR__ . '/..') ?: '';
    $base = ($root !== '' && str_starts_with($app, $root)) ? str_replace('\\', '/', substr($app, strlen($root))) : '';
    return rtrim($base, '/');
}

/** A URL inside the site: url('shop.php') -> /cedar/shop.php */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    $to = preg_match('~^https?://~', $path) ? $path : url($path);
    header('Location: ' . $to, true, 303);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, string $default = ''): string
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (TRUST_PROXY) {
        $fwd = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0];
        $fwd = trim((string) $fwd);
        if (filter_var($fwd, FILTER_VALIDATE_IP)) {
            $ip = $fwd;
        }
    }
    return substr($ip, 0, 45);
}

/** A file under assets/ with a version stamp, so browsers pick up new releases. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . ASSET_VERSION;
}

/** The site's absolute origin and base path: https://example.com or http://localhost/cedar */
function site_root(): string
{
    if (SITE_URL !== '') {
        return SITE_URL;
    }
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return (is_https() ? 'https' : 'http') . '://' . $host . base_path();
}

/** An absolute URL for canonical links, the sitemap and share cards. */
function abs_url(string $path = ''): string
{
    return site_root() . '/' . ltrim($path, '/');
}

/* ---------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    $sent = $_POST['csrf'] ?? '';
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

/* ---------------------------------------------------------------- flash */

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------------------------------------------------------- settings */

function settings(): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach (rows('SELECT k, v FROM settings') as $r) {
            $all[$r['k']] = $r['v'];
        }
    }
    return $all;
}

function setting(string $k, string $default = ''): string
{
    $s = settings();
    return isset($s[$k]) && $s[$k] !== '' ? $s[$k] : $default;
}

function save_setting(string $k, string $v): void
{
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', 'ss', [$k, $v]);
}

/** Opening hours as rows: [['days' => 'Mon to Fri', 'key' => 'mon-fri', 'open' => '9:00', 'close' => '17:00'], ...] */
function hours(): array
{
    $h = json_decode(setting('hours_json', '[]'), true);
    return is_array($h) ? $h : [];
}

/** The hours row that covers a date (Mon to Fri share one row), or null. */
function hours_for(DateTimeInterface $d): ?array
{
    $n   = (int) $d->format('N');
    $key = $n <= 5 ? 'mon-fri' : ($n === 6 ? 'sat' : 'sun');
    foreach (hours() as $h) {
        if (($h['days'] ?? '') === $key) {
            return $h;
        }
    }
    return null;
}

/** "9:00" -> minutes after midnight, or null. */
function hm(string $t): ?int
{
    return preg_match('/^(\d{1,2}):(\d{2})$/', trim($t), $m) ? (int) $m[1] * 60 + (int) $m[2] : null;
}

function is_open_day(DateTimeInterface $d): bool
{
    $h = hours_for($d);
    return $h !== null && !empty($h['open']);
}

/**
 * Whether a time of day can be booked on a date: the clinic must be open for more than an hour of it.
 * (Saturday 10:00 to 15:00 takes morning and midday, not afternoon.)
 */
function slot_fits(DateTimeInterface $d, string $slot): bool
{
    $h = hours_for($d);
    if (!$h || empty($h['open']) || !isset(TIME_SLOT_SPANS[$slot])) {
        return false;
    }
    [$s, $e] = TIME_SLOT_SPANS[$slot];
    $open  = hm($h['open']) ?? 0;
    $close = hm($h['close']) ?? 0;
    return min($e, $close) - max($s, $open) > 60;
}

/**
 * Whether the clinic is open right now, in plain words: ['open' => true, 'text' => 'Open now, until 17:00'].
 */
function clinic_status(): array
{
    $now = new DateTimeImmutable('now', new DateTimeZone(APP_TZ));
    $min = (int) $now->format('G') * 60 + (int) $now->format('i');
    $h   = hours_for($now);
    if ($h && !empty($h['open'])) {
        $o = hm($h['open']);
        $c = hm($h['close']);
        if ($o !== null && $c !== null && $min >= $o && $min < $c) {
            return ['open' => true, 'text' => 'Open now, until ' . $h['close']];
        }
        if ($o !== null && $min < $o) {
            return ['open' => false, 'text' => 'Closed now, opens at ' . $h['open'] . ' today'];
        }
    }
    for ($i = 1; $i <= 7; $i++) {
        $d = $now->modify("+$i day");
        $r = hours_for($d);
        if ($r && !empty($r['open'])) {
            return ['open' => false, 'text' => 'Closed now, opens ' . ($i === 1 ? 'tomorrow' : $d->format('l')) . ' at ' . $r['open']];
        }
    }
    return ['open' => false, 'text' => 'Closed now'];
}

/** Opening hours, short: "Mon–Fri 9:00–17:00 · Sat 10:00–15:00" */
function hours_compact(): string
{
    $parts = [];
    foreach (hours() as $h) {
        if (!empty($h['open'])) {
            $parts[] = str_replace(' to ', '–', $h['label']) . ' ' . $h['open'] . '–' . $h['close'];
        }
    }
    return implode(' · ', $parts);
}

/** Opening hours as "Mon to Fri 9:00 to 17:00, Sat 10:00 to 15:00" */
function hours_line(): string
{
    $parts = [];
    foreach (hours() as $h) {
        if (!empty($h['open'])) {
            $parts[] = $h['label'] . ' ' . $h['open'] . ' to ' . $h['close'];
        }
    }
    return implode(', ', $parts);
}

/* ---------------------------------------------------------------- contact links */

function phone_display(): string
{
    return setting('phone', '');
}

function phone_href(): string
{
    $p = preg_replace('/[^0-9+]/', '', setting('phone', ''));
    if ($p !== '' && $p[0] === '0') {
        $p = '+234' . substr($p, 1);
    }
    return 'tel:' . $p;
}

function wa_link(string $text = ''): string
{
    $n = preg_replace('/\D/', '', setting('whatsapp', ''));
    return 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function map_link(): string
{
    $q = trim(setting('street') . ' ' . setting('district') . ' ' . setting('city'));
    return 'https://www.openstreetmap.org/search?query=' . rawurlencode($q);
}

/* ---------------------------------------------------------------- throttle (public forms) */

function throttled(string $action): bool
{
    q('DELETE FROM throttle WHERE at < (NOW() - INTERVAL ? SECOND)', 'i', [THROTTLE_WINDOW]);
    $n = (int) val('SELECT COUNT(*) FROM throttle WHERE ip = ? AND action = ?', 'ss', [client_ip(), $action]);
    return $n >= THROTTLE_MAX;
}

function throttle_hit(string $action): void
{
    q('INSERT INTO throttle (ip, action, at) VALUES (?, ?, NOW())', 'ss', [client_ip(), $action]);
}

/* ---------------------------------------------------------------- validation */

function valid_phone(string $p): bool
{
    $digits = preg_replace('/\D/', '', $p);
    return strlen($digits) >= 10 && strlen($digits) <= 15;
}

function valid_email(string $m): bool
{
    return (bool) filter_var($m, FILTER_VALIDATE_EMAIL);
}

function clip(string $s, int $max): string
{
    return mb_substr($s, 0, $max);
}

/* ---------------------------------------------------------------- domain data */

const TIME_SLOTS = [
    'morning'   => 'Morning (9:00 to 12:00)',
    'midday'    => 'Midday (12:00 to 14:00)',
    'afternoon' => 'Afternoon (14:00 to 17:00)',
];

/** Each time of day as [start, end] in minutes after midnight. */
const TIME_SLOT_SPANS = [
    'morning'   => [540, 720],
    'midday'    => [720, 840],
    'afternoon' => [840, 1020],
];

const APPT_STATUSES = [
    'pending'   => 'Waiting to confirm',
    'confirmed' => 'Confirmed',
    'completed' => 'Seen',
    'cancelled' => 'Cancelled',
    'no_show'   => 'Did not come',
];

const CATEGORIES = [
    'frames'      => ['name' => 'Prescription frames', 'short' => 'Frames'],
    'sunglasses'  => ['name' => 'Sunglasses', 'short' => 'Sunglasses'],
    'safety'      => ['name' => 'Safety eyewear', 'short' => 'Safety'],
    'accessories' => ['name' => 'Cases, chains and cloths', 'short' => 'Accessories'],
];

const ENQUIRY_TYPES = [
    'contact'   => 'Message',
    'product'   => 'Shop question',
    'corporate' => 'Staff screening',
];

/** A short booking reference people can read out on the phone: CE-7K4Q2M */
function new_ref(): string
{
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $ref = 'CE-';
        for ($i = 0; $i < 6; $i++) {
            $ref .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
    } while (val('SELECT id FROM appointments WHERE ref = ?', 's', [$ref]));
    return $ref;
}

function services_list(bool $onlyActive = true): array
{
    return rows('SELECT * FROM services' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, id');
}

/** An image path from the database as a URL (handles both bundled img/ files and uploads/). */
function img_url(?string $path): string
{
    if (!$path) {
        return url('img/frames-wall.jpg');
    }
    return preg_match('~^https?://~', $path) ? $path : url($path);
}

function money(?string $amount): string
{
    if ($amount === null || $amount === '' || (float) $amount <= 0) {
        return '';
    }
    return '₦' . number_format((float) $amount, 0);
}

/* ---------------------------------------------------------------- form rendering (public site) */

/**
 * One labelled field with its error line under it.
 * $type: text|tel|email|date|textarea|select. $opts: options for select [value => label].
 */
function field(string $name, string $label, string $type, array $state, array $extra = []): string
{
    $id    = 'f-' . $name;
    $val   = $state['values'][$name] ?? '';
    $err   = $state['errors'][$name] ?? '';
    $req   = !empty($extra['required']);
    $attrs = ' id="' . e($id) . '" name="' . e($name) . '" aria-describedby="' . e($id) . '-err"';
    if ($req) {
        $attrs .= ' required';
    }
    if ($err !== '') {
        $attrs .= ' aria-invalid="true"';
    }
    foreach (['autocomplete', 'inputmode', 'min', 'max', 'maxlength', 'pattern'] as $a) {
        if (isset($extra[$a])) {
            $attrs .= ' ' . $a . '="' . e($extra[$a]) . '"';
        }
    }
    if ($type === 'textarea') {
        $input = '<textarea' . $attrs . ' rows="3">' . e($val) . '</textarea>';
    } elseif ($type === 'select') {
        $input = '<select' . $attrs . '>';
        if (!empty($extra['placeholder'])) {
            $input .= '<option value="">' . e($extra['placeholder']) . '</option>';
        }
        foreach ($extra['options'] ?? [] as $v => $l) {
            $input .= '<option value="' . e($v) . '"' . ((string) $v === (string) $val ? ' selected' : '') . '>' . e($l) . '</option>';
        }
        $input .= '</select>';
    } else {
        $input = '<input type="' . e($type) . '"' . $attrs . ' value="' . e($val) . '">';
    }
    $hint = $extra['error'] ?? '';
    $shown = $err !== '' ? $err : $hint;
    return '<div class="field' . (!empty($extra['full']) ? ' field--full' : '') . '"><label for="' . e($id) . '">' . e($label) . '</label>' . $input
        . '<p class="field__error" id="' . e($id) . '-err"' . ($err === '' ? ' hidden' : '') . '>' . e($shown) . '</p></div>';
}

/** The line under a form that says what to fix (and form-wide problems such as an expired form). */
function form_status(array $state): string
{
    $err = $state['errors'] ?? [];
    $msg = '';
    if (isset($err['form'])) {
        $msg = $err['form'];
    } elseif ($err) {
        $n = count($err);
        $msg = $n . ($n === 1 ? ' field' : ' fields') . ' to fix before we can send it.';
    }
    return '<p class="form__status" role="status" data-form-status' . ($msg === '' ? '' : ' data-error') . '>' . e($msg) . '</p>';
}

/** Honeypot: a field people never see; bots fill it. */
function honeypot(): string
{
    return '<div class="hp" aria-hidden="true"><label for="f-website">Leave this empty</label><input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>';
}

function is_bot(): bool
{
    return post('website') !== '';
}

/* ---------------------------------------------------------------- admin auth */

function current_admin(): ?array
{
    static $me = false;
    if ($me !== false) {
        return $me;
    }
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    $me = $id ? row('SELECT id, name, email, role FROM admins WHERE id = ? AND active = 1', 'i', [$id]) : null;
    return $me;
}

function require_admin(?string $role = null): array
{
    $me = current_admin();
    if (!$me) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('admin/login.php');
    }
    if ($role === 'owner' && $me['role'] !== 'owner') {
        http_response_code(403);
        exit('Only the clinic owner account can open this page.');
    }
    // idle timeout: 2 hours
    if (isset($_SESSION['admin_seen']) && time() - $_SESSION['admin_seen'] > 7200) {
        session_unset();
        session_destroy();
        redirect('admin/login.php?timeout=1');
    }
    $_SESSION['admin_seen'] = time();
    return $me;
}

/** Guards an admin POST: CSRF and login. */
function admin_post_guard(): void
{
    if (!csrf_ok()) {
        flash('error', 'The form expired. Please try again.');
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? url('admin/')), true, 303);
        exit;
    }
}

function time_ago(string $dt): string
{
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' h ago';
    if ($d < 86400 * 7) return floor($d / 86400) . ' d ago';
    return date('j M Y', strtotime($dt));
}

/* ---------------------------------------------------------------- public page bits */

const MAIN_ACTION = 'Book an eye test';

/** Opening hours as a definition list, with today's row marked (clinic time). */
function hours_dl(string $class = ''): string
{
    $today = hours_for(new DateTimeImmutable('now', new DateTimeZone(APP_TZ)));
    $out = '<dl class="hours' . ($class !== '' ? ' ' . e($class) : '') . '">';
    foreach (hours() as $h) {
        $is = $today && $today['days'] === $h['days'];
        $out .= '<div' . ($is ? ' class="is-today"' : '') . '><dt>' . e($h['label']) . ($is ? ' <span class="today-tag">Today</span>' : '') . '</dt><dd>'
            . (empty($h['open']) ? 'Closed' : e($h['open']) . '–' . e($h['close'])) . '</dd></div>';
    }
    return $out . '</dl>';
}

/** schema.org data for search engines: the clinic, its address, phone and hours. */
function clinic_jsonld(): string
{
    $days = ['mon-fri' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'sat' => ['Saturday'], 'sun' => ['Sunday']];
    $spec = [];
    foreach (hours() as $h) {
        if (!empty($h['open']) && isset($days[$h['days']])) {
            $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days[$h['days']],
                'opens' => str_pad($h['open'], 5, '0', STR_PAD_LEFT), 'closes' => str_pad($h['close'], 5, '0', STR_PAD_LEFT)];
        }
    }
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Optician',
        'name' => APP_NAME,
        'url' => abs_url(),
        'logo' => abs_url('img/mark.png'),
        'image' => abs_url('share.jpg'),
        'telephone' => preg_replace('/^tel:/', '', phone_href()),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => setting('street') . ', ' . setting('building'),
            'addressLocality' => setting('city'), 'addressRegion' => 'Rivers', 'addressCountry' => 'NG'],
        'openingHoursSpecification' => $spec,
    ];
    if (setting('email')) {
        $data['email'] = setting('email');
    }
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
}

/* ---------------------------------------------------------------- staff notifications */

/**
 * Emails the clinic about something sent from the website. Never blocks the visitor: a failure is only logged.
 * No visitor input goes into the headers, only into the plain-text body.
 */
function notify_staff(string $subject, array $lines, string $adminPath): void
{
    $to = NOTIFY_EMAIL !== '' ? NOTIFY_EMAIL : setting('notify_email', setting('email'));
    if ($to === '' || !valid_email($to) || !function_exists('mail')) {
        return;
    }
    $body = '';
    foreach ($lines as $k => $v) {
        $body .= (is_string($k) ? $k . ': ' : '') . str_replace(["\r\n", "\r"], "\n", (string) $v) . "\n";
    }
    $body .= "\nOpen it in the admin panel:\n" . abs_url($adminPath) . "\n";
    $host = parse_url(site_root(), PHP_URL_HOST) ?: 'localhost';
    $from = MAIL_FROM !== '' ? MAIL_FROM : 'website@' . preg_replace('/^www\./', '', $host);
    $headers = [
        'From' => APP_NAME . ' website <' . $from . '>',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer' => 'cedar',
    ];
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    if (!$ok) {
        error_log('[cedar] notification email to the clinic could not be sent: ' . $subject);
    }
}

/** The street and district, the one address the site prints. */
function address_html(): string
{
    return e(setting('street')) . ', ' . e(setting('district'));
}

/* ---------------------------------------------------------------- image thumbnails */

/**
 * A resized JPEG copy of a site image, made once with GD and cached in uploads/thumbs/<width>/.
 * Returns ['src' => url, 'w' => width, 'h' => height]; an image already that narrow comes back as it is.
 */
function thumb(string $path, int $w): array
{
    $file = realpath(__DIR__ . '/../' . ltrim($path, '/'));
    $root = realpath(__DIR__ . '/..');
    if (!$file || !str_starts_with($file, $root) || !($info = @getimagesize($file))) {
        return ['src' => img_url($path), 'w' => 0, 'h' => 0];
    }
    [$ow, $oh] = $info;
    if ($ow <= $w || !function_exists('imagecreatefromstring')) {
        return ['src' => img_url($path), 'w' => $ow, 'h' => $oh];
    }
    $h    = (int) round($oh * $w / $ow);
    $name = preg_replace('/[^a-z0-9]+/i', '-', pathinfo($path, PATHINFO_FILENAME)) . '-' . substr(md5($path . filemtime($file)), 0, 8) . '.jpg';
    $dir  = $root . '/uploads/thumbs/' . $w;
    $out  = $dir . '/' . $name;
    if (!is_file($out)) {
        $img = @imagecreatefromstring((string) file_get_contents($file));
        if (!$img) {
            return ['src' => img_url($path), 'w' => $ow, 'h' => $oh];
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $w, $h, $ow, $oh);
        imageinterlace($dst, true);
        @imagejpeg($dst, $out, 82);
        if (!is_file($out)) {
            return ['src' => img_url($path), 'w' => $ow, 'h' => $oh];
        }
    }
    return ['src' => url('uploads/thumbs/' . $w . '/' . $name), 'w' => $w, 'h' => $h];
}

/**
 * An <img> for a site image shown about $shown CSS pixels wide: a 1x and a 2x thumbnail, its width and height,
 * lazy unless $attrs says otherwise. $attrs is raw extra attributes (class, data-*, loading).
 */
function pic(string $path, string $alt, int $shown, string $attrs = '', string $sizes = ''): string
{
    // a photograph whose file has gone missing leaves an empty frame, not a broken image
    if ($path !== '' && !preg_match('~^https?://~', $path) && !is_file(__DIR__ . '/../' . ltrim($path, '/'))) {
        error_log('[cedar] missing image: ' . $path);
        return '';
    }
    $a = thumb($path, $shown);
    $b = thumb($path, $shown * 2);
    $srcset = $a['src'] . ' ' . ($a['w'] ?: $shown) . 'w';
    if ($b['src'] !== $a['src']) {
        $srcset .= ', ' . $b['src'] . ' ' . ($b['w'] ?: $shown * 2) . 'w';
    }
    $lazy = str_contains($attrs, 'loading=') || str_contains($attrs, 'fetchpriority') ? '' : ' loading="lazy"';
    return '<img src="' . e($a['src']) . '" srcset="' . e($srcset) . '" sizes="' . e($sizes ?: $shown . 'px') . '"'
        . ($a['w'] ? ' width="' . $a['w'] . '" height="' . $a['h'] . '"' : '')
        . ' alt="' . e($alt) . '" decoding="async"' . $lazy . ($attrs !== '' ? ' ' . $attrs : '') . '>';
}

/* ---------------------------------------------------------------- patient accounts */

/** Signs a patient in from an email and password. Returns the row, or null (and fills $error). */
function patient_authenticate(string $email, string $password, ?string &$error): ?array
{
    $p = row('SELECT * FROM patients WHERE email = ?', 's', [strtolower(trim($email))]);
    if (!$p || !password_verify($password, $p['password_hash'])) {
        $error = 'That email and password do not match an account.';
        return null;
    }
    if (password_needs_rehash($p['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE patients SET password_hash = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), $p['id']]);
    }
    return $p;
}

/**
 * Creates an account. Returns true when stored, filling $newPatient with the new row; or returns false with $errors filled.
 * $src is the raw input: name, email, phone, password, password2.
 */
function patient_register(array $src, array &$errors, ?array &$newPatient = null): bool
{
    $name  = clip((string) ($src['name'] ?? ''), 120);
    $email = strtolower(trim((string) ($src['email'] ?? '')));
    $phone = clip((string) ($src['phone'] ?? ''), 40);
    $pass  = (string) ($src['password'] ?? '');
    $pass2 = (string) ($src['password2'] ?? $pass);

    if ($name === '') $errors['name'] = 'Write your name.';
    if (!valid_email($email)) $errors['email'] = 'Write an email address with an @.';
    if ($email !== '' && val('SELECT id FROM patients WHERE email = ?', 's', [$email])) {
        $errors['email'] = 'An account already uses that email. Sign in instead, or reset the password.';
    }
    if (!valid_phone($phone)) $errors['phone'] = 'Write a phone number we can call, like 0803 123 4567.';
    if (strlen($pass) < 10) $errors['password'] = 'Use at least 10 characters.';
    if ($pass !== $pass2) $errors['password2'] = 'The two passwords are not the same.';
    if ($errors) {
        return false;
    }

    q('INSERT INTO patients (name, email, phone, password_hash) VALUES (?, ?, ?, ?)',
        'ssss', [$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
    $id = last_id();
    $newPatient = ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone];
    return true;
}

/* ---------------------------------------------------------------- password reset */

/** Starts a password reset: emails a one-hour link when the address has an account. */
function password_reset_start(string $email): void
{
    $p = row('SELECT id FROM patients WHERE email = ?', 's', [strtolower(trim($email))]);
    if (!$p) {
        return; // the same answer either way: never say whether an address is on file
    }
    $token = bin2hex(random_bytes(32));
    q('UPDATE patients SET reset_token = ?, reset_expires = NOW() + INTERVAL 1 HOUR WHERE id = ?',
        'si', [hash('sha256', $token), $p['id']]);
    $link = abs_url('reset.php?token=' . $token);
    send_mail($email, 'Reset your Cedar Eye Clinic password',
        "Someone asked to reset the password for your Cedar Eye Clinic account.\n\n"
        . "Open this link within the next hour to choose a new one:\n{$link}\n\n"
        . "If it was not you, ignore this message: nothing has changed.\n\nCedar Eye Clinic\n"
        . setting('street') . ', ' . setting('district') . ' · ' . phone_display());
}

/** Applies a reset token. Returns true when the password is now changed. */
function password_reset_finish(string $token, string $pass, string $pass2, array &$errors): bool
{
    $hash = hash('sha256', trim($token));
    $p = row('SELECT id FROM patients WHERE reset_token = ? AND reset_expires > NOW()', 's', [$hash]);
    if (!$p) {
        $errors['form'] = 'That reset link has expired or was already used. Ask for a new one.';
        return false;
    }
    if (strlen($pass) < 10) {
        $errors['password'] = 'Use at least 10 characters.';
        return false;
    }
    if ($pass !== $pass2) {
        $errors['password2'] = 'The two passwords are not the same.';
        return false;
    }
    q('UPDATE patients SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?',
        'si', [password_hash($pass, PASSWORD_DEFAULT), $p['id']]);
    return true;
}

/* ---------------------------------------------------------------- booking availability */

/** Bookings already held in a time of day on a date (waiting and confirmed count against it). */
function slot_taken(string $date, string $slot): int
{
    return (int) val(
        "SELECT COUNT(*) FROM appointments
         WHERE preferred_date = ? AND preferred_time = ? AND status IN ('pending','confirmed')",
        'ss', [$date, $slot]
    );
}

/**
 * Every time of day on a date, with what is left: whether it fits the opening hours of that day
 * and how many of the day's places are still free. Used by the wizard and api/slots.php.
 */
function slot_availability(string $date): array
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    $out = [];
    $h = $d ? hours_for($d) : null;
    $open = $d && is_open_day($d);

    foreach (TIME_SLOTS as $k => $label) {
        $fits   = $open && slot_fits($d, $k);
        $taken  = slot_taken($date, $k);
        $left   = max(0, BOOKING_PER_SLOT - $taken);
        $out[] = [
            'key'   => $k,
            'label' => $label,
            'open'  => (bool) $fits,
            'taken' => $taken,
            'left'  => $fits ? $left : 0,
            'full'  => $fits && $left === 0,
        ];
    }
    return [
        'date'    => $date,
        'valid'   => (bool) $d && $d->format('Y-m-d') === $date,
        'open'    => (bool) $open,
        'hours'   => $open && $h ? ['open' => $h['open'], 'close' => $h['close']] : null,
        'slots'   => $out,
        'free'    => array_sum(array_column($out, 'left')),
    ];
}

/* ---------------------------------------------------------------- outgoing mail */

/**
 * Sends one plain-text email. Returns false when it could not be sent (logged, never shown
 * to the visitor as a hard failure: the record in the database is what matters).
 */
function send_mail(string $to, string $subject, string $body): bool
{
    if (!valid_email($to) || !function_exists('mail')) {
        return false;
    }
    $host = parse_url(site_root(), PHP_URL_HOST) ?: 'localhost';
    $from = MAIL_FROM !== '' ? MAIL_FROM : 'website@' . preg_replace('/^www\./', '', $host);
    $headers = [
        'From' => APP_NAME . ' <' . $from . '>',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
        'X-Mailer' => 'cedar',
    ];
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    if (!$ok) {
        error_log('[cedar] email to ' . $to . ' could not be sent: ' . $subject);
    }
    return $ok;
}

// Bring an older database up to date (patient accounts, appointments.patient_id). It runs once:
// the version is a setting, so after the first page load of a new release there is nothing to do.
ensure_schema();
