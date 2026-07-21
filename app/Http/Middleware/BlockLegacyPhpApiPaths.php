<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BlockLegacyPhpApiPaths
{
    public const LOG_ACTION = 'legacy php api blocked';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->candidatePaths($request) as $path) {
            if ($this->isLegacyPhpApiPath($path)) {
                $this->logLegacyAttempt($request, $path, 'request_path');

                abort(404);
            }
        }

        $response = $next($request);

        if ($this->isApiPermanentRedirect($response)) {
            $locationPath = parse_url((string) $response->headers->get('Location', ''), PHP_URL_PATH);
            $this->logLegacyAttempt(
                $request,
                is_string($locationPath) ? trim(rawurldecode($locationPath), '/') : 'api/redirect',
                'api_permanent_redirect',
                ['status' => $response->getStatusCode()],
            );

            abort(404);
        }

        return $response;
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(Request $request): array
    {
        $paths = [trim($request->path(), '/')];

        foreach ([
            'REQUEST_URI',
            'REDIRECT_URL',
            'UNENCODED_URL',
            'ORIG_PATH_INFO',
            'SCRIPT_URL',
            'SCRIPT_URI',
            'PHP_SELF',
            'SCRIPT_NAME',
            'PATH_INFO',
            'REQUEST_FILENAME',
            'HTTP_X_ORIGINAL_URL',
            'HTTP_X_REWRITE_URL',
        ] as $serverKey) {
            $value = (string) $request->server($serverKey, '');
            $this->appendParsedPath($paths, $value);
        }

        $requestLine = (string) $request->server('THE_REQUEST', '');

        if (preg_match('#\s+(\S+)#', $requestLine, $matches) === 1) {
            $this->appendParsedPath($paths, $matches[1]);
        }

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * @param  list<string>  $paths
     */
    private function appendParsedPath(array &$paths, string $value): void
    {
        if ($value === '') {
            return;
        }

        $parsedPath = parse_url($value, PHP_URL_PATH);

        if (is_string($parsedPath)) {
            $paths[] = trim(rawurldecode($parsedPath), '/');
        }
    }

    private function isLegacyPhpApiPath(string $path): bool
    {
        return preg_match('#(?:^|/)api/[^?]*\.php$#i', $path) === 1;
    }

    private function isApiPermanentRedirect(Response $response): bool
    {
        if ($response->getStatusCode() !== Response::HTTP_PERMANENTLY_REDIRECT) {
            return false;
        }

        $locationPath = parse_url((string) $response->headers->get('Location', ''), PHP_URL_PATH);

        return is_string($locationPath) && str_starts_with(trim($locationPath, '/'), 'api/');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function logLegacyAttempt(Request $request, string $matchedPath, string $reason, array $extra = []): void
    {
        try {
            if (! Schema::hasTable('crm_logs')) {
                return;
            }

            $path = '/'.ltrim($matchedPath, '/');
            $userAgent = (string) $request->userAgent();
            $row = [
                'user_id' => null,
                'user_name' => null,
                'action' => self::LOG_ACTION,
                'details' => $request->method().' '.$path,
                'created_at' => now(),
                'ip' => mb_substr((string) ($request->ip() ?? ''), 0, 80),
            ];

            if (Schema::hasColumn('crm_logs', 'user_agent')) {
                $row['user_agent'] = $userAgent !== '' ? mb_substr($userAgent, 0, 512) : null;
            }

            if (Schema::hasColumn('crm_logs', 'context')) {
                $row['context'] = json_encode([
                    'request' => [
                        'method' => $request->method(),
                        'path' => '/'.ltrim($request->path(), '/'),
                        'matchedPath' => $path,
                        'query' => $this->sanitize($request->query->all()),
                        'ip' => $request->ip(),
                        'userAgent' => $request->userAgent(),
                        'referer' => $request->headers->get('referer'),
                    ],
                    'legacy' => [
                        'reason' => $reason,
                        ...$this->sanitize($extra),
                    ],
                ], JSON_UNESCAPED_UNICODE) ?: '{}';
            }

            DB::table('crm_logs')->insert($row);
        } catch (Throwable) {
            // The blocker must never become a legacy endpoint availability risk.
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitize(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = mb_strtolower((string) $key);

            if (preg_match('/password|token|secret|signature|key/', $normalizedKey) === 1) {
                $sanitized[$key] = '[filtered]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);

                continue;
            }

            if (is_string($value) && mb_strlen($value) > 500) {
                $sanitized[$key] = mb_substr($value, 0, 500).'...';

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
