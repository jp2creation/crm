# Documentation technique CRM

Ce dossier regroupe la documentation technique du CRM Martin Sols : architecture,
schema de donnees, flux principaux, exploitation et deploiement.

## Architecture applicative

```mermaid
flowchart LR
    Browser["Navigateur CRM"] --> WebRoutes["routes/web.php"]
    Mobile["Application mobile"] --> MobileApi["API mobile Sanctum"]
    WebRoutes --> Controllers["Controllers CRM"]
    Controllers --> Requests["Form Requests"]
    Controllers --> Services["Services metier CRM"]
    Services --> Queries["Query builders / conflits"]
    Services --> Models["Modeles Eloquent"]
    Models --> Database[(MySQL / MariaDB)]
    Admin["Filament /admin"] --> Resources["Resources Filament"]
    Resources --> Models
    Scheduler["Laravel scheduler"] --> Jobs["Nettoyage tokens / taches planifiees"]
    Jobs --> Database
```

## Modules principaux

- Authentification web Laravel, avec comptes `users` et droits Spatie.
- Portail CRM React/Blade servi par les vues Laravel.
- Reservations vehicules avec controle de conflits.
- Locations de materiel avec categories, planning, demi-journee ou journee.
- Conges bases sur les utilisateurs CRM existants lies au site.
- Pages CRM administrables.
- Administration Filament pour les donnees de reference et permissions.
- API mobile via Sanctum.
- Endpoints legacy `.php` conserves pour compatibilite et audites par middleware.
- Creation admin par commande Artisan `crm:admin`, sans mot de passe stocke dans `.env.example`.

## Schema ER simplifie

```mermaid
erDiagram
    users ||--o| crm_users : "compte CRM"
    crm_users }o--o{ crm_sites : "acces sites"
    crm_users }o--o{ crm_modules : "modules"
    crm_users }o--o{ crm_permissions : "permissions"

    crm_sites ||--o{ crm_vehicles : "vehicules"
    crm_sites ||--o{ crm_reservations : "reservations"
    crm_sites ||--o{ crm_equipment_items : "materiel"
    crm_sites ||--o{ crm_equipment_rentals : "locations"

    crm_vehicles ||--o{ crm_reservations : "planning"
    crm_users ||--o{ crm_reservations : "cree par"

    crm_equipment_categories ||--o{ crm_equipment_items : "classe"
    crm_equipment_items ||--o{ crm_equipment_rentals : "planning"
    crm_users ||--o{ crm_equipment_rentals : "cree par"

    crm_users ||--o| crm_leave_employees : "profil conges"
    crm_leave_employees ||--o{ crm_leave_entries : "absences"

    crm_menu_groups ||--o{ crm_menu_items : "navigation"
    crm_users ||--o{ crm_logs : "actions"
```

## Tables metier

- `crm_sites` : sites disponibles dans le CRM.
- `crm_users` : profil CRM relie au compte Laravel `users`.
- `crm_modules`, `crm_permissions` : activation des modules et droits fins.
- `crm_user_sites`, `crm_user_modules`, `crm_user_permissions` : pivots de droits CRM.
- `crm_vehicles`, `crm_reservations` : flotte et planning vehicules.
- `crm_equipment_categories`, `crm_equipment_items`, `crm_equipment_rentals` : materiel et locations.
- `crm_leave_employees`, `crm_leave_entries` : profils conges et absences.
- `crm_menu_groups`, `crm_menu_items` : structure du menu.
- `crm_pages` : pages internes administrables.
- `crm_logs` : journal metier.
- `personal_access_tokens` : tokens Sanctum mobile.

## Flux principaux

### Connexion web

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant L as LoginController
    participant S as Session Laravel
    participant C as CRM

    U->>L: POST /login
    L->>S: authentifie le compte users
    S-->>U: session web
    U->>C: GET /
    C-->>U: portail CRM selon droits/site
```

### Reservation vehicule

```mermaid
sequenceDiagram
    participant UI as Interface CRM
    participant API as ReservationApiController
    participant Service as ReservationService
    participant Conflict as ReservationConflictQuery
    participant DB as MySQL

    UI->>API: action=create_reservation
    API->>Service: payload valide
    Service->>DB: DB::transaction()
    Service->>Conflict: verifie chevauchement avec verrou
    Conflict->>DB: lockForUpdate()
    Service->>DB: cree reservation
    Service-->>UI: reservation creee
```

### Location materiel

```mermaid
sequenceDiagram
    participant UI as Interface CRM
    participant API as EquipmentRentalApiController
    participant Service as EquipmentRentalService
    participant Cache as Cache Laravel
    participant DB as MySQL

    UI->>API: action=bootstrap
    API->>Service: charge donnees du module
    Service->>Cache: categories actives
    Cache-->>Service: rememberForever ou DB
    Service->>DB: materiel, locations, sites, users
    Service-->>UI: planning materiel
```

### Conges

```mermaid
sequenceDiagram
    participant UI as Interface CRM
    participant API as LeaveApiController
    participant Service as LeaveService
    participant DB as MySQL

    UI->>API: action=save_leave
    API->>Service: utilisateur + site
    Service->>DB: transaction
    Service->>DB: employe CRM existant lie au site
    Service->>DB: controle chevauchement
    Service->>DB: cree ou modifie le conge
    Service-->>UI: planning conges actualise
```

## Assets, logs et cache

- Les assets servis depuis `public/assets` sont appeles avec `App\Support\CrmAsset`.
- La version d'asset est calculee par `filemtime`, ou forcee par `CRM_ASSET_VERSION`.
- Les logs Laravel utilisent le canal `daily`, avec `LOG_DAILY_DAYS=30`.
- Le cache applicatif utilise le store configure par `CACHE_STORE`.
- Les categories materiel actives sont mises en cache et invalidees lors des modifications.

## Scheduler

Le scheduler Laravel doit etre execute toutes les minutes par cron :

```cron
* * * * * cd /home/jpfronpi/crm && php artisan schedule:run >> /dev/null 2>&1
```

Taches actuellement planifiees :

- `sanctum:prune-expired --hours=24`, chaque jour a `02:15`.

## Deploiement

Voir [DEPLOYMENT.md](DEPLOYMENT.md).

Commandes de controle apres deploiement :

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan schedule:list
php artisan view:cache
php artisan view:clear
php artisan test
```

## Releases

Les releases doivent etre documentees dans `CHANGELOG.md` puis taguees dans Git.

Convention recommandee :

```bash
git tag -a vYYYY.MM.DD.N -m "Release vYYYY.MM.DD.N"
git push origin vYYYY.MM.DD.N
```

Ne pas creer de tag tant que les modifications de la release ne sont pas commitees.
