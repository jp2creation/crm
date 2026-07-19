<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class MirrorAuthenticatedSessionMetadata
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->mirror($request);

        return $response;
    }

    private function mirror(Request $request): void
    {
        if ((string) config('session.driver') === 'database' || ! $request->hasSession()) {
            return;
        }

        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        $table = (string) config('session.table', 'sessions');
        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->updateOrInsert(
            ['id' => (string) $request->session()->getId()],
            [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]
        );
    }
}
