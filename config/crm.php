<?php

return [
    'legacy_php_api' => [
        'enabled' => env('CRM_LEGACY_PHP_API_ENABLED', true),
        'log_calls' => env('CRM_LEGACY_PHP_API_LOG', true),
        'throttle_per_minute' => (int) env('CRM_LEGACY_PHP_API_THROTTLE_PER_MINUTE', 60),
    ],

    'admin_password' => [
        'min_length' => (int) env('CRM_ADMIN_PASSWORD_MIN', 12),
        'hash_rounds' => (int) env('CRM_ADMIN_HASH_ROUNDS', env('BCRYPT_ROUNDS', 12)),
    ],

    'display_timezone' => env('CRM_DISPLAY_TIMEZONE', 'Europe/Paris'),

    'assets' => [
        'version' => env('CRM_ASSET_VERSION'),
    ],
];
