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
5. Executer les migrations :

```bash
php artisan migrate --force
```

6. Regenerer les caches Laravel :

```bash
php artisan optimize:clear
php artisan optimize
php artisan view:cache
```

7. Verifier les pages critiques :

- `/login`
- `/conges`
- `/reservations`
- `/locations-materiel`
- `/admin`
- `/api/conges.php?action=bootstrap`

8. Verifier le scheduler et declencher un backup de controle si besoin :

```bash
php artisan schedule:list
php artisan backup:run
```

## Regle importante

Les fichiers dans `public/assets` sont des sorties compilees. Toute correction durable doit etre faite dans les sources applicatives puis rebuildee.
