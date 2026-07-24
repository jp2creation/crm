<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex,nofollow" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="{{ \App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader.css') }}">
    <title>Connexion - Martin Sols CRM</title>
    <style>
      :root {
        color-scheme: light;
        --primary: #a50034;
        --primary-dark: #85002b;
        --ink: #102033;
        --muted: #697386;
        --line: #dce2ea;
        --surface: #ffffff;
        --page: #f3f6fa;
      }

      * {
        box-sizing: border-box;
      }

      html {
        min-height: 100%;
        background: var(--page);
      }

      body {
        min-height: 100vh;
        min-height: 100svh;
        margin: 0;
        display: grid;
        place-items: center;
        padding: 24px;
        background:
          linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(243, 246, 250, 0.96)),
          var(--page);
        color: var(--ink);
        font-family: "DM Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      main {
        width: min(100%, 460px);
      }

      .login-card {
        display: grid;
        gap: 24px;
        padding: 30px;
        border: 1px solid rgba(220, 226, 234, 0.96);
        border-radius: 14px;
        background: var(--surface);
        box-shadow: 0 22px 58px rgba(16, 32, 51, 0.1);
      }

      .brand {
        display: grid;
        justify-items: center;
        gap: 14px;
        text-align: center;
      }

      .brand img {
        display: block;
        width: min(250px, 72vw);
        height: auto;
        max-height: 96px;
        object-fit: contain;
      }

      .app-install {
        display: grid;
        gap: 14px;
        padding: 16px;
        border: 1px solid rgba(165, 0, 52, 0.14);
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 244, 247, 0.96));
      }

      .app-install[hidden] {
        display: none;
      }

      .app-install__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
      }

      .app-install__eyebrow {
        margin: 0 0 4px;
        color: var(--primary);
        font-size: 0.74rem;
        font-weight: 900;
        letter-spacing: 0.04em;
        text-transform: uppercase;
      }

      .app-install__title {
        margin: 0;
        color: var(--ink);
        font-size: 1.06rem;
        font-weight: 900;
        line-height: 1.2;
      }

      .app-install__text,
      .app-install__help {
        margin: 0;
        color: var(--muted);
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.45;
      }

      .app-install__badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        min-height: 58px;
        padding: 10px 18px;
        border: 1px solid #121826;
        border-radius: 12px;
        background: #121826;
        color: #fff;
        text-decoration: none;
        box-shadow: 0 14px 30px rgba(16, 32, 51, 0.16);
      }

      .app-install__badge.is-ios,
      .app-install__badge.is-macos {
        background: #000;
        border-color: #000;
      }

      .app-install__badge.is-web {
        background: #fff;
        border-color: var(--line);
        color: var(--ink);
      }

      .app-install__badge span:first-child {
        display: grid;
        place-items: center;
        width: 28px;
        height: 28px;
        font-size: 1.45rem;
      }

      .app-install__badge small,
      .app-install__badge strong {
        display: block;
        line-height: 1.1;
      }

      .app-install__badge small {
        font-size: 0.74rem;
        font-weight: 700;
        opacity: 0.82;
      }

      .app-install__badge strong {
        margin-top: 3px;
        font-size: 1.02rem;
        font-weight: 900;
      }

      form {
        display: grid;
        gap: 17px;
      }

      .field {
        display: grid;
        gap: 8px;
      }

      label {
        color: var(--ink);
        font-size: 0.94rem;
        font-weight: 800;
      }

      input[type="email"],
      input[type="password"] {
        width: 100%;
        min-height: 52px;
        padding: 0 14px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
        color: var(--ink);
        font: inherit;
        font-size: 1rem;
        font-weight: 650;
      }

      input:focus {
        outline: 4px solid rgba(165, 0, 52, 0.14);
        border-color: var(--primary);
      }

      .remember {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: fit-content;
        color: var(--muted);
        font-size: 0.94rem;
        font-weight: 700;
      }

      .remember input {
        width: 20px;
        height: 20px;
        margin: 0;
        accent-color: var(--primary);
      }

      .error {
        padding: 11px 13px;
        border: 1px solid rgba(185, 28, 28, 0.24);
        border-radius: 10px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 0.92rem;
        font-weight: 700;
      }

      button {
        min-height: 54px;
        border: 0;
        border-radius: 10px;
        background: var(--primary);
        color: #fff;
        font: inherit;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 14px 30px rgba(165, 0, 52, 0.2);
      }

      button:focus {
        outline: 4px solid rgba(165, 0, 52, 0.2);
        outline-offset: 2px;
      }

      button:hover {
        background: var(--primary-dark);
      }

      #crm-pwa-install-button {
        display: none !important;
      }

      @media (max-width: 520px) {
        body {
          place-items: center;
          padding: 16px;
        }

        .login-card {
          gap: 16px;
          padding: 20px;
          border-radius: 12px;
        }

        .brand {
          gap: 10px;
        }

        .brand img {
          width: min(190px, 60vw);
          max-height: 74px;
        }

        form {
          gap: 14px;
        }

        input[type="email"],
        input[type="password"] {
          min-height: 48px;
        }

        .app-install__head {
          display: grid;
        }

        .app-install__badge {
          width: 100%;
        }

        button {
          min-height: 50px;
        }
      }

      @media (max-width: 380px) {
        body {
          padding: 14px;
        }

        .login-card {
          padding: 22px;
        }
      }
    </style>
  </head>
  <body data-login-mobile-app="{{ $loginIsMobileApp ? '1' : '0' }}">
    @include('partials.brand-morph-loader')
    <script>
      (function () {
        var loader = document.getElementById('brand-morph-loader')
        if (!loader) return

        loader.classList.add('is-visible')
        loader.setAttribute('aria-hidden', 'false')
      })()
    </script>
    <main>
      <section class="login-card" aria-label="Connexion Martin Sols">
        <div class="brand">
          <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
        </div>

        <section
          class="app-install"
          data-login-app-install
          data-android-url="{{ $loginInstallLinks['androidApkUrl'] ?? '' }}"
          data-ios-url="{{ $loginInstallLinks['iosInstallUrl'] ?? '' }}"
          data-macos-url="{{ $loginInstallLinks['macosPkgUrl'] ?? '' }}"
          aria-label="Installer l'application Martin Sols"
          hidden
        >
          <div class="app-install__head">
            <div>
              <p class="app-install__eyebrow">Application</p>
              <h2 class="app-install__title" data-login-app-title>Installer Martin Sols</h2>
            </div>
            <a class="app-install__badge" href="#" data-login-app-button rel="noopener">
              <span data-login-app-icon aria-hidden="true"></span>
              <span>
                <small data-login-app-small>Télécharger</small>
                <strong data-login-app-label>L'application</strong>
              </span>
            </a>
          </div>
          <p class="app-install__text" data-login-app-text>
            Accès plus rapide, plein écran et meilleure stabilité sur chantier.
          </p>
          <p class="app-install__help" data-login-app-help></p>
        </section>

        <form method="post" action="{{ route('login') }}" autocomplete="on" data-login-form>
          @csrf

          @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
          @endif

          <div class="field">
            <label for="email">Adresse e-mail</label>
            <input
              id="email"
              name="email"
              type="email"
              value="{{ old('email') }}"
              autocomplete="username"
              inputmode="email"
              autocapitalize="none"
              spellcheck="false"
              data-login-email
              required
              autofocus
            />
          </div>

          <div class="field">
            <label for="password">Mot de passe</label>
            <input
              id="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
            />
          </div>

          <input type="hidden" name="remember" value="0" />
          <label class="remember">
            <input name="remember" type="checkbox" value="1" data-login-remember @checked(old('remember', true)) />
            <span>Rester connecté</span>
          </label>

          <button type="submit">Se connecter</button>
        </form>
      </section>
    </main>
    @include('partials.pwa-scripts')
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader.js') }}"></script>
    <script src="{{ \App\Support\CrmAsset::url('modules/crm-core/brand-morph-loader-app.js') }}"></script>
    <script>
      (() => {
        const storageKey = 'martin-sols:login:remembered-email';
        const form = document.querySelector('[data-login-form]');
        const email = document.querySelector('[data-login-email]');
        const remember = document.querySelector('[data-login-remember]');

        if (!form || !email || !remember) {
          return;
        }

        try {
          const rememberedEmail = window.localStorage.getItem(storageKey);

          if (rememberedEmail && !email.value) {
            email.value = rememberedEmail;
            remember.checked = true;
          }
        } catch (error) {
          // Private browsing modes can block localStorage; the Laravel remember cookie still works.
        }

        form.addEventListener('submit', () => {
          try {
            const normalizedEmail = email.value.trim();

            if (remember.checked && normalizedEmail) {
              window.localStorage.setItem(storageKey, normalizedEmail);
            } else {
              window.localStorage.removeItem(storageKey);
            }
          } catch (error) {
            // Do not block login if localStorage is unavailable.
          }
        });
      })();
    </script>
    <script>
      (() => {
        const card = document.querySelector('[data-login-app-install]');

        if (!card) {
          return;
        }

        const button = card.querySelector('[data-login-app-button]');
        const title = card.querySelector('[data-login-app-title]');
        const text = card.querySelector('[data-login-app-text]');
        const help = card.querySelector('[data-login-app-help]');
        const small = card.querySelector('[data-login-app-small]');
        const label = card.querySelector('[data-login-app-label]');
        const icon = card.querySelector('[data-login-app-icon]');
        const params = new URLSearchParams(window.location.search);

        const isStandalone = window.matchMedia?.('(display-mode: standalone)').matches === true
          || window.navigator.standalone === true;
        const isNativeApp = document.body.dataset.loginMobileApp === '1'
          || params.has('mobile_app')
          || params.has('mobile_embed')
          || Boolean(window.MartinSolsNativeApp)
          || window.Capacitor?.isNativePlatform?.() === true;

        if (isStandalone || isNativeApp || !button || !title || !text || !help || !small || !label || !icon) {
          card.remove();

          return;
        }

        const userAgent = window.navigator.userAgent || '';
        const platform = window.navigator.platform || '';
        const isAndroid = /Android/i.test(userAgent);
        const isIos = /iPhone|iPad|iPod/i.test(userAgent)
          || (platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
        const isMacos = /Macintosh|Mac OS X/i.test(userAgent) && !isIos;
        const androidUrl = card.dataset.androidUrl || '';
        const iosUrl = card.dataset.iosUrl || '';
        const macosUrl = card.dataset.macosUrl || '';

        const configure = (kind, options) => {
          button.className = `app-install__badge is-${kind}`;
          button.href = options.href || '#';
          button.toggleAttribute('download', options.download === true);
          title.textContent = options.title;
          text.textContent = options.text;
          help.textContent = options.help;
          small.textContent = options.small;
          label.textContent = options.label;
          icon.textContent = options.icon;
          button.onclick = options.onClick || null;
          card.hidden = false;
        };

        if (isAndroid && androidUrl) {
          configure('android', {
            href: androidUrl,
            download: true,
            icon: '▶',
            small: 'Télécharger',
            label: 'APK Android',
            title: 'Installer l’app Android',
            text: 'Télécharge le fichier APK Martin Sols, puis autorise l’installation depuis Chrome si Android le demande.',
            help: 'Application distribuée hors Play Store.',
          });

          return;
        }

        if (isIos) {
          configure('ios', {
            href: iosUrl || '#',
            icon: '',
            small: iosUrl ? 'Installer via' : 'Ajouter à',
            label: iosUrl ? 'iPhone' : 'l’écran d’accueil',
            title: 'Installer sur iPhone',
            text: 'Utilise la version web installable pour ouvrir le CRM comme une app.',
            help: 'Depuis Safari : bouton Partager, puis Ajouter à l’écran d’accueil.',
            onClick: iosUrl ? null : (event) => {
              event.preventDefault();
              help.textContent = 'Safari > Partager > Ajouter à l’écran d’accueil. Le CRM s’ouvrira ensuite en plein écran.';
            },
          });

          return;
        }

        if (isMacos && macosUrl) {
          configure('macos', {
            href: macosUrl,
            download: true,
            icon: '',
            small: 'Télécharger',
            label: 'macOS',
            title: 'Installer l’app Mac',
            text: 'Télécharge le paquet Martin Sols pour utiliser le CRM comme application Mac.',
            help: 'Application distribuée hors App Store.',
          });

          return;
        }

        configure('web', {
          href: '#',
          icon: '⌄',
          small: 'Installer',
          label: 'Application web',
          title: 'Installer Martin Sols',
          text: 'Chrome et Edge peuvent installer le CRM en application, sans passer par un store.',
          help: 'Utilise le bouton pour lancer l’installation si ton navigateur la propose.',
          onClick: (event) => {
            event.preventDefault();

            if (window.MartinSolsPwa?.install) {
              window.MartinSolsPwa.install();
              return;
            }

            help.textContent = 'Dans Chrome ou Edge : menu du navigateur, puis Installer l’application.';
          },
        });
      })();
    </script>
  </body>
</html>
