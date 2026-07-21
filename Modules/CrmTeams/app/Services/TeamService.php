<?php

namespace Modules\CrmTeams\Services;

use App\Models\CrmUser;
use App\Models\User;
use Modules\CrmCore\Services\CrmAccessService;
use Modules\CrmCore\Support\CrmReferenceCache;
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

        $defaultSiteId = (int) (collect(CrmReferenceCache::activeUserRows())
            ->firstWhere('id', $actor->id)['defaultSiteId'] ?? 0);

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
        $siteLookup = array_fill_keys($siteIds, true);
        $counts = array_fill_keys($siteIds, 0);

        foreach (CrmReferenceCache::activeUserRows() as $user) {
            foreach ($user['siteIds'] as $siteId) {
                if (isset($siteLookup[$siteId])) {
                    $counts[$siteId]++;
                }
            }
        }

        return collect(CrmReferenceCache::activeSiteRows())
            ->filter(fn (array $site): bool => isset($siteLookup[(int) $site['id']]))
            ->sortBy('name')
            ->map(fn (array $site): array => [
                'id' => (int) $site['id'],
                'name' => $site['name'],
                'slug' => $site['slug'],
                'active' => true,
                'membersCount' => (int) ($counts[(int) $site['id']] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function memberRows(int $siteId): array
    {
        return collect(CrmReferenceCache::activeUserRows())
            ->filter(fn (array $member): bool => in_array($siteId, $member['siteIds'], true))
            ->sortBy(fn (array $member): string => sprintf(
                '%s|%s|%s',
                $member['firstName'] ?: $member['name'],
                $member['lastName'] ?: $member['name'],
                $member['name'],
            ))
            ->map(fn (array $member): array => $this->memberRow($member))
            ->values()
            ->all();
    }

    /**
     * @param  array{id: int, name: string, firstName: string, lastName: string, email: string, phone: string, role: string, photoUrl: string, siteIds: array<int, int>, siteNames: array<int, string>, defaultSiteId: int|null}  $member
     */
    private function memberRow(array $member): array
    {
        [$firstName, $lastName] = $this->splitName($member['name'], $member['firstName'], $member['lastName']);

        return [
            'id' => $member['id'],
            'name' => trim($firstName.' '.$lastName) ?: $member['name'],
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $member['phone'],
            'email' => $member['email'],
            'role' => $member['role'],
            'photoUrl' => $member['photoUrl'],
            'siteIds' => $member['siteIds'],
            'siteNames' => $member['siteNames'],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name, string $firstName, string $lastName): array
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if ($firstName === '') {
            $rawName = trim($name);
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
