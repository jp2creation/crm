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
        if ((bool) config('crm.security.force_https', false) && ! $this->isSecure($request)) {
            return redirect()->away('https://'.$request->getHttpHost().$request->getRequestUri(), 301);
        }

        $response = $next($request);

        if ((bool) config('crm.security.hsts_enabled', false) && $this->isSecure($request)) {
            $response->headers->set('Strict-Transport-Security', $this->hstsHeader());
        }

        return $response;
    }

    private function isSecure(Request $request): bool
    {
        $forwardedProto = strtolower((string) $request->headers->get('X-Forwarded-Proto'));
        $forwardedSsl = strtolower((string) $request->headers->get('X-Forwarded-Ssl'));

        return $request->secure()
            || $forwardedProto === 'https'
            || $forwardedSsl === 'on';
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
}
