# Installation du CRM Martin Sols

Ce document decrit l'installation locale et les commandes utiles pour preparer une mise en production.

## Prerequis

- PHP 8.3 ou superieur avec les extensions usuelles Laravel.
- Composer.
- Node.js et npm.
- MySQL ou MariaDB.
- Git.
- Un serveur web compatible Laravel pour la production.

Pour l'application mobile :

- Node.js et npm dans `mobile/`.
- Android Studio pour Android.
- Xcode sur macOS pour iOS.

## Installation locale

Depuis la racine du projet :

```bash
composer install
npm install
```

Creer le fichier d'environnement :

```bash
cp .env.example .env
```

Sur Windows PowerShell :

```powershell
Copy-Item .env.example .env
```

Configurer ensuite `.env` :

```env
APP_NAME="Martin Sols CRM"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=

CRM_ADMIN_EMAIL=admin@crm.jp2.fr
CRM_ADMIN_NAME=Administrateur

SANCTUM_MOBILE_TOKEN_EXPIRATION_DAYS=365
```

Generer la cle Laravel :

```bash
php artisan key:generate
```

Creer la base de donnees MySQL/MariaDB si elle n'existe pas encore, puis executer les migrations :

```bash
php artisan migrate
```

Initialiser les roles de base :

```bash
php artisan db:seed
```

Creer ou mettre a jour le compte admin CRM avec une saisie masquee :

```bash
php artisan crm:admin --email=admin@crm.jp2.fr --name="Administrateur"
```

En deploiement non interactif, utiliser une variable temporaire du shell, sans l'ajouter a `.env` :

```powershell
$env:CRM_ADMIN_TMP='MotDePasseFort-2026!'
php artisan crm:admin --email=admin@crm.jp2.fr --name="Administrateur" --password-env=CRM_ADMIN_TMP
Remove-Item Env:\CRM_ADMIN_TMP
```

Compiler les assets :

```bash
npm run build
```

Lancer le serveur local :

```bash
php artisan serve
```

L'application sera disponible sur `http://127.0.0.1:8000`.

## Installation Docker avec Sail

Le projet fournit `docker-compose.yml` avec PHP 8.3, MySQL, Redis et Mailpit :

```bash
cp .env.example .env
composer install
```

Adapter le `.env` local pour Sail :

```dotenv
DB_HOST=mysql
DB_USERNAME=sail
DB_PASSWORD=password
```

Puis lancer la stack :

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

L'application est disponible sur `http://localhost` et Mailpit sur `http://localhost:8025`.

## Developpement

Backend Laravel :

```bash
php artisan serve
```

Frontend Vite :

```bash
npm run dev
```

Le script Composer `dev` lance aussi serveur, queue, logs et Vite via `concurrently` :

```bash
composer run dev
```

## Tests et qualite

Executer tous les tests :

```bash
php artisan test
```

Executer les tests CRM les plus courants :

```bash
php artisan test tests/Feature/CrmReservationApiTest.php tests/Feature/CrmEquipmentRentalApiTest.php tests/Feature/CrmLeaveApiTest.php tests/Feature/CrmPageApiTest.php
```

Verifier le style PHP :

```bash
vendor/bin/pint --test app routes database tests
```

Verifier la compilation frontend :

```bash
npm run build
```

## Application mobile

Le client mobile se trouve dans `mobile/`.

```bash
cd mobile
npm install
cp .env.example .env
npm run dev
```

Configurer l'URL de l'API si elle n'est pas locale :

```env
VITE_API_BASE_URL=https://crm.example.com
```

Build et synchronisation Capacitor :

```bash
npm run build
npm run cap:sync
```

Ouvrir Android Studio :

```bash
npm run cap:open:android
```

Ouvrir Xcode sur macOS :

```bash
npm run cap:open:ios
```

## Preparation production

Sur le serveur, apres envoi du code versionne :

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

Si les dependances Node sont construites ailleurs, envoyer les assets compiles au lieu d'executer `npm install` sur le serveur.

Verifier ensuite :

- `/login`
- `/`
- `/conges`
- `/pages-crm`
- `/admin`
- `/api/conges?action=bootstrap`
- `/api/mobile/me` avec un token Sanctum valide

## Variables importantes

- `APP_URL` : URL publique de l'application.
- `DB_*` : connexion MySQL/MariaDB.
- `CRM_ADMIN_PASSWORD_MIN`, `CRM_ADMIN_HASH_ROUNDS` : politique de mot de passe admin.
- `CRM_API_THROTTLE_PER_MINUTE` : limite minute des API CRM Laravel.
- `CRM_LOGIN_THROTTLE_PER_MINUTE` : limite minute du login web et du token mobile.
- `CRM_RESPONSE_COMPRESSION_*` : activation, taille minimale et niveau gzip des reponses API.
- `CRM_BACKUP_DISK`, `CRM_BACKUP_PATH`, `CRM_BACKUP_KEEP` : destination et retention des sauvegardes SQL.
- `CRM_TRUST_LARAVEL_SESSION` : autorise les API CRM a utiliser la session Laravel.
- `CRM_ALLOW_LEGACY_ACTOR_IMPERSONATION` : compatibilite legacy, a garder desactivee sauf besoin controle.
- `SANCTUM_MOBILE_TOKEN_EXPIRATION_DAYS` : duree des tokens mobiles.
- `CORS_ALLOWED_ORIGINS` : origins autorisees pour l'API mobile et les appels `api/*`.

## CI/CD

Le workflow GitHub Actions `.github/workflows/ci.yml` execute a chaque push ou pull request :

- installation Composer et npm
- Pint en mode verification
- build Vite
- tests Laravel sur PHP 8.3.

## Depannage

Vider les caches Laravel :

```bash
php artisan optimize:clear
```

Regenerer l'autoload Composer :

```bash
composer dump-autoload
```

Verifier les migrations :

```bash
php artisan migrate:status
```

Relancer une compilation propre :

```bash
npm run build
```

Les logs Laravel sont dans `storage/logs/laravel.log`.
