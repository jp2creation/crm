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
          { name: 'Congés', slug: 'conges', routePath: '/conges', active: true, sortOrder: 17 },
          { name: 'Rapport de visite', slug: 'tournees-representants', routePath: '/rapport-visite', active: true, sortOrder: 18 },
          { name: 'Promo', slug: 'documents-promo', routePath: '/documents/promo', active: true, sortOrder: 241 },
          { name: 'Fiches techniques', slug: 'documents-fiches-techniques', routePath: '/documents/fiches-techniques', active: true, sortOrder: 242 },
          { name: 'Procédures', slug: 'documents-procedures', routePath: '/documents/procedures', active: true, sortOrder: 243 },
          { name: 'Tapis ROMUS', slug: 'tapis-romus', routePath: '/tapis-romus', active: true, sortOrder: 60 },
          { name: 'Contrôle caisse', slug: 'controle-caisse', routePath: '/controle-caisse', active: true, sortOrder: 25 },
          { name: 'Demande d\'acompte', slug: 'demandes-acompte', routePath: '/demandes-acompte', active: true, sortOrder: 26 },
          { name: 'Remise de chèques', slug: 'remise-cheques', routePath: '/remise-cheques', active: true, sortOrder: 27 },
          { name: 'Addvance', slug: 'addvance', routePath: 'https://martinsols.addvancesolutions.fr', active: true, sortOrder: 28 },
          { name: 'Pages CRM', slug: 'pages-crm', routePath: '/pages-crm', active: true, sortOrder: 18 },
          { name: 'Administration', slug: 'administration', routePath: '/administration', active: true, sortOrder: 20 }
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
          { itemKey: 'module:conges', groupKey: 'apps', label: 'Congés', iconKey: 'calendar', active: true, sortOrder: 17 },
          { itemKey: 'module:tournees-representants', groupKey: 'apps', label: 'Rapport de visite', iconKey: 'calendar', active: true, sortOrder: 18 },
          { itemKey: 'module:documents-promo', groupKey: 'apps', label: 'Promo', iconKey: 'article', active: true, sortOrder: 22 },
          { itemKey: 'module:documents-fiches-techniques', groupKey: 'apps', label: 'Fiches techniques', iconKey: 'article', active: true, sortOrder: 23 },
          { itemKey: 'module:documents-procedures', groupKey: 'apps', label: 'Procédures', iconKey: 'article', active: true, sortOrder: 24 },
          { itemKey: 'module:tapis-romus', groupKey: 'apps', label: 'Tapis ROMUS', iconKey: 'table', active: true, sortOrder: 60 },
          { itemKey: 'module:controle-caisse', groupKey: 'accounting', label: 'Contrôle caisse', iconKey: 'creditCard', active: true, sortOrder: 10 },
          { itemKey: 'module:demandes-acompte', groupKey: 'accounting', label: 'Demande d\'acompte', iconKey: 'banknote', active: true, sortOrder: 26 },
          { itemKey: 'module:remise-cheques', groupKey: 'accounting', label: 'Remise de chèques', iconKey: 'creditCard', active: true, sortOrder: 27 },
          { itemKey: 'module:addvance', groupKey: 'accounting', label: 'Addvance', iconKey: 'creditCard', active: true, sortOrder: 28 },
          { itemKey: 'module:pages-crm', groupKey: 'internal', label: 'Pages CRM', iconKey: 'article', active: true, sortOrder: 18 },
          { itemKey: 'module:administration', groupKey: 'internal', label: 'Administration', iconKey: 'settings', active: true, sortOrder: 20 }
        ]
      }
    </script>
    <script data-crm-logout-bridge>
      (function () {
        var legacyLogoutPath = '/auth/login'
        var logoutUrl = @json(route('logout', [], false))
        var loginUrl = @json(route('login', [], false))
        var csrfToken = @json(csrf_token())
        var logoutInProgress = false

        function isLegacyLogoutUrl(value) {
          if (!value) return false

          try {
            var url = new URL(value, window.location.href)
            var path = url.pathname.replace(/\/+$/, '') || '/'

            return url.origin === window.location.origin && path === legacyLogoutPath
          } catch (e) {
            return false
          }
        }

        function submitLogoutFallback() {
          var form = document.createElement('form')
          var token = document.createElement('input')

          form.method = 'POST'
          form.action = logoutUrl
          form.style.display = 'none'

          token.type = 'hidden'
          token.name = '_token'
          token.value = csrfToken

          form.appendChild(token)
          document.body.appendChild(form)
          form.submit()
        }

        function logoutToCrmLogin() {
          if (logoutInProgress) return

          logoutInProgress = true

          fetch(logoutUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Accept': 'text/html,application/xhtml+xml',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
            .then(function (response) {
              if (!response.ok) {
                throw new Error('Logout failed')
              }

              window.location.replace(loginUrl)
            })
            .catch(function () {
              submitLogoutFallback()
            })
        }

        document.addEventListener('click', function (event) {
          var target = event.target
          var link = target && typeof target.closest === 'function' ? target.closest('a[href]') : null

          if (!link || !isLegacyLogoutUrl(link.href)) return

          event.preventDefault()
          event.stopImmediatePropagation()
          logoutToCrmLogin()
        }, true)

        ;['pushState', 'replaceState'].forEach(function (method) {
          var original = history[method]

          history[method] = function patchedLegacyLogoutRoute(state, title, url) {
            if (isLegacyLogoutUrl(url)) {
              logoutToCrmLogin()
              return
            }

            return original.apply(this, arguments)
          }
        })

        window.MartinSolsCrmLogout = logoutToCrmLogin
      })()
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
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-sales-tours/crm-tournees-representants.js') }}"></script>
    <script defer src="{{ \App\Support\CrmAsset::url('modules/crm-pages/crm-pages.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('modules/crm-teams/crm-equipes.js') }}"></script>
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/crm-account-settings.js') }}"></script>
    <link rel="stylesheet" href="{{ \App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader.css') }}">
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

      body.crm-mobile-app {
        overflow-x: hidden;
        background: #f8fafc;
        padding-top: max(34px, env(safe-area-inset-top));
        padding-bottom: max(54px, env(safe-area-inset-bottom));
      }

      body.crm-mobile-app .layout-header {
        top: max(34px, env(safe-area-inset-top)) !important;
      }

      body.crm-mobile-app .layout-container.layout-page {
        padding-bottom: max(1.5rem, 54px) !important;
      }

      body.crm-mobile-app.crm-mobile-fallback-nav-active {
        padding-top: calc(max(34px, env(safe-area-inset-top)) + 64px);
      }

      .crm-mobile-fallback-header {
        position: fixed;
        inset: max(34px, env(safe-area-inset-top)) 0 auto 0;
        z-index: 9989;
        height: 64px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 16px;
        border-bottom: 1px solid rgb(226 232 240);
        background: rgb(255 255 255 / .96);
        box-shadow: 0 8px 24px rgb(15 23 42 / .08);
        backdrop-filter: blur(14px);
      }

      .crm-mobile-fallback-menu-button,
      .crm-mobile-fallback-close {
        display: inline-grid;
        width: 42px;
        height: 42px;
        place-items: center;
        border: 1px solid rgb(226 232 240);
        border-radius: 8px;
        background: #fff;
        color: rgb(29 53 79);
      }

      .crm-mobile-fallback-menu-button svg,
      .crm-mobile-fallback-close svg {
        width: 24px;
        height: 24px;
      }

      .crm-mobile-fallback-brand {
        display: flex;
        min-width: 0;
        flex: 1;
        align-items: center;
        justify-content: center;
      }

      .crm-mobile-fallback-brand img {
        width: min(172px, 52vw);
        max-height: 42px;
        object-fit: contain;
      }

      .crm-mobile-fallback-drawer[hidden] {
        display: none !important;
      }

      .crm-mobile-fallback-drawer {
        position: fixed;
        inset: 0;
        z-index: 9992;
        display: grid;
        grid-template-columns: minmax(0, 82vw) 1fr;
      }

      .crm-mobile-fallback-backdrop {
        grid-column: 1 / -1;
        grid-row: 1;
        border: 0;
        background: rgb(15 23 42 / .48);
      }

      .crm-mobile-fallback-panel {
        position: relative;
        grid-column: 1;
        grid-row: 1;
        display: flex;
        min-width: 0;
        max-width: 380px;
        flex-direction: column;
        overflow: hidden;
        border-right: 1px solid rgb(226 232 240);
        background: #fff;
        box-shadow: 18px 0 42px rgb(15 23 42 / .18);
      }

      .crm-mobile-fallback-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-height: calc(max(34px, env(safe-area-inset-top)) + 78px);
        padding: max(22px, calc(env(safe-area-inset-top) + 18px)) 18px 14px;
        border-bottom: 1px solid rgb(226 232 240);
      }

      .crm-mobile-fallback-panel-header img {
        width: 168px;
        max-width: 62vw;
        object-fit: contain;
      }

      .crm-mobile-fallback-nav {
        flex: 1;
        overflow: auto;
        padding: 16px 12px 18px;
      }

      .crm-mobile-fallback-group + .crm-mobile-fallback-group {
        margin-top: 18px;
      }

      .crm-mobile-fallback-group-title {
        margin: 0 0 8px;
        padding: 0 10px;
        color: rgb(100 116 139);
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0;
      }

      .crm-mobile-fallback-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 48px;
        padding: 10px 12px;
        border-radius: 8px;
        color: rgb(29 53 79);
        font-size: 16px;
        font-weight: 800;
        text-decoration: none;
      }

      .crm-mobile-fallback-link.is-active {
        background: rgb(149 0 46);
        color: #fff;
      }

      .crm-mobile-fallback-initials {
        display: inline-grid;
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        place-items: center;
        border-radius: 8px;
        background: rgb(248 250 252);
        color: rgb(149 0 46);
        font-size: 12px;
        font-weight: 950;
      }

      .crm-mobile-fallback-link.is-active .crm-mobile-fallback-initials {
        background: rgb(255 255 255 / .16);
        color: #fff;
      }

      body.crm-mobile-embed:not(.crm-mobile-app) aside,
      body.crm-mobile-embed:not(.crm-mobile-app) .layout-header,
      body.crm-mobile-embed:not(.crm-mobile-app) .fixed.inset-0[class*="bg-black/50"],
      body.crm-mobile-embed:not(.crm-mobile-app) .header-mobile-panel {
        display: none !important;
      }

      body.crm-mobile-embed:not(.crm-mobile-app) main {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        padding-top: 0 !important;
      }

      body.crm-mobile-embed:not(.crm-mobile-app) .layout-container.layout-page {
        width: 100% !important;
        max-width: 100% !important;
        padding: .75rem !important;
      }

      body.crm-mobile-embed:not(.crm-mobile-app) [class*="ml-[var(--sidebar-width"],
      body.crm-mobile-embed:not(.crm-mobile-app) [class*="lg:ml-[var(--sidebar-width"] {
        margin-left: 0 !important;
      }

      .crm-mobile-app-settings-trigger {
        position: fixed;
        right: 14px;
        bottom: max(18px, calc(env(safe-area-inset-bottom) + 14px));
        z-index: 9990;
        display: grid;
        width: 42px;
        height: 42px;
        place-content: center;
        grid-template-columns: repeat(3, 4px);
        gap: 3px;
        border: 1px solid rgb(226 232 240);
        border-radius: 999px;
        background: rgb(255 255 255 / .95);
        color: rgb(149 0 46);
        box-shadow: 0 12px 28px rgb(29 53 79 / .18);
        backdrop-filter: blur(14px);
      }

      .crm-mobile-app-settings-trigger span {
        width: 4px;
        height: 4px;
        border-radius: 999px;
        background: currentColor;
      }

      .crm-mobile-app-settings[hidden] {
        display: none !important;
      }

      .crm-mobile-app-settings {
        position: fixed;
        inset: 0;
        z-index: 9991;
        display: grid;
        place-items: end center;
        padding: 18px 14px max(18px, env(safe-area-inset-bottom));
      }

      .crm-mobile-app-settings-backdrop {
        position: absolute;
        inset: 0;
        border: 0;
        background: rgb(15 23 42 / .38);
      }

      .crm-mobile-app-settings-panel {
        position: relative;
        width: min(100%, 430px);
        max-height: min(86dvh, 620px);
        overflow: auto;
        display: grid;
        gap: 14px;
        padding: 18px;
        border: 1px solid rgb(226 232 240);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 24px 70px rgb(15 23 42 / .24);
        color: rgb(29 53 79);
        font-family: "DM Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      .crm-mobile-app-settings-header,
      .crm-mobile-app-settings-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
      }

      .crm-mobile-app-settings-header p {
        margin: 0 0 5px;
        color: rgb(93 114 135);
        font-size: .72rem;
        font-weight: 800;
        line-height: 1;
        text-transform: uppercase;
      }

      .crm-mobile-app-settings-header h2 {
        margin: 0;
        color: rgb(29 53 79);
        font-size: 1.22rem;
        line-height: 1.2;
      }

      .crm-mobile-app-settings-close {
        width: 38px;
        height: 38px;
        border: 1px solid rgb(226 232 240);
        border-radius: 8px;
        background: rgb(244 244 245);
        color: rgb(60 84 110);
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
      }

      .crm-mobile-app-settings-switches {
        display: grid;
        gap: 10px;
      }

      .crm-mobile-app-settings-switch {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        min-height: 48px;
        padding: 10px 12px;
        border: 1px solid rgb(226 232 240);
        border-radius: 8px;
        background: rgb(250 250 250);
        color: rgb(29 53 79);
        font-size: .92rem;
        font-weight: 800;
      }

      .crm-mobile-app-settings-switch input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
      }

      .crm-mobile-app-settings-switch i {
        position: relative;
        flex: none;
        width: 42px;
        height: 24px;
        border-radius: 999px;
        background: rgb(189 198 207);
        transition: background-color .16s ease;
      }

      .crm-mobile-app-settings-switch i::after {
        content: "";
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 2px 6px rgb(15 23 42 / .18);
        transition: transform .16s ease;
      }

      .crm-mobile-app-settings-switch input:checked + i {
        background: rgb(149 0 46);
      }

      .crm-mobile-app-settings-switch input:checked + i::after {
        transform: translateX(18px);
      }

      .crm-mobile-app-settings-status {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }

      .crm-mobile-app-settings-status div {
        min-width: 0;
        padding: 12px;
        border: 1px solid rgb(226 232 240);
        border-radius: 8px;
        background: rgb(250 250 250);
      }

      .crm-mobile-app-settings-status span,
      .crm-mobile-app-settings-version {
        display: block;
        color: rgb(93 114 135);
        font-size: .72rem;
        font-weight: 800;
        line-height: 1.2;
        text-transform: uppercase;
      }

      .crm-mobile-app-settings-status strong {
        display: block;
        margin-top: 6px;
        overflow-wrap: anywhere;
        color: rgb(29 53 79);
        font-size: .86rem;
        line-height: 1.25;
      }

      .crm-mobile-app-settings-error {
        display: none;
        margin: 0;
        padding: 9px 11px;
        border: 1px solid rgb(254 202 202);
        border-radius: 8px;
        background: rgb(254 242 242);
        color: rgb(185 28 28);
        font-size: .86rem;
        font-weight: 800;
        line-height: 1.35;
      }

      .crm-mobile-app-settings-error.is-visible {
        display: block;
      }

      .crm-mobile-app-settings-actions button {
        min-height: 40px;
        padding: 9px 12px;
        border-radius: 8px;
        font-size: .9rem;
        font-weight: 800;
      }

      .crm-mobile-app-settings-secondary {
        border: 1px solid rgb(226 232 240);
        background: rgb(244 244 245);
        color: rgb(60 84 110);
      }

      .crm-mobile-app-settings-primary {
        border: 1px solid transparent;
        background: rgb(149 0 46);
        color: #fff;
      }

      @media (max-width: 380px) {
        .crm-mobile-app-settings-status {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>
  @php($isCrmMobileApp = session('crm_mobile_app') || request()->boolean('mobile_app'))
  @php($crmBodyClass = trim((request()->boolean('mobile_embed') ? 'crm-mobile-embed ' : '').($isCrmMobileApp ? 'crm-mobile-app' : '')))
  <body class="{{ $crmBodyClass }}">
    @include('partials.brand-morph-loader')
    <script>
      (function () {
        var loader = document.getElementById('brand-morph-loader')
        if (!loader) return

        loader.classList.add('is-visible')
        loader.setAttribute('aria-hidden', 'false')
      })()
    </script>
    <div id="root"></div>
    @if($isCrmMobileApp)
      <button class="crm-mobile-app-settings-trigger" type="button" data-crm-mobile-settings-toggle aria-label="Parametres de l'app">
        <span></span><span></span><span></span>
      </button>
      <div class="crm-mobile-app-settings" data-crm-mobile-settings hidden>
        <button class="crm-mobile-app-settings-backdrop" type="button" data-crm-mobile-settings-close aria-label="Fermer"></button>
        <section class="crm-mobile-app-settings-panel" role="dialog" aria-modal="true" aria-label="Parametres de l'app">
          <div class="crm-mobile-app-settings-header">
            <div>
              <p>Application</p>
              <h2>Parametres</h2>
            </div>
            <button class="crm-mobile-app-settings-close" type="button" data-crm-mobile-settings-close aria-label="Fermer">&times;</button>
          </div>

          <div class="crm-mobile-app-settings-switches">
            <label class="crm-mobile-app-settings-switch">
              <span>Localisation</span>
              <input data-crm-mobile-location-enabled type="checkbox">
              <i aria-hidden="true"></i>
            </label>
            <label class="crm-mobile-app-settings-switch">
              <span>Haute precision</span>
              <input data-crm-mobile-location-accuracy type="checkbox">
              <i aria-hidden="true"></i>
            </label>
          </div>

          <div class="crm-mobile-app-settings-status">
            <div>
              <span>Reseau</span>
              <strong data-crm-mobile-network-status>En ligne</strong>
            </div>
            <div>
              <span>Localisation</span>
              <strong data-crm-mobile-location-status>Desactivee</strong>
            </div>
            <div>
              <span>Version</span>
              <strong>App mobile</strong>
            </div>
            <div>
              <span>WebView</span>
              <strong data-crm-mobile-platform>Android</strong>
            </div>
          </div>

          <p class="crm-mobile-app-settings-error" data-crm-mobile-settings-error></p>

          <div class="crm-mobile-app-settings-actions">
            <button class="crm-mobile-app-settings-secondary" type="button" data-crm-mobile-test-location>Tester localisation</button>
            <button class="crm-mobile-app-settings-primary" type="button" data-crm-mobile-settings-close>Fermer</button>
          </div>
        </section>
      </div>
      <script data-crm-mobile-app-settings>
        (function () {
          var storageKey = 'martin-sols.crm.mobile-app.settings'
          var modal = document.querySelector('[data-crm-mobile-settings]')
          var toggle = document.querySelector('[data-crm-mobile-settings-toggle]')
          var closeButtons = document.querySelectorAll('[data-crm-mobile-settings-close]')
          var locationEnabled = document.querySelector('[data-crm-mobile-location-enabled]')
          var locationAccuracy = document.querySelector('[data-crm-mobile-location-accuracy]')
          var networkStatus = document.querySelector('[data-crm-mobile-network-status]')
          var locationStatus = document.querySelector('[data-crm-mobile-location-status]')
          var platformStatus = document.querySelector('[data-crm-mobile-platform]')
          var errorBox = document.querySelector('[data-crm-mobile-settings-error]')
          var testLocation = document.querySelector('[data-crm-mobile-test-location]')
          var lastLocation = null
          var locationLoading = false

          if (!modal || !toggle || !locationEnabled || !locationAccuracy) return

          function readSettings() {
            try {
              var stored = JSON.parse(localStorage.getItem(storageKey) || '{}')

              return {
                locationEnabled: Boolean(stored.locationEnabled),
                highAccuracyLocation: Boolean(stored.highAccuracyLocation),
              }
            } catch (e) {
              return {
                locationEnabled: false,
                highAccuracyLocation: false,
              }
            }
          }

          function writeSettings() {
            localStorage.setItem(storageKey, JSON.stringify(settings))
          }

          function showError(message) {
            if (!errorBox) return

            errorBox.textContent = message || ''
            errorBox.classList.toggle('is-visible', Boolean(message))
          }

          function networkLabel() {
            return navigator.onLine ? 'En ligne' : 'Hors ligne'
          }

          function locationLabel() {
            if (locationLoading) return 'Recherche...'

            if (lastLocation) {
              return [
                lastLocation.latitude.toFixed(5),
                lastLocation.longitude.toFixed(5),
                Math.round(lastLocation.accuracy) + ' m'
              ].join(' / ')
            }

            return settings.locationEnabled ? 'Activee' : 'Desactivee'
          }

          function publishLocation() {
            window.dispatchEvent(new CustomEvent('martin-sols:mobile-location', {
              detail: {
                enabled: settings.locationEnabled,
                last: lastLocation,
              }
            }))
          }

          function renderSettings() {
            locationEnabled.checked = settings.locationEnabled
            locationAccuracy.checked = settings.highAccuracyLocation

            if (networkStatus) networkStatus.textContent = networkLabel()
            if (locationStatus) locationStatus.textContent = locationLabel()
            if (platformStatus) platformStatus.textContent = navigator.userAgent
            if (testLocation) testLocation.disabled = locationLoading || !settings.locationEnabled
          }

          function requestLocation() {
            if (!navigator.geolocation) {
              showError('Localisation indisponible sur ce navigateur.')
              return
            }

            locationLoading = true
            showError('')
            renderSettings()

            navigator.geolocation.getCurrentPosition(function (position) {
              lastLocation = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                updatedAt: new Date(position.timestamp).toISOString(),
              }
              locationLoading = false
              publishLocation()
              renderSettings()
            }, function (error) {
              locationLoading = false
              showError(error && error.message ? error.message : 'Localisation indisponible.')
              renderSettings()
            }, {
              enableHighAccuracy: settings.highAccuracyLocation,
              timeout: 12000,
              maximumAge: 60000,
            })
          }

          var settings = readSettings()

          toggle.addEventListener('click', function () {
            modal.hidden = false
            renderSettings()
          })

          closeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
              modal.hidden = true
            })
          })

          locationEnabled.addEventListener('change', function () {
            settings.locationEnabled = locationEnabled.checked
            writeSettings()
            showError('')
            renderSettings()

            if (settings.locationEnabled) {
              requestLocation()
            } else {
              lastLocation = null
              publishLocation()
            }
          })

          locationAccuracy.addEventListener('change', function () {
            settings.highAccuracyLocation = locationAccuracy.checked
            writeSettings()
            showError('')
            renderSettings()
          })

          if (testLocation) {
            testLocation.addEventListener('click', requestLocation)
          }

          window.addEventListener('online', renderSettings)
          window.addEventListener('offline', renderSettings)

          renderSettings()
        })()
      </script>
    @endif
    @if($isCrmMobileApp)
      <script data-crm-mobile-fallback-nav>
        (function () {
          var fallbackId = 'crm-mobile-fallback-nav'
          var drawerId = 'crm-mobile-fallback-drawer'
          var logoUrl = @json(asset('assets/logo/martin-sols-logo.png'))

          function headerIsVisible() {
            var header = document.querySelector('.layout-header')

            if (!header) return false

            var style = window.getComputedStyle(header)
            var rect = header.getBoundingClientRect()

            return style.display !== 'none'
              && style.visibility !== 'hidden'
              && Number(style.opacity || 1) !== 0
              && rect.height > 24
              && rect.bottom > 0
          }

          function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
              return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
              })[char]
            })
          }

          function initials(label) {
            return String(label || 'MS')
              .trim()
              .split(/\s+/)
              .filter(Boolean)
              .slice(0, 2)
              .map(function (part) { return part.charAt(0).toUpperCase() })
              .join('') || 'MS'
          }

          function normalizedPath(value) {
            try {
              var url = new URL(value || '/', window.location.origin)

              if (url.origin !== window.location.origin) return url.href

              return url.pathname.replace(/\/+$/, '') || '/'
            } catch (e) {
              return '/'
            }
          }

          function isActive(path) {
            var current = window.location.pathname.replace(/\/+$/, '') || '/'
            var target = normalizedPath(path)

            if (/^https?:\/\//.test(target)) return false
            if (target === '/dashboard/crm' && current === '/') return true

            return current === target || current.indexOf(target + '/') === 0
          }

          function menuData() {
            var fallback = window.CRM_NAV_FALLBACK || {}
            var modules = Array.isArray(fallback.modules) ? fallback.modules : []
            var routeByKey = {}

            modules.forEach(function (module) {
              routeByKey['module:' + module.slug] = module.routePath || ('/' + module.slug)
            })

            return (Array.isArray(fallback.menuGroups) ? fallback.menuGroups : [])
              .filter(function (group) { return group && group.active !== false })
              .sort(function (a, b) { return Number(a.sortOrder || 0) - Number(b.sortOrder || 0) })
              .map(function (group) {
                var items = (Array.isArray(fallback.menuItems) ? fallback.menuItems : [])
                  .filter(function (item) { return item && item.active !== false && item.groupKey === group.menuKey })
                  .sort(function (a, b) { return Number(a.sortOrder || 0) - Number(b.sortOrder || 0) })
                  .map(function (item) {
                    return {
                      label: item.label || 'CRM',
                      path: routeByKey[item.itemKey] || item.routePath || '#'
                    }
                  })

                return {
                  title: group.title || group.menuKey || 'CRM',
                  items: items
                }
              })
              .filter(function (group) { return group.items.length > 0 })
          }

          function menuIcon() {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>'
          }

          function closeIcon() {
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg>'
          }

          function drawerHtml() {
            var groups = menuData().map(function (group) {
              var links = group.items.map(function (item) {
                var active = isActive(item.path)

                return ''
                  + '<a class="crm-mobile-fallback-link' + (active ? ' is-active' : '') + '" href="' + esc(item.path) + '">'
                  + '<span class="crm-mobile-fallback-initials">' + esc(initials(item.label)) + '</span>'
                  + '<span>' + esc(item.label) + '</span>'
                  + '</a>'
              }).join('')

              return ''
                + '<section class="crm-mobile-fallback-group">'
                + '<p class="crm-mobile-fallback-group-title">' + esc(group.title) + '</p>'
                + links
                + '</section>'
            }).join('')

            return ''
              + '<div id="' + drawerId + '" class="crm-mobile-fallback-drawer" hidden>'
              + '<button class="crm-mobile-fallback-backdrop" type="button" data-crm-mobile-fallback-close aria-label="Fermer"></button>'
              + '<aside class="crm-mobile-fallback-panel" aria-label="Menu CRM">'
              + '<div class="crm-mobile-fallback-panel-header">'
              + '<img src="' + esc(logoUrl) + '" alt="Martin Sols">'
              + '<button class="crm-mobile-fallback-close" type="button" data-crm-mobile-fallback-close aria-label="Fermer">' + closeIcon() + '</button>'
              + '</div>'
              + '<nav class="crm-mobile-fallback-nav">' + groups + '</nav>'
              + '</aside>'
              + '</div>'
          }

          function openDrawer() {
            var drawer = document.getElementById(drawerId)

            if (drawer) drawer.hidden = false
          }

          function closeDrawer() {
            var drawer = document.getElementById(drawerId)

            if (drawer) drawer.hidden = true
          }

          function removeFallback() {
            var header = document.getElementById(fallbackId)
            var drawer = document.getElementById(drawerId)

            if (header) header.remove()
            if (drawer) drawer.remove()
            document.body.classList.remove('crm-mobile-fallback-nav-active')
          }

          function ensureFallback() {
            if (headerIsVisible()) {
              removeFallback()
              return
            }

            if (document.getElementById(fallbackId)) return

            document.body.insertAdjacentHTML('afterbegin', ''
              + '<header id="' + fallbackId + '" class="crm-mobile-fallback-header">'
              + '<button class="crm-mobile-fallback-menu-button" type="button" data-crm-mobile-fallback-open aria-label="Menu CRM">' + menuIcon() + '</button>'
              + '<a class="crm-mobile-fallback-brand" href="/dashboard/crm?mobile_app=1">'
              + '<img src="' + esc(logoUrl) + '" alt="Martin Sols">'
              + '</a>'
              + '<span style="width:42px;height:42px" aria-hidden="true"></span>'
              + '</header>'
              + drawerHtml()
            )

            document.body.classList.add('crm-mobile-fallback-nav-active')
            document.querySelector('[data-crm-mobile-fallback-open]')?.addEventListener('click', openDrawer)
            document.querySelectorAll('[data-crm-mobile-fallback-close]').forEach(function (button) {
              button.addEventListener('click', closeDrawer)
            })
          }

          function scheduleChecks() {
            window.setTimeout(ensureFallback, 900)
            window.setTimeout(ensureFallback, 1800)
            window.setTimeout(ensureFallback, 3200)
          }

          document.addEventListener('DOMContentLoaded', scheduleChecks, { once: true })
          window.addEventListener('load', scheduleChecks)
          window.addEventListener('popstate', scheduleChecks)

          ;['pushState', 'replaceState'].forEach(function (method) {
            var original = history[method]

            history[method] = function patchedMobileFallbackHistoryState() {
              var result = original.apply(this, arguments)
              closeDrawer()
              scheduleChecks()

              return result
            }
          })

          if (document.readyState !== 'loading') scheduleChecks()
        })()
      </script>
    @endif
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader.js') }}"></script>
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader-app.js') }}"></script>
    @if(request()->boolean('mobile_embed') && ! $isCrmMobileApp)
      <script>
        (function () {
          function send(type, payload) {
            try {
              window.parent.postMessage({
                source: 'martin-sols-crm',
                type: type,
                payload: payload || {}
              }, '*')
            } catch (e) {
              // Mobile bridge is optional.
            }
          }

          function currentPath() {
            return window.location.pathname + window.location.search + window.location.hash
          }

          function notifyNavigation() {
            send('crm:navigation', {
              path: currentPath(),
              title: document.title || ''
            })
          }

          ;['pushState', 'replaceState'].forEach(function (method) {
            var original = history[method]

            history[method] = function patchedMobileHistoryState() {
              var result = original.apply(this, arguments)
              window.setTimeout(notifyNavigation, 0)

              return result
            }
          })

          document.addEventListener('click', function (event) {
            var target = event.target
            var link = target && typeof target.closest === 'function' ? target.closest('a[href]') : null

            if (!link) return

            var url

            try {
              url = new URL(link.href, window.location.href)
            } catch (e) {
              return
            }

            if (link.target === '_blank' || url.origin !== window.location.origin) {
              event.preventDefault()

              if (url.origin === window.location.origin) {
                window.location.href = url.href
                return
              }

              send('crm:external-url', {
                url: url.href,
                title: (link.textContent || url.hostname).trim()
              })
            }
          }, true)

          window.addEventListener('message', function (event) {
            var message = event.data

            if (!message || message.source !== 'martin-sols-mobile') return

            if (message.type === 'mobile:location-update') {
              window.dispatchEvent(new CustomEvent('martin-sols:mobile-location', {
                detail: message.payload || {}
              }))
            }
          })

          window.MartinSolsMobileApp = {
            requestLocation: function () {
              send('crm:location-request', { path: currentPath() })
            }
          }

          window.addEventListener('popstate', notifyNavigation)
          window.addEventListener('load', notifyNavigation)

          if (document.readyState !== 'loading') {
            notifyNavigation()
          } else {
            document.addEventListener('DOMContentLoaded', notifyNavigation, { once: true })
          }
        })()
      </script>
    @endif
    @unless(request()->boolean('mobile_embed') && ! $isCrmMobileApp)
      @include('partials.pwa-scripts')
    @endunless
  </body>
</html>
