<?php
/**
 * auth.php
 * Session handling, login verification, and CSRF token helpers for the
 * admin panel. Every protected admin page should require this file and
 * call require_login() before rendering anything.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Basic session hardening
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

require_once __DIR__ . '/../db.php';

/**
 * Redirects to the login page unless an admin is currently logged in.
 * Call this at the top of every protected admin page.
 */
function require_login() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Checks a username/password against the admin_users table.
 * Returns the admin's id on success, or false on failure.
 */
function verify_admin_login($username, $password) {
    $pdo = get_db_connection();
    if ($pdo === null) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user['id'];
        }
    } catch (PDOException $e) {
        error_log('Admin login query failed: ' . $e->getMessage());
    }

    return false;
}

/**
 * Returns the CSRF token for the current session, generating one if needed.
 * Include this as a hidden field in every admin form:
 *   <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a submitted CSRF token against the one stored in the session.
 * Call this at the top of every POST handler that changes data.
 */
function verify_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

/**
 * Small helper to escape output safely in admin templates.
 */
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
