<?php

namespace App\Http\Middleware;

use App\Models\CrmUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Modules\CrmCore\Services\CrmAccessService;
use Symfony\Component\HttpFoundation\Response;

class EnsureCrmModuleAccess
{
    public function __construct(private readonly CrmAccessService $access) {}

    public function handle(Request $request, Closure $next, string $moduleSlug, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($this->canUseAdminPanel($user)) {
            return $next($request);
        }

        $actor = $user->crmUser()
            ->with(['modules:id,slug,active', 'permissions:id,name,label,sort_order', 'sites:id'])
            ->where('active', true)
            ->first();

        if (! $actor instanceof CrmUser || $actor->role === 'blocked') {
            abort(403);
        }

        $moduleSlugs = $this->moduleSlugs($actor, $moduleSlug);

        if ($permissions === []) {
            abort_unless($this->hasAnyModule($actor, $moduleSlugs), 403);

            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (str_starts_with($permission, 'platform.') && $this->access->hasPermission($actor, $permission)) {
                return $next($request);
            }
        }

        if ($this->hasGlobalModulePermission($actor, $moduleSlugs, $permissions)) {
            return $next($request);
        }

        abort_unless($this->hasAnySitePermission($actor, $moduleSlugs, $permissions), 403);

        return $next($request);
    }

    private function canUseAdminPanel(User $user): bool
    {
        if ($user->hasAnyRole(['admin', 'Admin', 'Super Admin'])) {
            return true;
        }

        return $user->getAllPermissions()
            ->contains(fn ($permission): bool => in_array($permission->name, ['filament.access', 'filament.manage'], true));
    }

    /**
     * @return array<int, string>
     */
    private function moduleSlugs(CrmUser $actor, string $moduleSlug): array
    {
        if ($moduleSlug !== 'documents') {
            return [$moduleSlug];
        }

        $documentSlugs = $actor->modules
            ->where('active', true)
            ->pluck('slug')
            ->filter(fn (string $slug): bool => str_starts_with($slug, 'documents-'))
            ->values()
            ->all();

        return array_values(array_unique(['documents', ...$documentSlugs]));
    }

    /**
     * @param  array<int, string>  $moduleSlugs
     */
    private function hasAnyModule(CrmUser $actor, array $moduleSlugs): bool
    {
        foreach ($moduleSlugs as $slug) {
            if ($this->access->hasModule($actor, $slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $moduleSlugs
     * @param  array<int, string>  $permissions
     */
    private function hasAnySitePermission(CrmUser $actor, array $moduleSlugs, array $permissions): bool
    {
        foreach ($moduleSlugs as $slug) {
            if ($this->access->siteIdsForModule($actor, $slug, $permissions) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $moduleSlugs
     * @param  array<int, string>  $permissions
     */
    private function hasGlobalModulePermission(CrmUser $actor, array $moduleSlugs, array $permissions): bool
    {
        if (! in_array('pages-crm', $moduleSlugs, true)) {
            return false;
        }

        if (! $this->hasAnyModule($actor, $moduleSlugs)) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($this->access->hasPermission($actor, $permission)) {
                return true;
            }
        }

        return false;
    }
}
