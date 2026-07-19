# Standard des modules CRM

Ce projet utilise des modules Laravel sous `Modules/Crm*`. Un nouveau module doit rester lisible, testable et deconnecte des autres modules.

## Structure minimale

- `module.json` avec `name`, `alias`, `priority` et `providers`.
- `app/Providers/<Module>ServiceProvider.php` pour charger les routes et les listeners du module.
- `routes/web.php` pour les pages CRM et les routes `/api/<module>` sans extension `.php`.
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
