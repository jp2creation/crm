<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function authenticatedApiUser(Request $request): ?User
    {
        $user = $request->user();

        if (! $user && $request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
        }

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<string, string>
     */
    protected function crmApiHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => request()->headers->get('Origin', '*'),
            'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-CSRF-TOKEN, X-Requested-With, X-XSRF-TOKEN',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Vary' => 'Origin',
        ];
    }
}
