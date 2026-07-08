<?php
declare(strict_types=1);

function crm_api_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    static $env = null;

    if ($env === null) {
        $env = [];
        $path = dirname(__DIR__, 2) . '/.env';

        if (is_readable($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                    continue;
                }

                [$name, $rawValue] = explode('=', $line, 2);
                $name = trim($name);
                $rawValue = trim($rawValue);

                if (
                    (str_starts_with($rawValue, '"') && str_ends_with($rawValue, '"'))
                    || (str_starts_with($rawValue, "'") && str_ends_with($rawValue, "'"))
                ) {
                    $rawValue = substr($rawValue, 1, -1);
                }

                $env[$name] = $rawValue;
            }
        }
    }

    return $env[$key] ?? $default;
}

function crm_api_bool(string $key, bool $default = false): bool
{
    $value = crm_api_env($key);

    if ($value === null || $value === '') {
        return $default;
    }

    return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
}

function crm_api_debug(): bool
{
    return crm_api_bool('CRM_API_DEBUG', false) || crm_api_bool('APP_DEBUG', false);
}

function crm_api_legacy_actor_enabled(): bool
{
    return crm_api_bool('CRM_ALLOW_LEGACY_ACTOR_IMPERSONATION', false);
}

function crm_api_legacy_actor_header_enabled(): bool
{
    return crm_api_legacy_actor_enabled()
        && crm_api_bool('CRM_ALLOW_LEGACY_ACTOR_HEADER', false);
}

function crm_api_legacy_actor_body_enabled(): bool
{
    return crm_api_legacy_actor_enabled()
        && crm_api_bool('CRM_ALLOW_LEGACY_ACTOR_BODY', false);
}

function crm_api_laravel_session_enabled(): bool
{
    return crm_api_bool('CRM_TRUST_LARAVEL_SESSION', true);
}

function crm_api_actor_id(array $body = []): int
{
    if (! empty($_SESSION['crm_user_id'])) {
        return (int) $_SESSION['crm_user_id'];
    }

    $authenticatedCrmUserId = crm_api_authenticated_crm_user_id();

    if ($authenticatedCrmUserId > 0) {
        return $authenticatedCrmUserId;
    }

    if (crm_api_legacy_actor_header_enabled() && ! empty($_SERVER['HTTP_X_CRM_USER_ID'])) {
        return (int) $_SERVER['HTTP_X_CRM_USER_ID'];
    }

    if (crm_api_legacy_actor_body_enabled() && ! empty($body['actorUserId'])) {
        return (int) $body['actorUserId'];
    }

    return 0;
}

function crm_api_authenticated_crm_user_id(): int
{
    $laravelUserId = crm_api_laravel_user_id_from_session();

    if ($laravelUserId <= 0) {
        return 0;
    }

    try {
        $pdo = crm_api_db();
        $stmt = $pdo->prepare('SELECT id FROM crm_users WHERE user_id = ? AND active = 1 LIMIT 1');
        $stmt->execute([$laravelUserId]);

        return (int) ($stmt->fetchColumn() ?: 0);
    } catch (Throwable $error) {
        error_log('[crm-api-auth] ' . $error->getMessage());

        return 0;
    }
}

function crm_api_laravel_user_id_from_session(): int
{
    if (! crm_api_laravel_session_enabled()) {
        return 0;
    }

    $sessionId = crm_api_laravel_session_id();

    if ($sessionId === '') {
        return 0;
    }

    try {
        $lifetime = max(1, (int) crm_api_env('SESSION_LIFETIME', '120'));
        $activeAfter = time() - ($lifetime * 60);
        $table = crm_api_identifier(crm_api_env('SESSION_TABLE', 'sessions') ?: 'sessions');
        $pdo = crm_api_db();
        $stmt = $pdo->prepare("SELECT user_id FROM `{$table}` WHERE id = ? AND user_id IS NOT NULL AND last_activity >= ? LIMIT 1");
        $stmt->execute([$sessionId, $activeAfter]);

        return (int) ($stmt->fetchColumn() ?: 0);
    } catch (Throwable $error) {
        error_log('[crm-api-session] ' . $error->getMessage());

        return 0;
    }
}

function crm_api_laravel_session_id(): string
{
    $cookieName = crm_api_session_cookie_name();
    $cookie = $_COOKIE[$cookieName] ?? '';

    if (! is_string($cookie) || $cookie === '') {
        return '';
    }

    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

    if (! is_readable($autoload)) {
        return '';
    }

    require_once $autoload;

    try {
        $key = crm_api_laravel_key();
        $cipher = crm_api_env('APP_CIPHER', 'AES-256-CBC') ?: 'AES-256-CBC';
        $encrypter = new Illuminate\Encryption\Encrypter($key, $cipher);
        $decrypted = $encrypter->decrypt($cookie, false);

        if (! is_string($decrypted) || $decrypted === '') {
            return '';
        }

        if (class_exists(Illuminate\Cookie\CookieValuePrefix::class)) {
            $validated = Illuminate\Cookie\CookieValuePrefix::validate(
                $cookieName,
                $decrypted,
                $encrypter->getAllKeys(),
            );

            return is_string($validated) ? $validated : '';
        }

        return $decrypted;
    } catch (Throwable $error) {
        error_log('[crm-api-cookie] ' . $error->getMessage());

        return '';
    }
}

function crm_api_session_cookie_name(): string
{
    $configured = crm_api_env('SESSION_COOKIE');

    if (is_string($configured) && $configured !== '' && strtolower($configured) !== 'null') {
        return $configured;
    }

    $appName = crm_api_env('APP_NAME', 'laravel') ?: 'laravel';
    $slug = strtolower(trim((string) $appName));

    if (function_exists('iconv')) {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
    }

    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: 'laravel';

    return trim($slug, '-') . '-session';
}

function crm_api_laravel_key(): string
{
    $key = crm_api_env('APP_KEY', '') ?: '';

    if (str_starts_with($key, 'base64:')) {
        $decoded = base64_decode(substr($key, 7), true);

        if ($decoded !== false) {
            return $decoded;
        }
    }

    return $key;
}

function crm_api_db(): PDO
{
    $config = crm_api_private_config();
    $db = $config['db'] ?? [];
    $host = $db['host'] ?? 'localhost';
    $port = (int) ($db['port'] ?? 3306);
    $name = $db['database'] ?? '';
    $charset = $db['charset'] ?? 'utf8mb4';
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    return new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function crm_api_private_config(): array
{
    $files = [
        dirname(__DIR__, 3) . '/crm_private/config.php',
        dirname(__DIR__, 2) . '/crm_private/config.php',
    ];

    foreach ($files as $file) {
        if (is_file($file)) {
            $config = require $file;

            return is_array($config) ? $config : [];
        }
    }

    return [];
}

function crm_api_identifier(string $identifier): string
{
    return preg_match('/^[A-Za-z0-9_]+$/', $identifier) ? $identifier : 'sessions';
}

function crm_api_exception_response(Throwable $error): array
{
    error_log('[crm-api] ' . $error);

    if (crm_api_debug()) {
        return ['ok' => false, 'error' => $error->getMessage()];
    }

    return ['ok' => false, 'error' => 'Erreur interne. Consulte les logs serveur.'];
}
