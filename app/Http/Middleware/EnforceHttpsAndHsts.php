<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceHttpsAndHsts
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) config('crm.security.force_https', false) && ! $request->secure()) {
            return redirect()->away('https://'.$request->getHttpHost().$request->getRequestUri(), 301);
        }

        $response = $next($request);

        if ((bool) config('crm.security.hsts_enabled', false) && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', $this->hstsHeader());
        }

        if ((bool) config('crm.security.headers_enabled', true)) {
            $this->setSecurityHeaders($response);
        }

        return $response;
    }

    private function hstsHeader(): string
    {
        $parts = ['max-age='.max(0, (int) config('crm.security.hsts_max_age', 31536000))];

        if ((bool) config('crm.security.hsts_include_subdomains', true)) {
            $parts[] = 'includeSubDomains';
        }

        if ((bool) config('crm.security.hsts_preload', true)) {
            $parts[] = 'preload';
        }

        return implode('; ', $parts);
    }

    private function setSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', (string) config('crm.security.frame_options', 'DENY'));
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', (string) config('crm.security.referrer_policy', 'strict-origin-when-cross-origin'));
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        $permissionsPolicy = trim((string) config('crm.security.permissions_policy', ''));

        if ($permissionsPolicy !== '') {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        if ((bool) config('crm.security.csp_enabled', false)) {
            $header = (bool) config('crm.security.csp_report_only', true)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($header, (string) config('crm.security.csp'));
        }
    }
}
