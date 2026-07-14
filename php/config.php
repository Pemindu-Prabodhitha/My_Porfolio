<?php
/**
 * config.php
 * Central place for settings used by the backend scripts.
 * Edit these values for your own site before deploying.
 */

// The email address that should receive contact form submissions
define('CONTACT_RECEIVER_EMAIL', 'your.email@example.com');

// Shown as the "From" name in notification emails
define('SITE_NAME', 'Your Name Portfolio');

// Where submissions get logged as a backup (useful if mail() isn't configured,
// e.g. on localhost, or if the database is unavailable). Created automatically.
define('CONTACT_LOG_FILE', __DIR__ . '/../data/contact-log.txt');

// --- Database settings ---
// Fill these in after creating the database with database/schema.sql.
// If DB_ENABLED is false, or the connection fails for any reason, the site
// automatically falls back to data/projects.json for projects and to the
// text-file log for contact messages - so nothing breaks while you're
// setting the database up.
define('DB_ENABLED', true);
define('DB_HOST', 'sql306.infinityfree.com');
define('DB_NAME', 'if0_42406162_portfolio');
define('DB_USER', 'if0_42406162');
define('DB_PASS', 'pRojport13');
