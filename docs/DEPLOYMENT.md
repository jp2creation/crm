# Deploiement CRM

Ce projet doit etre deploye depuis le depot Git local, pas par modification directe de fichiers minifies sur le serveur.

## Verification locale

```bash
composer install --no-interaction
npm install
php artisan test
vendor/bin/pint --test app routes database tests
npm run build
composer audit
npm audit --audit-level=moderate
```

## Mise en production

1. Sauvegarder la base de donnees et les fichiers uploads.
2. Envoyer le code versionne sur le serveur.
3. Installer les dependances PHP sans dev :

```bash
composer install --no-dev --optimize-autoloader
```

4. Construire ou envoyer les assets compiles selon la strategie choisie.
5. Ecrire la revision deployee pour le cache-busting des assets :

```bash
git rev-parse HEAD > .deployed-revision
```

Si le serveur ne contient pas le dossier `.git`, renseigner `CRM_ASSET_VERSION` dans `.env` avec le SHA ou le numero de release.

6. Publier les assets des modules CRM :

```bash
php artisan crm:publish-module-assets --force
```

7. Executer les migrations :

```bash
php artisan migrate --force
```

Les migrations CRM sont chargees depuis `Modules/*/database/migrations`. Avant d'envoyer une release, verifier qu'aucune migration CRM ne reste dans `database/migrations` :

```bash
php artisan test --filter=CrmModuleManifestTest
```

8. Regenerer les caches Laravel :

```bash
php artisan optimize:clear
php artisan optimize
php artisan view:cache
```

9. Verifier les pages critiques :

- `/login`
- `/dashboard/crm`
- `/conges`
- `/reservations`
- `/locations-materiel`
- `/rapport-visite`
- `/controle-caisse`
- `/remise-cheques`
- `/documents/promo`
- `/admin`
- `/api/conges?action=bootstrap`

10. Verifier le scheduler et declencher un backup de controle si besoin :

```bash
php artisan schedule:list
php artisan backup:run
```

## Feature flags production

Les modules peuvent etre actives ou desactives sans redeploiement via la table `crm_feature_flags`.

```bash
php artisan crm:feature --list
php artisan crm:feature module:locations-materiel --disable
php artisan crm:feature module:locations-materiel --enable
php artisan optimize:clear
```

Les flags de module utilisent la forme `module:<slug>`. Une desactivation retire le module des references actives, du menu et des routes protegees par `crm.module`.

## Migration des endpoints legacy

La feuille de route de retrait des routes `.php` est documentee dans [LEGACY_API_MIGRATION.md](LEGACY_API_MIGRATION.md). En production, conserver `CRM_LEGACY_PHP_API_ENABLED=false` sauf fenetre de compatibilite explicitement planifiee.

## Regle importante

Les fichiers dans `public/assets` sont des sorties compilees. Toute correction durable doit etre faite dans les sources applicatives puis rebuildee.

Les fichiers dans `public/modules` sont publies depuis `Modules/*/resources/assets`. Apres une modification de module, lancer `php artisan crm:publish-module-assets --force` avant de vider les caches.
