FROM php:8.2-apache

# Install system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy composer files for caching
COPY composer.json composer.lock /var/www/html/

# Install composer from the official composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader || true

# Copy application code
COPY . /var/www/html

# If a `config.php` wasn't committed to the repo (it's purposely ignored),
# generate a minimal runtime `config.php` from environment variables so the
# container has the expected constants and session initialization.
RUN if [ ! -f /var/www/html/config.php ]; then \
    cat > /var/www/html/config.php <<'PHP'
<?php
// Auto-generated config.php from Docker build - reads runtime environment
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
fi

# Ensure cache directory exists and is writable
RUN mkdir -p /var/www/html/cache && chown -R www-data:www-data /var/www/html/cache

# Generate optimized autoload (run again after copying project files)
RUN composer dump-autoload --optimize || true

EXPOSE 80
CMD ["apache2-foreground"]
