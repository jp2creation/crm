<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $compressed = gzencode($content, (int) config('crm.compression.level', 6));

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', $this->varyHeader($response));

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        if (! (bool) config('crm.compression.enabled', true)) {
            return false;
        }

        if ($request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return false;
        }

        if (! str_contains(strtolower((string) $request->headers->get('Accept-Encoding')), 'gzip')) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if ($response->headers->has('Content-Encoding') || ! method_exists($response, 'getContent')) {
            return false;
        }

        $content = $response->getContent();
        $minBytes = max(0, (int) config('crm.compression.min_bytes', 1024));

        if (! is_string($content) || strlen($content) < $minBytes) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        return str_contains($contentType, 'application/json')
            || str_contains($contentType, 'text/')
            || str_contains($contentType, 'application/javascript')
            || str_contains($contentType, 'application/xml');
    }

    private function varyHeader(Response $response): string
    {
        $vary = collect(explode(',', (string) $response->headers->get('Vary')))
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values();

        if (! $vary->contains(fn (string $value): bool => strtolower($value) === 'accept-encoding')) {
            $vary->push('Accept-Encoding');
        }

        return $vary->implode(', ');
    }
}
