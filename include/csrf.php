<?php
/**
 * CSRF Protection Helper for CarGO Application (Phase 2.2)
 *
 * Provides cryptographically secure token generation, constant-time validation,
 * and safe session handling without duplicate session_start notices.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Returns the current session's CSRF token, generating one if not present.
 * Uses cryptographically secure random bytes.
 *
 * @return string 64-character hexadecimal token
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted token against the session token using constant-time comparison.
 *
 * @param string|null $token Submitted token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    if (empty($token) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to verify the CSRF token from $_POST['csrf_token'].
 *
 * @return bool True if valid, false otherwise
 */
function verify_csrf_token(): bool {
    $token = $_POST['csrf_token'] ?? null;
    return validate_csrf_token($token);
}
