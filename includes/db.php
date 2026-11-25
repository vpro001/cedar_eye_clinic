<?php
/**
 * One mysqli connection for the request, and small prepared-statement helpers.
 * Every query that takes outside input goes through q() with bound parameters.
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db(): mysqli
{
    static $link = null;
    if ($link instanceof mysqli) {
        return $link;
    }
    try {
        $link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $link->set_charset('utf8mb4');
        $link->query("SET time_zone = '+01:00'");
    } catch (mysqli_sql_exception $e) {
        $link = null;
        error_log('[cedar] database connection failed: ' . $e->getMessage());
        fatal_page(503, 'Back shortly', APP_DEBUG
            ? 'Database connection failed: ' . $e->getMessage()
            : 'The clinic website is down for a few minutes. Please call or WhatsApp the clinic, or try again shortly.');
    }
    return $link;
}

/**
 * Run a prepared statement. $types is the mysqli bind string ("ssi"), $params the values.
 * Returns the mysqli_result for SELECTs, true otherwise.
 */
function q(string $sql, string $types = '', array $params = [])
{
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return $res === false ? true : $res;
}

/** All rows as associative arrays. */
function rows(string $sql, string $types = '', array $params = []): array
{
    $res = q($sql, $types, $params);
    return $res instanceof mysqli_result ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/** The first row, or null. */
function row(string $sql, string $types = '', array $params = []): ?array
{
    $res = q($sql, $types, $params);
    if (!$res instanceof mysqli_result) {
        return null;
    }
    $r = $res->fetch_assoc();
    return $r ?: null;
}

/** The first column of the first row, or null. */
function val(string $sql, string $types = '', array $params = [])
{
    $r = row($sql, $types, $params);
    return $r ? reset($r) : null;
}

function last_id(): int
{
    return (int) db()->insert_id;
}
