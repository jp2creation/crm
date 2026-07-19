<?php

return [
    'legacy_php_api' => [
        'enabled' => env('CRM_LEGACY_PHP_API_ENABLED', env('APP_ENV', 'production') !== 'production'),
        'log_calls' => env('CRM_LEGACY_PHP_API_LOG', true),
        'throttle_per_minute' => (int) env('CRM_LEGACY_PHP_API_THROTTLE_PER_MINUTE', 60),
    ],

    'security' => [
        'force_https' => env('CRM_FORCE_HTTPS', env('APP_ENV', 'production') === 'production'),
        'hsts_enabled' => env('CRM_HSTS_ENABLED', env('APP_ENV', 'production') === 'production'),
        'hsts_max_age' => (int) env('CRM_HSTS_MAX_AGE', 31536000),
        'hsts_include_subdomains' => env('CRM_HSTS_INCLUDE_SUBDOMAINS', true),
        'hsts_preload' => env('CRM_HSTS_PRELOAD', true),
    ],

    'api' => [
        'throttle_per_minute' => (int) env('CRM_API_THROTTLE_PER_MINUTE', 120),
    ],

    'dashboard' => [
        'cache_seconds' => (int) env('CRM_DASHBOARD_CACHE_SECONDS', 300),
        'metrics_enabled' => env('CRM_DASHBOARD_METRICS_ENABLED', true),
    ],

    'leaves' => [
        'default_entitlement_days' => (float) env('CRM_LEAVE_DEFAULT_ENTITLEMENT_DAYS', 25),
        'exclude_weekends' => env('CRM_LEAVE_EXCLUDE_WEEKENDS', false),
        'enforce_balances' => env('CRM_LEAVE_ENFORCE_BALANCES', false),
    ],

    'queue' => [
        'alert_threshold' => (int) env('CRM_QUEUE_ALERT_THRESHOLD', 1000),
        'alert_cooldown_minutes' => (int) env('CRM_QUEUE_ALERT_COOLDOWN_MINUTES', 30),
    ],

    'login' => [
        'throttle_per_minute' => (int) env('CRM_LOGIN_THROTTLE_PER_MINUTE', 10),
    ],

    'compression' => [
        'enabled' => env('CRM_RESPONSE_COMPRESSION_ENABLED', true),
        'min_bytes' => (int) env('CRM_RESPONSE_COMPRESSION_MIN_BYTES', 1024),
        'level' => (int) env('CRM_RESPONSE_COMPRESSION_LEVEL', 6),
    ],

    'backup' => [
        'disk' => env('CRM_BACKUP_DISK', 'local'),
        'path' => env('CRM_BACKUP_PATH', 'backups/database'),
        'keep' => (int) env('CRM_BACKUP_KEEP', 14),
    ],

    'cash_control' => [
        'archive_after_years' => max(1, (int) env('CRM_CASH_RECEIPT_ARCHIVE_AFTER_YEARS', 3)),
        'invoice_reminder_days' => array_values(array_filter(
            array_map(
                static fn (string $day): int => (int) trim($day),
                explode(',', (string) env('CRM_INVOICE_REMINDER_DAYS', '15,30')),
            ),
            static fn (int $day): bool => $day > 0,
        )),
    ],

    'notifications' => [
        'locale' => env('CRM_NOTIFICATION_LOCALE', env('APP_LOCALE', 'fr')),
    ],

    'admin_password' => [
        'min_length' => (int) env('CRM_ADMIN_PASSWORD_MIN', 12),
        'hash_rounds' => (int) env('CRM_ADMIN_HASH_ROUNDS', env('BCRYPT_ROUNDS', 12)),
    ],

    'display_timezone' => env('CRM_DISPLAY_TIMEZONE', 'Europe/Paris'),

    'assets' => [
        'version' => env('CRM_ASSET_VERSION'),
    ],

    'check_ocr' => [
        'enabled' => env('CRM_CHECK_OCR_ENABLED', true),
        'python' => env('CRM_CHECK_OCR_PYTHON', 'python3'),
        'script' => env('CRM_CHECK_OCR_SCRIPT') ?: base_path('scripts/check_ocr.py'),
        'timeout' => (int) env('CRM_CHECK_OCR_TIMEOUT', 45),
    ],
];
