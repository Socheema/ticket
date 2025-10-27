<?php
// Copy this file to `config.php` and fill in your values (do NOT commit config.php)

return [
    // Database connection
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_name' => getenv('DB_NAME') ?: 'twig_db',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '',

    // App base path (use empty string for root, or '/twig' if hosted in a subdirectory)
    'app_base' => getenv('APP_BASE') ?: '/twig',

    // Session settings (override as needed)
    'session_cookie_secure' => getenv('SESSION_COOKIE_SECURE') !== false ? (bool)getenv('SESSION_COOKIE_SECURE') : false,
];
