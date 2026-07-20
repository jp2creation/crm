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
source from the licensed template. The production CRM still loads the customized
legacy Adminex bundle from `public/assets` while the reservation and equipment
pages are migrated back to source.

New CRM frontend work should start in `resources/frontend/crm` or in module
`resources/assets` files, not by editing minified files in `public/assets`.
