<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
      rel="stylesheet"
    />
    <script src="{{ \App\Support\CrmAsset::url('assets/crm-active-site.js') }}"></script>
    <script src="{{ \App\Support\CrmAsset::url('assets/crm-text-fixes.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('assets/index-CqSzWeas.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('assets/crm-conges.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('assets/crm-controle-caisse.js') }}"></script>
    <script type="module" crossorigin src="{{ \App\Support\CrmAsset::url('assets/crm-remise-cheques.js') }}"></script>
    <link rel="stylesheet" crossorigin href="{{ \App\Support\CrmAsset::url('assets/index-CVBlw941.css') }}">
    <style>
      .layout-header .header-search-wrap,
      .layout-header .header-mobile-panel,
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
    </style>
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
