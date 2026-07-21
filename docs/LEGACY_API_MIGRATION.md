# Migration des endpoints legacy `.php`

Objectif : garder uniquement les routes Laravel sans extension et supprimer toute exposition directe de scripts ou d'alias `/api/*.php`.

## Etat au 21 juillet 2026

- Aucun fichier PHP legacy n'est expose dans `public/`; seul `public/index.php` reste le front controller Laravel.
- Les modules CRM enregistrent uniquement les routes metier `/api/<module>` sans extension.
- Une route globale de blocage `/api/{legacyPhpPath}.php` retourne `404` avant toute action metier, afin d'empecher la redirection canonique automatique vers les routes modernes.
- `LegacyPhpApiController`, `AuditLegacyPhpApi`, le rate limiter `crm-legacy-api` et les variables `CRM_LEGACY_PHP_API_*` ont ete supprimes.
- La documentation OpenAPI ne liste plus les chemins `.php`.

## Regle de developpement

Une nouvelle fonctionnalite ne doit jamais ajouter de route API metier avec extension `.php`. La seule route `.php` autorisee est le coupe-circuit global `crm.api.legacy-php-blocked`.

Si une integration externe demande un ancien chemin, il faut migrer le client vers la route Laravel moderne correspondante :

```text
/api/reservations.php?action=health  ->  /api/reservations?action=health
/api/conges.php?action=bootstrap     ->  /api/conges?action=bootstrap
```

## Commandes de controle

```bash
find public -maxdepth 5 -type f -name '*.php' -print
rg -n "/api/[A-Za-z0-9_-]+\\.php|LegacyPhpApiController|crm-legacy-api|legacy_php_api" app bootstrap config Modules resources tests docs
php artisan route:list | rg "/api/.*\\.php|crm-legacy-api|LegacyPhpApiController"
php artisan test --filter=CrmSecurityTest
```

La seule sortie attendue pour `find public ...` est :

```text
public/index.php
```

La seule route `.php` attendue dans `route:list` est `api/{legacyPhpPath}.php`, nommee `crm.api.legacy-php-blocked`, et elle doit pointer vers `BlockedLegacyPhpApiController`.
