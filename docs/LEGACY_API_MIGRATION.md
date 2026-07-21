# Migration des endpoints legacy `.php`

Objectif : garder uniquement les routes Laravel sans extension et supprimer toute exposition directe de scripts ou d'alias `/api/*.php`.

## Etat au 21 juillet 2026

- Aucun fichier PHP legacy n'est expose dans `public/`; seul `public/index.php` reste le front controller Laravel.
- Les modules CRM enregistrent uniquement les routes `/api/<module>` sans extension.
- Les anciens chemins `/api/*.php` ne sont plus routes par Laravel et retournent une erreur `404`.
- `LegacyPhpApiController`, `AuditLegacyPhpApi`, le rate limiter `crm-legacy-api` et les variables `CRM_LEGACY_PHP_API_*` ont ete supprimes.
- La documentation OpenAPI ne liste plus les chemins `.php`.

## Regle de developpement

Une nouvelle fonctionnalite ne doit jamais ajouter de route API avec extension `.php`.

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
