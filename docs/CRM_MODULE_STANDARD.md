# Standard des modules CRM

Ce projet utilise des modules Laravel sous `Modules/Crm*`. Un nouveau module doit rester lisible, testable et deconnecte des autres modules.

## Structure minimale

- `module.json` avec `name`, `alias`, `priority` et `providers`.
- `app/Providers/<Module>ServiceProvider.php` pour charger les routes et les listeners du module.
- `routes/web.php` pour les pages CRM et les routes `/api/<module>` sans extension `.php`.
- `database/migrations` pour les migrations versionnees du module.
- `app/Services` pour la logique metier. Les controleurs doivent rester minces.
- `app/Data` pour les DTO qui normalisent les payloads entrants.
- `tests/Feature` pour les parcours API et UI critiques.

## Conventions API

- Les routes de production utilisent `/api/<module>`.
- Les routes legacy `.php` restent uniquement derriere le middleware `crm.legacy_php_api`.
- Les controleurs API recoivent `Modules\CrmCore\Http\Requests\CrmApiRequest`.
- Les actions complexes convertissent le payload avec un DTO avant d'appeler le service.

## Evenements

Les modules publient les actions importantes via `Modules\CrmCore\Events\CrmDomainEvent`.

Exemple :

```php
CrmDomainEvent::dispatch(
    module: 'reservations',
    name: 'deleted',
    entity: 'reservation',
    entityId: $reservation->id,
    siteId: $reservation->site_id,
    actorId: $actor->id,
    actorName: $actor->name,
    payload: ['title' => $reservation->title],
);
```

Les modules qui veulent reagir a un evenement enregistrent leur listener dans leur provider. Cela evite les appels directs entre modules.

## Migrations

- Les migrations CRM restent dans `Modules/<Module>/database/migrations`.
- Les migrations Laravel globales et les migrations de packages restent dans `database/migrations`.
- Le provider de chaque module etend `Modules\CrmCore\Providers\CrmModuleServiceProvider`, qui charge automatiquement `routes/web.php` et `database/migrations`.
- Lors d'un deploiement, `php artisan migrate --force` suffit : Laravel garde les migrations deja executees par leur nom de fichier.

## Feature flags

Chaque module CRM a un flag `module:<slug>` en base dans `crm_feature_flags`.

Commandes utiles :

```bash
php artisan crm:feature --list
php artisan crm:feature module:reservations --disable
php artisan crm:feature module:reservations --enable
```

Un module desactive par feature flag disparait du menu et ses routes protegees par `crm.module` retournent une erreur au lieu de continuer a servir la fonctionnalite.
