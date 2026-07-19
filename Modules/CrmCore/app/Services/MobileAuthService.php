<?php

namespace Modules\CrmCore\Services;

use App\Models\CrmMenuGroup;
use App\Models\CrmMenuItem;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\CrmCore\Http\Requests\MobileTokenRequest;

class MobileAuthService
{
    /**
     * @return array{data: array<string, mixed>, status: int}
     */
    public function issueToken(MobileTokenRequest $request): array
    {
        $data = $request->validated();
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return $this->result([
                'ok' => false,
                'error' => __('crm.mobile.too_many_attempts'),
                'retryAfter' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return $this->result(['ok' => false, 'error' => __('crm.mobile.invalid_credentials')], 422);
        }

        $crmUser = $this->crmUserFor($user);

        if (! $crmUser || $crmUser->role === 'blocked') {
            RateLimiter::hit($throttleKey, 60);

            return $this->result(['ok' => false, 'error' => __('crm.mobile.account_unavailable')], 403);
        }

        RateLimiter::clear($throttleKey);

        $expiresAt = $this->tokenExpiresAt();
        $token = $user
            ->createToken((string) $data['device_name'], ['crm:mobile'], $expiresAt)
            ->plainTextToken;

        return $this->result([
            'ok' => true,
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresAt' => $expiresAt?->toIso8601String(),
            'user' => $this->userPayload($crmUser),
        ]);
    }

    /**
     * @return array{data: array<string, mixed>, status: int}
     */
    public function currentUser(Request $request): array
    {
        $user = $request->user();
        $crmUser = $user instanceof User ? $this->crmUserFor($user) : null;

        if (! $crmUser || $crmUser->role === 'blocked') {
            return $this->result(['ok' => false, 'error' => __('crm.mobile.account_unavailable')], 403);
        }

        return $this->result(['ok' => true, 'user' => $this->userPayload($crmUser)]);
    }

    /**
     * @return array{data: array<string, mixed>, status: int}
     */
    public function createWebSession(Request $request): array
    {
        $user = $request->user();
        $crmUser = $user instanceof User ? $this->crmUserFor($user) : null;

        if (! $user instanceof User || ! $crmUser || $crmUser->role === 'blocked') {
            return $this->result(['ok' => false, 'error' => __('crm.mobile.account_unavailable')], 403);
        }

        $data = $request->validate([
            'redirectPath' => ['nullable', 'string', 'max:2048'],
            'siteId' => ['nullable', 'integer'],
            'embed' => ['nullable', 'boolean'],
        ]);

        $token = Str::random(56);
        $mobileToken = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        Cache::put($this->webSessionCacheKey($token), [
            'user_id' => (int) $user->id,
            'mobile_token_id' => $mobileToken && method_exists($mobileToken, 'getKey')
                ? (int) $mobileToken->getKey()
                : null,
            'redirect_path' => $this->safeMobileRedirectPath(
                (string) ($data['redirectPath'] ?? '/'),
                isset($data['siteId']) ? (int) $data['siteId'] : null,
                (bool) ($data['embed'] ?? true),
            ),
        ], now()->addSeconds(90));

        return $this->result([
            'ok' => true,
            'url' => url('/mobile/session/'.$token),
            'expiresIn' => 90,
        ]);
    }

    public function consumeWebSession(Request $request, string $token): string
    {
        $payload = Cache::pull($this->webSessionCacheKey($token));

        abort_unless(is_array($payload), 404);

        $user = User::query()->find((int) ($payload['user_id'] ?? 0));

        abort_unless($user instanceof User, 403);

        Auth::guard('web')->login($user, remember: false);
        $request->session()->regenerate();

        $mobileTokenId = (int) ($payload['mobile_token_id'] ?? 0);

        if ($mobileTokenId > 0) {
            $request->session()->put('crm_mobile_token_id', $mobileTokenId);
            $request->session()->put('crm_mobile_app', true);
        }

        return (string) ($payload['redirect_path'] ?? '/');
    }

    public function revokeCurrentToken(Request $request): void
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
    }

    /**
     * @return array{data: array<string, mixed>, status: int}
     */
    private function result(array $data, int $status = 200): array
    {
        return ['data' => $data, 'status' => $status];
    }

    private function crmUserFor(User $user): ?CrmUser
    {
        return CrmUser::query()
            ->with(['account:id,email,name', 'modules:id,slug,name,active,route_path,sort_order', 'permissions:id,name', 'sites:id,name,slug'])
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(CrmUser $crmUser): array
    {
        $modules = $crmUser->role === 'blocked'
            ? collect()
            : $crmUser->modules
                ->where('active', true)
                ->sortBy('sort_order')
                ->map(fn ($module): array => [
                    'id' => (int) $module->id,
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'routePath' => $module->route_path,
                ])
                ->values();

        $sites = $crmUser->sites
            ->sortByDesc(fn ($site): bool => (bool) $site->pivot?->is_default)
            ->map(fn ($site): array => [
                'id' => (int) $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'isDefault' => (bool) $site->pivot?->is_default,
            ])
            ->values();

        $defaultSite = $sites->firstWhere('isDefault', true) ?: $sites->first();

        return [
            'id' => $crmUser->id,
            'name' => $crmUser->name,
            'email' => $crmUser->email ?: $crmUser->account?->email,
            'role' => $crmUser->role,
            'photoUrl' => $crmUser->photo_url,
            'sites' => $sites->all(),
            'defaultSiteId' => $defaultSite['id'] ?? null,
            'siteIds' => $crmUser->sites->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'modules' => $modules->all(),
            'menu' => $crmUser->role === 'blocked' ? [] : $this->menuPayload($modules->all()),
            'permissions' => $crmUser->role === 'blocked'
                ? []
                : $crmUser->permissions->pluck('name')->sort()->values()->all(),
        ];
    }

    /**
     * @param  array<int, array{id:int,name:string,slug:string,routePath:?string}>  $modules
     * @return array<int, array{key:string,title:string,items:array<int, array<string, mixed>>}>
     */
    private function menuPayload(array $modules): array
    {
        $modulesBySlug = collect($modules)->keyBy('slug');
        $itemKeys = $modulesBySlug
            ->keys()
            ->map(fn (string $slug): string => 'module:'.$slug)
            ->all();

        if ($itemKeys === []) {
            return [];
        }

        $groups = CrmMenuGroup::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->keyBy('menu_key');

        $items = CrmMenuItem::query()
            ->where('active', true)
            ->whereIn('item_key', $itemKeys)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return $items
            ->groupBy('group_key')
            ->map(function ($groupItems, string $groupKey) use ($groups, $modulesBySlug): array {
                $group = $groups->get($groupKey);

                return [
                    'key' => $groupKey,
                    'title' => $group?->title ?: Str::headline($groupKey),
                    'items' => $groupItems
                        ->map(function (CrmMenuItem $item) use ($modulesBySlug): ?array {
                            $slug = Str::after($item->item_key, 'module:');
                            $module = $modulesBySlug->get($slug);

                            if (! $module) {
                                return null;
                            }

                            return [
                                'key' => $item->item_key,
                                'groupKey' => $item->group_key,
                                'iconKey' => $item->icon_key,
                                'label' => $item->label,
                                'slug' => $slug,
                                'routePath' => $module['routePath'] ?: '/'.$slug,
                                'sortOrder' => (int) $item->sort_order,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();
    }

    private function safeMobileRedirectPath(string $value, ?int $siteId, bool $embed = true): string
    {
        $path = trim($value);

        if ($path === '' || ! Str::startsWith($path, '/') || Str::startsWith($path, '//') || str_contains($path, "\n") || str_contains($path, "\r")) {
            $path = '/';
        }

        if (preg_match('#^/(?:api|assets|build|filament|livewire|storage|up)(?:/|$)#', $path)) {
            $path = '/';
        }

        $parts = parse_url($path);
        $targetPath = (string) ($parts['path'] ?? '/');

        if ($embed && $targetPath === '/dashboard/crm') {
            $targetPath = '/';
        }

        $query = [];

        if (! empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        if ($embed) {
            $query['mobile_embed'] = '1';
        } else {
            $query['mobile_app'] = '1';
            unset($query['mobile_embed']);
        }

        if ($siteId && $siteId > 0) {
            $query['mobile_site_id'] = (string) $siteId;
        }

        $queryString = http_build_query($query);

        return $targetPath.($queryString !== '' ? '?'.$queryString : '');
    }

    private function webSessionCacheKey(string $token): string
    {
        return 'crm:mobile:web-session:'.$token;
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
}
