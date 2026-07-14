<?php

namespace Modules\CrmTeams\Services;

use App\Models\CrmSite;
use App\Models\CrmUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\CrmCore\Services\CrmAccessService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeamService
{
    public function __construct(
        private readonly CrmAccessService $access,
    ) {}

    public function actorForUser(User $user): CrmUser
    {
        $actor = CrmUser::query()
            ->with(['modules:id,slug,active', 'permissions:id,name,label', 'sites:id'])
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (! $actor) {
            $this->fail('Utilisateur CRM introuvable', 404);
        }

        return $actor;
    }

    public function bootstrap(CrmUser $actor, ?int $requestedSiteId = null): array
    {
        $siteIds = $this->access->siteIdsForModule($actor, 'equipes', ['teams.view']);

        if ($siteIds === []) {
            $this->fail('Aucun site autorise pour le module equipe', 403);
        }

        $selectedSiteId = $this->selectedSiteId($actor, $requestedSiteId, $siteIds);
        if (! $selectedSiteId) {
            $this->fail('Aucun site disponible', 403);
        }

        return [
            'ok' => true,
            'mode' => 'mysql',
            'user' => $this->actorRow($actor, $siteIds, $selectedSiteId),
            'selectedSiteId' => $selectedSiteId,
            'sites' => $this->siteRows($siteIds),
            'members' => $this->memberRows($selectedSiteId),
        ];
    }

    /**
     * @param  array<int, int>  $siteIds
     */
    private function selectedSiteId(CrmUser $actor, ?int $requestedSiteId, array $siteIds): ?int
    {
        if ($requestedSiteId && in_array($requestedSiteId, $siteIds, true)) {
            return $requestedSiteId;
        }

        $defaultSiteId = (int) DB::table('crm_user_sites')
            ->where('user_id', $actor->id)
            ->where('is_default', true)
            ->value('site_id');

        if ($defaultSiteId > 0 && in_array($defaultSiteId, $siteIds, true)) {
            return $defaultSiteId;
        }

        return $siteIds[0] ?? null;
    }

    /**
     * @param  array<int, int>  $siteIds
     * @return array<int, array{id: int, name: string, slug: string, active: bool, membersCount: int}>
     */
    private function siteRows(array $siteIds): array
    {
        $counts = DB::table('crm_user_sites')
            ->join('crm_users', 'crm_users.id', '=', 'crm_user_sites.user_id')
            ->whereIn('crm_user_sites.site_id', $siteIds)
            ->where('crm_users.active', true)
            ->selectRaw('crm_user_sites.site_id, COUNT(*) as total')
            ->groupBy('crm_user_sites.site_id')
            ->pluck('total', 'site_id');

        return CrmSite::query()
            ->active()
            ->whereIn('id', $siteIds)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'active'])
            ->map(fn (CrmSite $site): array => [
                'id' => (int) $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'active' => (bool) $site->active,
                'membersCount' => (int) ($counts[$site->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function memberRows(int $siteId): array
    {
        return CrmUser::query()
            ->with(['account:id,name,email', 'sites:id,name'])
            ->where('active', true)
            ->whereHas('sites', fn ($query) => $query->where('crm_sites.id', $siteId))
            ->orderByRaw("COALESCE(NULLIF(first_name, ''), name) asc")
            ->orderByRaw("COALESCE(NULLIF(last_name, ''), name) asc")
            ->get()
            ->map(fn (CrmUser $member): array => $this->memberRow($member))
            ->values()
            ->all();
    }

    private function memberRow(CrmUser $member): array
    {
        [$firstName, $lastName] = $this->splitName($member);

        return [
            'id' => (int) $member->id,
            'name' => trim($firstName.' '.$lastName) ?: $member->name,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => trim((string) $member->phone),
            'email' => trim((string) $member->email) ?: trim((string) ($member->account?->email ?? '')),
            'role' => $member->role,
            'photoUrl' => trim((string) $member->photo_url) ?: '/assets/logo/logomark.png',
            'siteIds' => $member->sites->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all(),
            'siteNames' => $member->sites->pluck('name')->values()->all(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(CrmUser $member): array
    {
        $firstName = trim((string) $member->first_name);
        $lastName = trim((string) $member->last_name);

        if ($firstName === '') {
            $rawName = trim((string) $member->name);
            if ($rawName === 'J-Philippe') {
                return ['Jean-Philippe', $lastName];
            }

            $parts = preg_split('/\s+/', $rawName, 2);
            $firstName = trim((string) ($parts[0] ?? ''));

            if ($lastName === '') {
                $lastName = trim((string) ($parts[1] ?? ''));
            }
        }

        return [$firstName, $lastName];
    }

    /**
     * @param  array<int, int>  $siteIds
     */
    private function actorRow(CrmUser $actor, array $siteIds, int $selectedSiteId): array
    {
        return [
            'id' => (int) $actor->id,
            'name' => $actor->name,
            'role' => $actor->role,
            'siteIds' => $siteIds,
            'selectedSiteId' => $selectedSiteId,
        ];
    }

    private function fail(string $message, int $status): never
    {
        throw new HttpException($status, $message);
    }
}
