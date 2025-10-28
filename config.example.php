<?php
// Copy this file to `config.php` and fill in your values (do NOT commit config.php)

return [
    // App base path. Recommended: leave unset to auto-detect at runtime.
    // Use empty string for root, or '/twig' if hosted in a subdirectory.
    'app_base' => getenv('APP_BASE') !== false ? getenv('APP_BASE') : '',

    // DATA_DIR: directory where `users.json` and `ticket.json` will be stored.
    'data_dir' => getenv('DATA_DIR') !== false ? getenv('DATA_DIR') : '',

    // Session settings (override as needed)
    'session_cookie_secure' => getenv('SESSION_COOKIE_SECURE') !== false ? (bool)getenv('SESSION_COOKIE_SECURE') : false,
];
