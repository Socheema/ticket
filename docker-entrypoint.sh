#!/usr/bin/env bash
set -e

# Generate config.php at container start if it doesn't exist
if [ ! -f /var/www/html/config.php ]; then
    cat > /var/www/html/config.php <<'PHP'
<?php
// Auto-generated config.php from container entrypoint - reads runtime environment
// Override by adding a real config.php to the repo or by setting env vars.
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'ticketflow';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

define('APP_NAME', getenv('APP_NAME') ?: 'TicketFlow');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
// Allow APP_BASE to be empty (root) or a path like '/twig'
$envAppBase = getenv('APP_BASE');
if ($envAppBase !== false) {
    $appBase = $envAppBase === '' ? '' : rtrim($envAppBase, '/');
} else {
    // best-effort auto-detect (may be overridden at runtime)
    $appBase = '';
}
define('APP_BASE', $appBase);

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    $secureEnv = getenv('SESSION_COOKIE_SECURE');
    $cookieSecure = false;
    if ($secureEnv !== false) {
        $cookieSecure = filter_var($secureEnv, FILTER_VALIDATE_BOOLEAN);
    }
    ini_set('session.cookie_secure', $cookieSecure ? 1 : 0);
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
PHP
    echo "Generated /var/www/html/config.php from environment variables"
fi

# Ensure cache directory exists and is writable
mkdir -p /var/www/html/cache
chown -R www-data:www-data /var/www/html/cache || true

# Exec the command (typically `apache2-foreground`)
exec "$@"
