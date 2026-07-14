<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @include('partials.pwa-head')
    <title>Martin Sols - CRM</title>

    <script>
      (function () {
        try {
          var saved = localStorage.getItem('martin-sols-crm-theme-v2')
          if (!saved) return

          var config = JSON.parse(saved)
          var root = document.documentElement

          if (config && config.mode === 'dark') root.classList.add('dark')
          if (config && config.direction === 'rtl') root.dir = 'rtl'

          var presets = {
            blue: ['59 130 246', '99 102 241'],
            purple: ['236 72 153', '14 165 233'],
            green: ['34 197 94', '20 184 166'],
            orange: ['249 115 22', '245 158 11'],
            red: ['239 68 68', '244 63 94'],
            cyan: ['149 0 46', '149 0 46'],
          }

          if (config && config.color && presets[config.color]) {
            root.style.setProperty('--theme-primary', presets[config.color][0])
            root.style.setProperty('--theme-accent', presets[config.color][1])
          }

          if (config && config.sidebarLayout) root.dataset.sidebarLayout = config.sidebarLayout
          if (config && config.container) root.dataset.container = config.container
          if (config && config.cardStyle) root.dataset.cardStyle = config.cardStyle
        } catch (e) {
          // Theme preference is optional.
        }
      })()
    </script>
    <script>
      (function () {
        try {
          localStorage.setItem('adminex-locale', 'fr')
        } catch (e) {
          // Locale preference is optional.
        }
      })()
    </script>
    <script>
      window.CRM_NAV_FALLBACK = {
        modules: [
          { name: 'Tableau de bord', slug: 'dashboard', routePath: '/dashboard/crm', active: true, sortOrder: 0 },
          { name: 'Réservations véhicules', slug: 'reservations', routePath: '/reservations', active: true, sortOrder: 10, menuBadge: 'Martin', showMenuBadge: true },
          { name: 'Location matériel', slug: 'locations-materiel', routePath: '/locations-materiel', active: true, sortOrder: 15 },
          { name: 'Équipe', slug: 'equipes', routePath: '/equipes', active: true, sortOrder: 16 },
          { name: 'Promo', slug: 'documents-promo', routePath: '/documents/promo', active: true, sortOrder: 241 },
          { name: 'Fiches techniques', slug: 'documents-fiches-techniques', routePath: '/documents/fiches-techniques', active: true, sortOrder: 242 },
          { name: 'Procédures', slug: 'documents-procedures', routePath: '/documents/procedures', active: true, sortOrder: 243 },
          { name: 'Tapis ROMUS', slug: 'tapis-romus', routePath: '/tapis-romus', active: true, sortOrder: 60 },
          { name: 'Contrôle caisse', slug: 'controle-caisse', routePath: '/controle-caisse', active: true, sortOrder: 25 },
          { name: 'Demande d\'acompte', slug: 'demandes-acompte', routePath: '/demandes-acompte', active: true, sortOrder: 26 },
          { name: 'Remise de chèques', slug: 'remise-cheques', routePath: '/remise-cheques', active: true, sortOrder: 27 },
          { name: 'Addvance', slug: 'addvance', routePath: 'https://martinsols.addvancesolutions.fr', active: true, sortOrder: 28 },
          { name: 'Pages CRM', slug: 'pages-crm', routePath: '/pages-crm', active: true, sortOrder: 18 },
          { name: 'Administration', slug: 'administration', routePath: '/administration', active: true, sortOrder: 20 },
          { name: 'Congés', slug: 'conges', routePath: '/conges', active: true, sortOrder: 24 }
        ],
        menuGroups: [
          { menuKey: 'home', title: 'Accueil', active: true, sortOrder: 0 },
          { menuKey: 'apps', title: 'Applications CRM', active: true, sortOrder: 10 },
          { menuKey: 'accounting', title: 'Comptabilité', active: true, sortOrder: 18 },
          { menuKey: 'internal', title: 'Administration', active: true, sortOrder: 20 }
        ],
        menuItems: [
          { itemKey: 'module:dashboard', groupKey: 'home', label: 'Tableau de bord', iconKey: 'dashboard', active: true, sortOrder: 0 },
          { itemKey: 'module:reservations', groupKey: 'apps', label: 'Réservations véhicules', iconKey: 'truck', active: true, sortOrder: 10 },
          { itemKey: 'module:locations-materiel', groupKey: 'apps', label: 'Location matériel', iconKey: 'package', active: true, sortOrder: 15 },
          { itemKey: 'module:equipes', groupKey: 'apps', label: 'Équipe', iconKey: 'users', active: true, sortOrder: 16 },
          { itemKey: 'module:documents-promo', groupKey: 'apps', label: 'Promo', iconKey: 'article', active: true, sortOrder: 22 },
          { itemKey: 'module:documents-fiches-techniques', groupKey: 'apps', label: 'Fiches techniques', iconKey: 'article', active: true, sortOrder: 23 },
          { itemKey: 'module:documents-procedures', groupKey: 'apps', label: 'Procédures', iconKey: 'article', active: true, sortOrder: 24 },
          { itemKey: 'module:tapis-romus', groupKey: 'apps', label: 'Tapis ROMUS', iconKey: 'table', active: true, sortOrder: 60 },
          { itemKey: 'module:controle-caisse', groupKey: 'accounting', label: 'Contrôle caisse', iconKey: 'creditCard', active: true, sortOrder: 10 },
          { itemKey: 'module:demandes-acompte', groupKey: 'accounting', label: 'Demande d\'acompte', iconKey: 'banknote', active: true, sortOrder: 26 },
          { itemKey: 'module:remise-cheques', groupKey: 'accounting', label: 'Remise de chèques', iconKey: 'creditCard', active: true, sortOrder: 27 },
          { itemKey: 'module:addvance', groupKey: 'accounting', label: 'Addvance', iconKey: 'creditCard', active: true, sortOrder: 28 },
          { itemKey: 'module:pages-crm', groupKey: 'internal', label: 'Pages CRM', iconKey: 'article', active: true, sortOrder: 18 },
          { itemKey: 'module:administration', groupKey: 'internal', label: 'Administration', iconKey: 'settings', active: true, sortOrder: 20 },
          { itemKey: 'module:conges', groupKey: 'internal', label: 'Congés', iconKey: 'calendar', active: true, sortOrder: 24 }
        ]
      }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
      rel="stylesheet"
    />
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/crm-active-site.js') }}"></script>
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/crm-text-fixes.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('assets/index-CqSzWeas.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-core/crm-dashboard.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-leaves/crm-conges.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-cash-control/crm-controle-caisse.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-deposit-requests/crm-demandes-acompte.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-check-remittances/crm-remise-cheques.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-documents/crm-documents.js') }}"></script>
    <script defer src="{{ \App\Support\CrmAsset::url('modules/crm-pages/crm-pages.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-teams/crm-equipes.js') }}"></script>
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/crm-account-settings.js') }}"></script>
    <link rel="stylesheet" crossorigin href="{{ \App\Support\CrmAsset::url('assets/index-CVBlw941.css') }}">
    <style>
      .layout-header .header-search-wrap,
      .layout-header .header-mobile-panel,
      .layout-header .header-actions-divider,
      .layout-header .ms-auto > div.hidden.sm\:block:has(img[src*="/assets/flags/"]),
      .layout-header .ms-auto > button.header-icon-btn.header-icon-btn-mobile {
        display: none !important;
      }

      .layout-header nav.hidden.min-w-0.flex-1.items-center {
        display: none !important;
      }

      @media (min-width: 768px) and (max-width: 1023.98px) {
        body:has(aside.translate-x-0) .layout-header {
          left: var(--sidebar-width, 260px) !important;
        }

        body:has(aside.translate-x-0) main {
          margin-left: var(--sidebar-width, 260px) !important;
          width: calc(100% - var(--sidebar-width, 260px)) !important;
          max-width: calc(100% - var(--sidebar-width, 260px)) !important;
        }

        body:has(aside.translate-x-0) .layout-container.layout-page {
          max-width: 100%;
          overflow-x: hidden;
        }

        body:has(aside.translate-x-0) .fixed.inset-0[class*="bg-black/50"][class*="z-[1025]"] {
          display: none !important;
        }
      }

      body.crm-mobile-embed {
        overflow-x: hidden;
        background: #f8fafc;
      }

      body.crm-mobile-embed aside,
      body.crm-mobile-embed .layout-header,
      body.crm-mobile-embed .fixed.inset-0[class*="bg-black/50"],
      body.crm-mobile-embed .header-mobile-panel {
        display: none !important;
      }

      body.crm-mobile-embed main {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding-top: 0 !important;
      }

      body.crm-mobile-embed .layout-container.layout-page {
        width: 100% !important;
        max-width: 100% !important;
        padding: .75rem !important;
      }

      body.crm-mobile-embed [class*="ml-[var(--sidebar-width"],
      body.crm-mobile-embed [class*="lg:ml-[var(--sidebar-width"] {
        margin-left: 0 !important;
      }
    </style>
  </head>
  <body class="{{ request()->boolean('mobile_embed') ? 'crm-mobile-embed' : '' }}">
    <div id="root"></div>
    @unless(request()->boolean('mobile_embed'))
      @include('partials.pwa-scripts')
    @endunless
  </body>
</html>
