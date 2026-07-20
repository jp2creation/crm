<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
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
    <script data-crm-frontend-assets>
      window.MartinSolsCrmAssets = {
        legacyAdminexScript: @json(\App\Support\CrmAsset::url(config('crm_frontend.legacy.adminex_script'))),
        legacyAdminexStylesheet: @json(\App\Support\CrmAsset::url(config('crm_frontend.legacy.adminex_stylesheet'))),
        brandMorphLoaderStylesheet: @json(\App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader.css')),
        logoUrl: @json(asset('assets/logo/martin-sols-logo.png'))
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
    @vite(config('crm_frontend.vite_entries'))
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
      <button class="crm-mobile-app-settings-trigger" type="button" data-crm-mobile-settings-toggle aria-label="Paramètres de l'app">
        <span></span><span></span><span></span>
      </button>
      <div class="crm-mobile-app-settings" data-crm-mobile-settings hidden>
        <button class="crm-mobile-app-settings-backdrop" type="button" data-crm-mobile-settings-close aria-label="Fermer"></button>
        <section class="crm-mobile-app-settings-panel" role="dialog" aria-modal="true" aria-label="Paramètres de l'app">
          <div class="crm-mobile-app-settings-header">
            <div>
              <p>Application</p>
              <h2>Paramètres</h2>
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
              <span>Haute précision</span>
              <input data-crm-mobile-location-accuracy type="checkbox">
              <i aria-hidden="true"></i>
            </label>
          </div>

          <div class="crm-mobile-app-settings-status">
            <div>
              <span>Réseau</span>
              <strong data-crm-mobile-network-status>En ligne</strong>
            </div>
            <div>
              <span>Localisation</span>
              <strong data-crm-mobile-location-status>Désactivée</strong>
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
    @endif
    @unless(request()->boolean('mobile_embed') && ! $isCrmMobileApp)
      @include('partials.pwa-scripts')
    @endunless
  </body>
</html>
