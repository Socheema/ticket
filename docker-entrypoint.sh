#!/usr/bin/env bash
set -e

# Generate config.php at container start if it doesn't exist
if [ ! -f /var/www/html/config.php ]; then
    cat > /var/www/html/config.php <<'PHP'
<?php
// Auto-generated config.php from container entrypoint - reads runtime environment
// Override by adding a real config.php to the repo or by setting env vars.
define('APP_NAME', getenv('APP_NAME') ?: 'TicketFlow');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
// Allow APP_BASE to be empty (root) or a path like '/twig'
$envAppBase = getenv('APP_BASE');
if ($envAppBase !== false) {
    $appBase = $envAppBase === '' ? '' : rtrim($envAppBase, '/');
} else {
    $appBase = '';
}
define('APP_BASE', $appBase);

// DATA_DIR and file locations for zero-DB
$dataDir = getenv('DATA_DIR') ?: __DIR__;
if (!is_dir($dataDir)) { @mkdir($dataDir, 0755, true); }
define('DATA_DIR', $dataDir);
define('USERS_FILE', rtrim(DATA_DIR, '/\\') . DIRECTORY_SEPARATOR . 'users.json');
define('TICKETS_FILE', rtrim(DATA_DIR, '/\\') . DIRECTORY_SEPARATOR . 'ticket.json');

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

# Ensure data files and cache directory exist and are writable
mkdir -p /var/www/html/cache
chown -R www-data:www-data /var/www/html/cache || true
if [ -n "${DATA_DIR:-}" ]; then
    mkdir -p "${DATA_DIR}" || true
fi
if [ -f /var/www/html/users.json ]; then
    chown www-data:www-data /var/www/html/users.json || true
fi
if [ -f /var/www/html/ticket.json ]; then
    chown www-data:www-data /var/www/html/ticket.json || true
fi
# Ensure default files exist in webroot so PHP can read/write them immediately
if [ ! -f /var/www/html/users.json ]; then
    echo '[]' > /var/www/html/users.json || true
    chown www-data:www-data /var/www/html/users.json || true
fi
if [ ! -f /var/www/html/ticket.json ]; then
    echo '[]' > /var/www/html/ticket.json || true
    chown www-data:www-data /var/www/html/ticket.json || true
fi

# Exec the command (typically `apache2-foreground`)
exec "$@"
