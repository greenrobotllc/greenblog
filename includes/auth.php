<?php
/**
 * GreenBlog Authentication System
 *
 * This file handles user authentication and session management.
 */

// Prevent direct access
if (!defined('GREENBLOG')) {
    die('Direct access not permitted');
}

/**
 * Start a secure session
 *
 * @return void
 */
function startSecureSession() {
    // Set secure session parameters

    // Set session cookie parameters
    $lifetime = SESSION_DURATION;
    $path = '/';
    $domain = '';  // Use current domain
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;

    // Use the traditional method with individual parameters
    session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);

    // Set additional session parameters
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_DURATION);

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration']) ||
        (time() - $_SESSION['last_regeneration']) > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Hash a password
 *
 * @param string $password Password to hash
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash
 *
 * @param string $password Password to verify
 * @param string $hash Hash to verify against
 * @return bool True if password matches hash, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Authenticate a user
 *
 * @param string $username Username
 * @param string $password Password
 * @return bool True if authentication successful, false otherwise
 */
function authenticateUser($username, $password) {
    // Get user from database
    $user = getRow("SELECT * FROM users WHERE username = ?", [$username]);

    if (!$user) {
        // User not found
        return false;
    }

    // Check if account is locked
    if (isset($user['login_attempts']) && $user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $lockTime = strtotime($user['last_login_attempt']) + LOGIN_TIMEOUT;

        if (time() < $lockTime) {
            // Account is locked
            return false;
        }

        // Reset login attempts if lock time has passed
        updateRecord('users', ['login_attempts' => 0], 'id = ?', [$user['id']]);
    }

    // Verify password
    if (verifyPassword($password, $user['password'])) {
        // Reset login attempts on successful login
        updateRecord('users', [
            'login_attempts' => 0,
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();

        return true;
    } else {
        // Increment login attempts
        $attempts = isset($user['login_attempts']) ? $user['login_attempts'] + 1 : 1;
        updateRecord('users', [
            'login_attempts' => $attempts,
            'last_login_attempt' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);

        return false;
    }
}

/**
 * Check if user is logged in
 *
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['login_time']) &&
           (time() - $_SESSION['login_time'] < SESSION_DURATION);
}

/**
 * Check if user has admin role
 *
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current user ID
 *
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get current username
 *
 * @return string|null Username or null if not logged in
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION['username'] : null;
}

/**
 * Logout current user
 *
 * @return void
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];

    // Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy the session
    session_destroy();
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token Token to verify
 * @return bool True if token is valid, false otherwise
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
