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
            if ($this->isLegacyPhpApiPath($path)) {
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
}
