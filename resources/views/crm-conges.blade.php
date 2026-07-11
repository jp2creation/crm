<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Conges - CRM Martin Sols</title>

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
        } catch (e) {
          // Theme preference is optional.
        }
      })()
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
      rel="stylesheet"
    />
    <script type="module" crossorigin src="{{ asset('assets/crm-conges.js') }}?v=2026071114"></script>
    <link rel="stylesheet" crossorigin href="{{ asset('assets/index-CVBlw941.css') }}">
    <style>
      :root {
        --theme-primary: 149 0 46;
        --theme-accent: 149 0 46;
      }

      body {
        margin: 0;
        min-height: 100vh;
        background: rgb(248 250 252);
        color: rgb(15 23 42);
        font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      }

      .crm-app-shell {
        display: grid;
        grid-template-columns: 17rem minmax(0, 1fr);
        min-height: 100vh;
      }

      .crm-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        overflow: auto;
        border-right: 1px solid rgb(226 232 240);
        background: #fff;
      }

      .crm-sidebar-brand {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-height: 4.25rem;
        padding: 0 1.1rem;
        border-bottom: 1px solid rgb(241 245 249);
        color: rgb(15 23 42);
        font-weight: 900;
        text-decoration: none;
      }

      .crm-sidebar-logo {
        display: inline-flex;
        width: 2.25rem;
        height: 2.25rem;
        align-items: center;
        justify-content: center;
        border-radius: .55rem;
        background: rgb(var(--theme-primary));
        color: #fff;
        font-size: .8rem;
        font-weight: 900;
      }

      .crm-sidebar-section {
        padding: 1rem .85rem .35rem;
        color: rgb(100 116 139);
        font-size: .72rem;
        font-weight: 900;
        text-transform: uppercase;
      }

      .crm-sidebar-nav {
        display: grid;
        gap: .2rem;
        padding: 0 .7rem .75rem;
      }

      .crm-sidebar-link {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-height: 2.55rem;
        border-radius: .55rem;
        color: rgb(51 65 85);
        padding: .55rem .7rem;
        font-size: .88rem;
        font-weight: 800;
        text-decoration: none;
      }

      .crm-sidebar-link:hover {
        background: rgb(248 250 252);
        color: rgb(var(--theme-primary));
      }

      .crm-sidebar-link.is-active {
        background: rgb(var(--theme-primary) / .1);
        color: rgb(var(--theme-primary));
      }

      .crm-sidebar-icon {
        display: flex;
        width: 1.75rem;
        height: 1.75rem;
        align-items: center;
        justify-content: center;
        border-radius: .45rem;
        background: rgb(241 245 249);
        font-size: .7rem;
        font-weight: 900;
      }

      .crm-sidebar-link.is-active .crm-sidebar-icon {
        background: rgb(var(--theme-primary));
        color: #fff;
      }

      .crm-main-shell {
        min-width: 0;
        display: flex;
        flex-direction: column;
      }

      .crm-topbar {
        position: sticky;
        top: 0;
        z-index: 30;
        display: flex;
        min-height: 4.25rem;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 1px solid rgb(226 232 240);
        background: rgb(255 255 255 / .94);
        padding: 0 1.25rem;
        backdrop-filter: blur(12px);
      }

      .crm-topbar-title {
        min-width: 0;
      }

      .crm-topbar-title strong {
        display: block;
        color: rgb(15 23 42);
        font-size: .98rem;
        font-weight: 900;
      }

      .crm-topbar-title span {
        display: block;
        margin-top: .15rem;
        color: rgb(100 116 139);
        font-size: .78rem;
      }

      .crm-topbar-actions {
        display: flex;
        align-items: center;
        gap: .55rem;
      }

      .crm-topbar-link,
      .crm-topbar-button {
        display: inline-flex;
        min-height: 2.35rem;
        align-items: center;
        justify-content: center;
        border: 1px solid rgb(226 232 240);
        border-radius: .45rem;
        background: #fff;
        color: rgb(51 65 85);
        padding: .55rem .75rem;
        font-size: .84rem;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
      }

      .crm-topbar-link:hover,
      .crm-topbar-button:hover {
        color: rgb(var(--theme-primary));
        border-color: rgb(var(--theme-primary) / .45);
      }

      .crm-main {
        padding: 1.25rem;
      }

      .layout-container {
        max-width: 1440px;
        margin: 0 auto;
      }

      @media (max-width: 1024px) {
        .crm-app-shell {
          grid-template-columns: 1fr;
        }

        .crm-sidebar {
          position: static;
          height: auto;
          border-right: 0;
          border-bottom: 1px solid rgb(226 232 240);
        }

        .crm-sidebar-nav {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }
      }

      @media (max-width: 640px) {
        .crm-topbar {
          align-items: flex-start;
          flex-direction: column;
          padding: .85rem 1rem;
        }

        .crm-topbar-actions {
          width: 100%;
          justify-content: space-between;
        }

        .crm-sidebar-nav {
          grid-template-columns: 1fr;
        }

        .crm-main {
          padding: 1rem;
        }
      }
    </style>
  </head>
  <body>
    @php
      $user = auth()->user();
      $menuSections = [
        'Applications' => [
          ['label' => 'Reservations vehicules', 'href' => '/reservations', 'icon' => 'RV'],
          ['label' => 'Location materiel', 'href' => '/locations-materiel', 'icon' => 'LM'],
          ['label' => 'Tapis ROMUS', 'href' => '/tapis-romus', 'icon' => 'TR'],
        ],
        'Interne' => [
          ['label' => 'Pages CRM', 'href' => '/pages-crm', 'icon' => 'PC'],
          ['label' => 'Conges', 'href' => '/conges', 'icon' => 'CG', 'active' => true],
          ['label' => 'Administration', 'href' => '/administration', 'icon' => 'AD'],
        ],
      ];
    @endphp

    <div class="crm-app-shell">
      <aside class="crm-sidebar" aria-label="Menu CRM">
        <a class="crm-sidebar-brand" href="{{ route('crm.home') }}">
          <span class="crm-sidebar-logo">MS</span>
          <span>Martin Sols CRM</span>
        </a>

        @foreach ($menuSections as $section => $items)
          <div class="crm-sidebar-section">{{ $section }}</div>
          <nav class="crm-sidebar-nav" aria-label="{{ $section }}">
            @foreach ($items as $item)
              <a class="crm-sidebar-link {{ ! empty($item['active']) ? 'is-active' : '' }}" href="{{ $item['href'] }}">
                <span class="crm-sidebar-icon">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
              </a>
            @endforeach
          </nav>
        @endforeach
      </aside>

      <div class="crm-main-shell">
        <header class="crm-topbar">
          <div class="crm-topbar-title">
            <strong>Conges Palissy</strong>
            <span>{{ $user?->name ?? $user?->email ?? 'CRM' }}</span>
          </div>
          <div class="crm-topbar-actions">
            <a class="crm-topbar-link" href="{{ route('crm.home') }}">Retour CRM</a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="crm-topbar-button" type="submit">Deconnexion</button>
            </form>
          </div>
        </header>

        <main class="crm-main">
          <div class="layout-container">
            <div id="crm-leaves-module" class="leaves-panel">Chargement des conges...</div>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
