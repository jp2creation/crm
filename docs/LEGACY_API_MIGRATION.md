# Migration des endpoints legacy `.php`

Objectif : retirer progressivement les anciens endpoints `/api/*.php` et garder uniquement les routes REST Laravel sans extension.

## Etat au 19 juillet 2026

- Les routes REST sans extension sont disponibles pour les modules CRM principaux.
- Le middleware `crm.legacy_php_api` audite les appels `.php`.
- La production doit rester avec `CRM_LEGACY_PHP_API_ENABLED=false`.

## Feuille de route

### Phase 1 - Audit final des consommateurs, avant le 15 aout 2026

- Lire les logs `Blocked legacy CRM .php API call`.
- Identifier les navigateurs, PWA, scripts ou integrations externes qui appellent encore `/api/*.php`.
- Creer un ticket par endpoint encore utilise avec le remplacement REST attendu.

### Phase 2 - Migration des clients, avant le 15 septembre 2026

- Remplacer tous les appels front et mobile par `/api/<module>`.
- Mettre a jour la documentation OpenAPI/Swagger pour les endpoints `Chat`, `News`, `Documents`, reservations, locations et comptabilite.
- Garder les tests qui garantissent que les assets compiles ne referencent plus les routes legacy.

### Phase 3 - Suppression applicative, avant le 31 octobre 2026

- Retirer les definitions de routes `/api/*.php` dans les modules.
- Supprimer les alias de routes `.legacy` lorsque les logs prouvent qu'ils ne recoivent plus de trafic utile.
- Conserver les redirects de pages historiques uniquement si elles aident les favoris utilisateurs.

### Phase 4 - Nettoyage securite, avant le 15 novembre 2026

- Supprimer `AuditLegacyPhpApi` si aucun endpoint `.php` ne reste expose.
- Supprimer la configuration `CRM_LEGACY_PHP_API_ENABLED`.
- Mettre a jour les tests de securite pour verifier l'absence totale de routes API legacy.

## Commandes de controle

```bash
rg -n "/api/[A-Za-z0-9_-]+\\.php|\\.legacy" app Modules resources tests
php artisan route:list | rg "\\.php|legacy"
php artisan test --filter=CrmSecurityTest
```

## Regle de release

Une nouvelle fonctionnalite ne doit pas ajouter de route `.php`. Si une integration externe impose temporairement un endpoint legacy, elle doit passer par `crm.legacy_php_api`, etre documentee ici, et avoir une date de retrait.
