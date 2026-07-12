<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLegacyPhpApi
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('crm.legacy_php_api.enabled', true)) {
            Log::warning('Blocked legacy CRM .php API call.', $this->context($request));

            return response()
                ->json([
                    'ok' => false,
                    'error' => 'Endpoint legacy desactive. Utilisez la route API sans extension .php.',
                ], 410, [], JSON_UNESCAPED_UNICODE)
                ->withHeaders($this->corsHeaders($request));
        }

        if ((bool) config('crm.legacy_php_api.log_calls', true)) {
            Log::notice('Legacy CRM .php API call.', $this->context($request));
        }

        return $next($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Request $request): array
    {
        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function corsHeaders(Request $request): array
    {
        return [
            'Access-Control-Allow-Origin' => $request->headers->get('Origin', '*'),
            'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-CSRF-TOKEN, X-Requested-With, X-XSRF-TOKEN',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Vary' => 'Origin',
        ];
    }
}
