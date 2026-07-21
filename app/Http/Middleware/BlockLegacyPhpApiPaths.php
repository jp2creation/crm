<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockLegacyPhpApiPaths
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->candidatePaths($request) as $path) {
            if (preg_match('#^api/[^?]*\.php$#i', $path) === 1) {
                abort(404);
            }
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(Request $request): array
    {
        $paths = [trim($request->path(), '/')];

        foreach (['REQUEST_URI', 'REDIRECT_URL', 'UNENCODED_URL', 'ORIG_PATH_INFO'] as $serverKey) {
            $value = (string) $request->server($serverKey, '');
            $parsedPath = parse_url($value, PHP_URL_PATH);

            if (is_string($parsedPath)) {
                $paths[] = trim(rawurldecode($parsedPath), '/');
            }
        }

        return array_values(array_unique(array_filter($paths)));
    }
}
