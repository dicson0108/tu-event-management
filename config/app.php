<?php
// URL prefix for this installation. Use an empty string for a domain-root install.
const APP_BASE_PATH = '/TUEventManagement';

function app_url(string $path): string {
    // Accept paths already prefixed by the application (for login return URLs).
    if ($path === APP_BASE_PATH || str_starts_with($path, APP_BASE_PATH . '/')) {
        return $path;
    }
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

require_once __DIR__ . '/../includes/error.php';
if (PHP_SAPI !== 'cli') {
    ob_start();
    set_exception_handler('handle_app_exception');
}
