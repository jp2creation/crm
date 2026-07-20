<?php

use App\Models\CrmCashCountLine;
use App\Models\CrmCashMovement;
use App\Models\CrmCashReceipt;
use App\Models\CrmCashReceiptArchive;
use App\Models\CrmCashRegisterDay;
use App\Models\CrmCheckRemittance;
use App\Models\CrmCheckRemittanceLine;
use App\Models\CrmDepositRequest;
use App\Models\CrmDocument;
use App\Models\CrmDocumentDirectory;
use App\Models\CrmEquipmentCategory;
use App\Models\CrmEquipmentItem;
use App\Models\CrmEquipmentRental;
use App\Models\CrmLeaveBalance;
use App\Models\CrmLeaveEmployee;
use App\Models\CrmLeaveEntry;
use App\Models\CrmLeaveStatusHistory;
use App\Models\CrmLeaveTransaction;
use App\Models\CrmMenuGroup;
use App\Models\CrmMenuItem;
use App\Models\CrmModule;
use App\Models\CrmPage;
use App\Models\CrmPermission;
use App\Models\CrmReservation;
use App\Models\CrmSalesTour;
use App\Models\CrmSalesVisit;
use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\CrmUserSiteModulePermission;
use App\Models\CrmVehicle;
use Illuminate\Http\Request;

$trustedProxies = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CRM_TRUSTED_PROXIES', ''))
)));

$trustedHosts = array_values(array_filter(array_map(
    static function (string $host): string {
        $host = trim($host);

        if ($host === '' || str_starts_with($host, '^')) {
            return $host;
        }

        return '^'.str_replace('\*', '.*', preg_quote($host, '#')).'$';
    },
    explode(',', (string) env('CRM_TRUSTED_HOSTS', ''))
)));

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
        'headers_enabled' => env('CRM_SECURITY_HEADERS_ENABLED', true),
        'frame_options' => env('CRM_SECURITY_FRAME_OPTIONS', 'DENY'),
        'referrer_policy' => env('CRM_SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('CRM_SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),
        'csp_enabled' => env('CRM_SECURITY_CSP_ENABLED', env('APP_ENV', 'production') === 'production'),
        'csp_report_only' => env('CRM_SECURITY_CSP_REPORT_ONLY', true),
        'csp' => env('CRM_SECURITY_CSP', "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; img-src 'self' data: blob:; font-src 'self' data: https://fonts.bunny.net; connect-src 'self'; worker-src 'self' blob:; manifest-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"),
        'trusted_proxies' => $trustedProxies,
        'trusted_proxy_headers' => Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_PREFIX
            | Request::HEADER_X_FORWARDED_AWS_ELB,
        'trusted_hosts' => $trustedHosts,
        'trusted_host_subdomains' => env('CRM_TRUSTED_HOST_SUBDOMAINS', true),
    ],

    'api' => [
        'throttle_per_minute' => (int) env('CRM_API_THROTTLE_PER_MINUTE', 120),
        'role_throttle_per_minute' => [
            'admin' => (int) env('CRM_API_THROTTLE_ADMIN_PER_MINUTE', 200),
            'responsable' => (int) env('CRM_API_THROTTLE_RESPONSABLE_PER_MINUTE', env('CRM_API_THROTTLE_MANAGER_PER_MINUTE', 100)),
            'manager' => (int) env('CRM_API_THROTTLE_MANAGER_PER_MINUTE', 100),
            'user' => (int) env('CRM_API_THROTTLE_USER_PER_MINUTE', 80),
            'employee' => (int) env('CRM_API_THROTTLE_EMPLOYEE_PER_MINUTE', 60),
            'blocked' => (int) env('CRM_API_THROTTLE_BLOCKED_PER_MINUTE', 30),
            'guest' => (int) env('CRM_API_THROTTLE_GUEST_PER_MINUTE', 60),
        ],
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
        'keep_weekly' => (int) env('CRM_BACKUP_KEEP_WEEKLY', 8),
        'keep_monthly' => (int) env('CRM_BACKUP_KEEP_MONTHLY', 12),
        'dump_binary' => env('CRM_BACKUP_DUMP_BINARY'),
        'dump_options' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('CRM_BACKUP_DUMP_OPTIONS', ''))),
        )),
        'gzip_level' => (int) env('CRM_BACKUP_GZIP_LEVEL', 6),
        'timeout_seconds' => (int) env('CRM_BACKUP_TIMEOUT_SECONDS', 0),
        'verify' => env('CRM_BACKUP_VERIFY', true),
        'encrypt' => env('CRM_BACKUP_ENCRYPT', false),
        'encryption_key' => env('CRM_BACKUP_ENCRYPTION_KEY'),
    ],

    'archive' => [
        'retention_years' => max(1, (int) env('CRM_ARCHIVE_RETENTION_YEARS', 2)),
        'batch_size' => max(1, (int) env('CRM_ARCHIVE_BATCH_SIZE', 500)),
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
        'channels' => array_values(array_filter(
            array_map('trim', explode(',', (string) env('CRM_NOTIFICATION_CHANNELS', 'database,mail,pwa'))),
        )),
    ],

    'audit' => [
        'enabled' => env('CRM_AUDIT_ENABLED', true),
        'models' => [
            CrmCashCountLine::class,
            CrmCashMovement::class,
            CrmCashReceipt::class,
            CrmCashReceiptArchive::class,
            CrmCashRegisterDay::class,
            CrmCheckRemittance::class,
            CrmCheckRemittanceLine::class,
            CrmDepositRequest::class,
            CrmDocument::class,
            CrmDocumentDirectory::class,
            CrmEquipmentCategory::class,
            CrmEquipmentItem::class,
            CrmEquipmentRental::class,
            CrmLeaveBalance::class,
            CrmLeaveEmployee::class,
            CrmLeaveEntry::class,
            CrmLeaveStatusHistory::class,
            CrmLeaveTransaction::class,
            CrmMenuGroup::class,
            CrmMenuItem::class,
            CrmModule::class,
            CrmPage::class,
            CrmPermission::class,
            CrmReservation::class,
            CrmSalesTour::class,
            CrmSalesVisit::class,
            CrmSite::class,
            CrmUser::class,
            CrmUserSiteModulePermission::class,
            CrmVehicle::class,
        ],
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
