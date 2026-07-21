# CRM Frontend

This directory is the versioned source for the CRM browser application.

`resources/frontend/crm` is the active Laravel/Vite shell. It owns CRM boot code,
shared API helpers, mobile/PWA bridges, fallback navigation, shared styles, and
module registration.

`resources/views/crm.blade.php` must stay intentionally thin: server meta tags,
the JSON shell configuration (`#crm-shell-config`), `#root`, and the Vite entry.
Theme boot, logout interception, mobile settings, loader activation, and legacy
bridges belong in the versioned TypeScript sources.

`resources/frontend/adminex` contains the original Adminex React/TypeScript
source from the licensed template. It is compiled by the main Vite build and is
the target location for the ongoing migration.

`resources/frontend/static/assets` contains versioned public assets such as
logos, PWA icons, flags, avatars, demo product images, and the transitional
`legacy-adminex-*` snapshot still required by Reservations and Equipment
Rentals. They are published to `public/assets` by
`php artisan crm:publish-static-assets --force --clean`.

New CRM frontend work should start in `resources/frontend/crm`, in
`resources/frontend/adminex`, or in module `resources/assets` files, not by
editing generated files in `public` or expanding the legacy Adminex snapshot.
