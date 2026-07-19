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

## Script de deploiement aide

Le depot fournit `make deploy`, qui appelle `scripts/deploy-planethoster.sh`. Le script construit une archive locale, l'envoie en SSH, cree un backup du dossier distant, extrait la release, met a jour `CRM_ASSET_VERSION`, lance les migrations et reconstruit les caches.

Les secrets ne doivent jamais etre stockes dans le depot. Configurer les variables dans le terminal ou dans un gestionnaire local :

```bash
export CRM_DEPLOY_HOST=node35-ca.n0c.com
export CRM_DEPLOY_PORT=5022
export CRM_DEPLOY_USER=mon_utilisateur
export CRM_DEPLOY_PATH=/home/mon_utilisateur/crm
export CRM_DEPLOY_COMPOSER=/home/mon_utilisateur/bin/composer

make deploy-check
make deploy
```

Options utiles :

- `CRM_DEPLOY_BUILD=0` pour sauter `npm run build` si les assets sont deja prets.
- `CRM_DEPLOY_ALLOW_DIRTY=1` pour deployer une copie locale non commitee, uniquement en urgence.
- `CRM_DEPLOY_TMP_DIR=/home/mon_utilisateur` pour choisir le dossier temporaire distant.
