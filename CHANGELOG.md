# Changelog

Toutes les modifications notables du CRM Martin Sols sont documentees ici.

Le format suit une logique proche de "Keep a Changelog" et les releases doivent
etre taguees dans Git avec la convention `vYYYY.MM.DD.N`.

## [Unreleased]

### Added

- Documentation technique dans `docs/README.md` avec architecture, schema ER et flux principaux.
- Convention de release et rappel d'utilisation des tags Git.

### Changed

- `.editorconfig` complete pour mieux cadrer les fichiers PHP, JS, CSS, JSON et Markdown.

## [2026.07.11.3] - 2026-07-11

### Added

- Cache-busting automatique des assets CRM via `App\Support\CrmAsset`.
- Migration d'indexes de performance pour reservations, locations, materiel, vehicules et conges.
- Cron serveur Laravel `schedule:run` toutes les minutes.
- Tache planifiee `sanctum:prune-expired --hours=24`.
- Cache applicatif des categories materiel actives avec invalidation automatique.

### Changed

- Logs Laravel configures en canal `daily` avec conservation sur 30 jours.
- Variables `.env.example` completees pour logs, timezone, securite legacy et assets.

## [2026.07.11.2] - 2026-07-11

### Added

- `FormRequest` dediees pour login, token mobile et actions API CRM.
- `ReservationConflictQuery` pour centraliser les controles de chevauchement.
- Chargements eager loading dans les ressources Filament principales.
- Configuration `CRM_DISPLAY_TIMEZONE`.

### Changed

- Les controles de conflits vehicules, materiel et conges utilisent une classe de query dediee.
- Les validations recurrentes des API CRM sont centralisees.

## [2026.07.11.1] - 2026-07-11

### Added

- Middleware d'audit pour endpoints legacy `.php`.
- Politiques Filament de base pour limiter les acces directs.
- Revocation des tokens Sanctum lors des changements de mot de passe.

### Changed

- Durcissement des mots de passe admin seedes.
- Throttling des endpoints legacy CRM.

## [2026.07.10.1] - 2026-07-10

### Added

- Tableau de bord CRM apres connexion.
- Vue cartes pour location materiel et reservations vehicules.
- Module conges integre au shell CRM.

### Fixed

- Corrections de pages blanches et problemes de routes 404 internes.
- Harmonisation visuelle mobile pour les modules reservations, locations et conges.
