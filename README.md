# Martin Sols CRM

CRM interne pour centraliser les outils operationnels Martin Sols : portail web, administration, reservations, locations de materiel, conges, pages de contenu et API mobile.

## Objectif

Ce depot contient l'application Laravel qui remplace les anciens endpoints PHP disperses par une base applicative versionnee, testable et extensible. L'interface principale sert aux equipes internes, tandis que l'administration Filament permet de maintenir les donnees de reference, les droits et les contenus.

## Fonctionnalites principales

- Authentification web Laravel et portail CRM protege.
- Tableau de bord et navigation multi-sites.
- Gestion des reservations et des vehicules.
- Gestion des locations de materiel, categories, disponibilites et conflits.
- Planning des conges avec employees, statuts, demi-journees et droits de gestion.
- Pages CRM administrables et accessibles via slugs.
- Administration Filament pour utilisateurs, roles, modules, menus, sites, vehicules, materiel et contenus.
- API legacy compatible avec les anciens chemins `.php`.
- API mobile Laravel Sanctum pour l'application Capacitor du dossier `mobile/`.

## Stack technique

- PHP 8.2+
- Laravel 12
- Filament 5
- Laravel Sanctum
- Spatie Laravel Permission
- MySQL ou MariaDB
- Node.js, npm, Vite et Tailwind CSS
- PHPUnit pour les tests backend
- Capacitor pour le client mobile

## Acces applicatifs

- Portail CRM : `/`
- Connexion : `/login`
- Administration : `/admin`
- Conges : `/conges`
- Pages CRM : `/pages-crm`
- API reservations : `/api/reservations`
- API locations de materiel : `/api/equipment-rentals`
- API conges : `/api/conges`
- API pages : `/api/pages`
- API mobile : `/api/mobile/token`, `/api/mobile/me`, `/api/mobile/logout`

Les routes legacy en `.php` restent exposees pour compatibilite, par exemple `/api/conges.php`.

## Structure du depot

- `app/Http/Controllers/Crm/` : endpoints CRM web, legacy et mobile.
- `app/Services/Crm/` : logique metier des modules CRM.
- `app/Filament/Resources/` : back-office Filament.
- `database/migrations/` : schema Laravel et tables CRM.
- `resources/views/` : vues CRM, login et pages.
- `public/assets/` : assets compiles servis en production.
- `tests/Feature/` : tests fonctionnels des API CRM.
- `mobile/` : application mobile Capacitor connectee a l'API Sanctum.
- `docs/` : notes de deploiement et de durcissement.

## Installation

Les instructions completes sont dans [INSTALLATION.md](INSTALLATION.md).

Resume local :

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Avant d'executer les seeds, renseigner au minimum `CRM_ADMIN_PASSWORD` dans `.env`.

## Verification

```bash
php artisan test
vendor/bin/pint --test app routes database tests
npm run build
```

Des tests cibles existent notamment pour les reservations, les locations de materiel, les conges, les pages CRM et l'authentification mobile.

## Deploiement

La procedure de reference est dans [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

Regle importante : les fichiers de `public/assets` sont des sorties compilees. Une correction durable doit etre faite dans les sources applicatives puis reconstruite.

## Securite

- Ne jamais commiter `.env`, tokens, exports de base de donnees ou logs.
- Utiliser des mots de passe forts pour les comptes admin.
- Desactiver les options legacy d'impersonation sauf besoin explicite.
- L'API mobile utilise Sanctum et des tokens Bearer ; leur duree est pilotee par `SANCTUM_MOBILE_TOKEN_EXPIRATION_DAYS`.
