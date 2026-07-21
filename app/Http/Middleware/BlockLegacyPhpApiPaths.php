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
        if (preg_match('#^api/[^?]*\.php$#i', trim($request->path(), '/')) === 1) {
            abort(404);
        }

        return $next($request);
    }
}
