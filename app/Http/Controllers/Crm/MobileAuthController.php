<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function options(): JsonResponse
    {
        return $this->json(['ok' => true]);
    }

    public function token(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->json([
                'ok' => false,
                'error' => 'Trop de tentatives. Reessayez plus tard.',
                'retryAfter' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return $this->json(['ok' => false, 'error' => 'Identifiants invalides.'], 422);
        }

        $crmUser = $this->crmUserFor($user);

        if (! $crmUser || $crmUser->role === 'blocked') {
            RateLimiter::hit($throttleKey, 60);

            return $this->json(['ok' => false, 'error' => 'Compte CRM indisponible.'], 403);
        }

        RateLimiter::clear($throttleKey);

        $expiresAt = $this->tokenExpiresAt();
        $token = $user
            ->createToken((string) $data['device_name'], ['crm:mobile'], $expiresAt)
            ->plainTextToken;

        return $this->json([
            'ok' => true,
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresAt' => $expiresAt?->toIso8601String(),
            'user' => $this->userPayload($crmUser),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $crmUser = $user instanceof User ? $this->crmUserFor($user) : null;

        if (! $crmUser || $crmUser->role === 'blocked') {
            return $this->json(['ok' => false, 'error' => 'Compte CRM indisponible.'], 403);
        }

        return $this->json(['ok' => true, 'user' => $this->userPayload($crmUser)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $bearerToken = $request->bearerToken();
        $token = $request->user()?->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        if ($bearerToken && str_contains($bearerToken, '|')) {
            $plainTextToken = Str::after($bearerToken, '|');

            $request->user()?->tokens()
                ->where('token', hash('sha256', $plainTextToken))
                ->delete();
        }

        Auth::guard('sanctum')->forgetUser();
        Auth::guard('web')->forgetUser();

        return $this->json(['ok' => true]);
    }

    private function crmUserFor(User $user): ?CrmUser
    {
        return CrmUser::query()
            ->with(['modules:id,slug,name,active,route_path,sort_order', 'permissions:id,name', 'sites:id,name,slug'])
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();
    }

    private function userPayload(CrmUser $crmUser): array
    {
        return [
            'id' => $crmUser->id,
            'name' => $crmUser->name,
            'email' => $crmUser->email ?: $crmUser->account?->email,
            'role' => $crmUser->role,
            'siteIds' => $crmUser->sites->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'modules' => $crmUser->role === 'blocked'
                ? []
                : $crmUser->modules
                    ->where('active', true)
                    ->sortBy('sort_order')
                    ->map(fn ($module): array => [
                        'id' => (int) $module->id,
                        'name' => $module->name,
                        'slug' => $module->slug,
                        'routePath' => $module->route_path,
                    ])
                    ->values()
                    ->all(),
            'permissions' => $crmUser->role === 'blocked'
                ? []
                : $crmUser->permissions->pluck('name')->sort()->values()->all(),
        ];
    }

    private function tokenExpiresAt(): ?Carbon
    {
        $days = (int) config('sanctum.mobile_token_expiration_days', 365);

        return $days > 0 ? now()->addDays($days) : null;
    }

    private function throttleKey(Request $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    private function json(array $data, int $status = 200): JsonResponse
    {
        return response()
            ->json($data, $status, [], JSON_UNESCAPED_UNICODE)
            ->withHeaders($this->crmApiHeaders());
    }
}
