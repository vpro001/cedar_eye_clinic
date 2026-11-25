<?php
/**
 * Schema upgrades for an existing database. install.php builds a fresh one from database.sql;
 * this brings an older one up to date. It runs itself once: the version is a setting, so after
 * the first page load of a new release there is nothing left to do.
 */

const SCHEMA_VERSION = 2;

function ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $have = setting('schema_version');
        if ($have !== '' && (int) $have >= SCHEMA_VERSION) {
            return;
        }
        $db = db();

        // v2: patient accounts. People sign in, and their bookings collect in one place.
        $db->query('CREATE TABLE IF NOT EXISTS patients (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(40) NOT NULL DEFAULT "",
            password_hash VARCHAR(255) NOT NULL,
            reset_token CHAR(64) NULL,
            reset_expires DATETIME NULL,
            last_login DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $col = $db->query("SHOW COLUMNS FROM appointments LIKE 'patient_id'");
        if ($col && $col->num_rows === 0) {
            $db->query('ALTER TABLE appointments
                ADD COLUMN patient_id INT UNSIGNED NULL AFTER ref,
                ADD KEY idx_patient (patient_id)');
        }

        save_setting('schema_version', (string) SCHEMA_VERSION);
    } catch (Throwable $e) {
        // Never take the site down over an upgrade: log it and carry on with the old schema.
        error_log('[cedar] schema upgrade failed: ' . $e->getMessage());
    }
}
