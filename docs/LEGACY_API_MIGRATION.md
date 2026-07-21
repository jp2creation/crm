# Migration des endpoints legacy `.php`

Objectif : retirer progressivement les anciens endpoints `/api/*.php` et garder uniquement les routes REST Laravel sans extension.

## Etat au 21 juillet 2026

- Les routes REST sans extension sont disponibles pour les modules CRM principaux.
- Les anciens endpoints `/api/*.php` ne pointent plus vers les controleurs metier.
- Chaque route `.php` passe par `LegacyPhpApiController`, qui journalise l'appel dans le canal `crm`.
- Les methodes de lecture `GET` et `HEAD` sont redirigees en `308` vers la route REST sans extension, avec la query conservee.
- Les mutations `POST`, `PUT`, `PATCH` et `DELETE` sont refusees en `410`.
- La variable `CRM_LEGACY_PHP_API_REDIRECT_SAFE_METHODS=false` permet de bloquer aussi les lectures si une fenetre de retrait total est planifiee.

## Feuille de route

### Phase 1 - Audit final des consommateurs, avant le 15 aout 2026

- Lire les logs `Legacy CRM .php API redirected.` et `Legacy CRM .php API mutation blocked.`.
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
- Supprimer la configuration `CRM_LEGACY_PHP_API_REDIRECT_SAFE_METHODS`.
- Mettre a jour les tests de securite pour verifier l'absence totale de routes API legacy.

## Commandes de controle

```bash
rg -n "/api/[A-Za-z0-9_-]+\\.php|\\.legacy" app Modules resources tests
php artisan route:list | rg "\\.php|legacy"
php artisan test --filter=CrmSecurityTest
```

## Regle de release

Une nouvelle fonctionnalite ne doit pas ajouter de route `.php`. Si une integration externe impose temporairement un endpoint legacy, il doit rester un adaptateur mort vers `LegacyPhpApiController`, etre documente ici, et avoir une date de retrait.
