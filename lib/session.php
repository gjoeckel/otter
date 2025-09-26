<?php
// Centralized session configuration
function initializeSession() {
    // Set cache directory
    $cache_dir = __DIR__ . '/../cache';
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0777, true);
    }

    // Only modify session settings if session is not already active
    if (session_status() === PHP_SESSION_NONE) {
        // Check if headers have already been sent (prevents warnings)
        if (!headers_sent()) {
            // Set session path
            ini_set('session.save_path', $cache_dir);

            // Set session cookie parameters
            $cookie_params = [
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false, // Allow non-HTTPS in local development
                'httponly' => true,
                'samesite' => 'Lax'
            ];

            session_set_cookie_params($cookie_params);
        }

        // Start session (suppress warnings if headers already sent)
        @session_start();
    }
    // If session is already active, just ensure it exists (no warnings)
}

// Check if user is authenticated
function isAuthenticated() {
    return isset($_SESSION['home_authenticated']) && $_SESSION['home_authenticated'] === true;
}

// Set authentication status
function setAuthenticated($status = true) {
    $_SESSION['home_authenticated'] = $status;
}

// Clear authentication
function clearAuthentication() {
    unset($_SESSION['home_authenticated']);
}