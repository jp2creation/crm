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
        gap: 8px;
        padding: 9px;
        border: 1px solid rgba(220, 226, 234, 0.95);
        border-radius: 12px;
        background: #f8fafc;
      }

      .app-install[hidden] {
        display: none;
      }

      .app-install__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
      }

      .app-install__copy {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
      }

      .app-install__mark {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        flex: 0 0 auto;
        border-radius: 10px;
        background: rgba(165, 0, 52, 0.08);
        color: var(--primary);
      }

      .app-install__mark svg,
      .app-install__badge svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-width: 2.4;
      }

      .app-install__mark > span,
      .app-install__badge span:first-child > span {
        font-size: 1.12rem;
        font-weight: 900;
        line-height: 1;
      }

      .app-install__title {
        display: block;
        color: var(--ink);
        font-size: 0.89rem;
        font-weight: 900;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .app-install__text {
        display: block;
        color: var(--muted);
        font-size: 0.76rem;
        font-weight: 700;
        line-height: 1.25;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .app-install__help {
        margin: 0;
        color: var(--muted);
        font-size: 0.76rem;
        font-weight: 700;
        line-height: 1.35;
      }

      .app-install__help:empty {
        display: none;
      }

      .app-install__badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 7px 11px;
        border: 1px solid var(--install-border, rgba(16, 32, 51, 0.14));
        border-radius: 999px;
        background: var(--install-bg, #fff);
        color: var(--install-color, var(--ink));
        text-decoration: none;
        box-shadow: 0 10px 24px var(--install-shadow, rgba(16, 32, 51, 0.1));
      }

      .app-install.is-android {
        border-color: rgba(61, 220, 132, 0.42);
        background: rgba(61, 220, 132, 0.08);
        --install-bg: #3ddc84;
        --install-border: #3ddc84;
        --install-color: #062a17;
        --install-shadow: rgba(61, 220, 132, 0.22);
      }

      .app-install.is-android .app-install__mark {
        background: rgba(61, 220, 132, 0.22);
        color: #0a7d43;
      }

      .app-install.is-ios,
      .app-install.is-macos {
        border-color: rgba(0, 0, 0, 0.18);
        --install-bg: #000;
        --install-border: #000;
        --install-color: #fff;
        --install-shadow: rgba(0, 0, 0, 0.14);
      }

      .app-install.is-ios .app-install__mark,
      .app-install.is-macos .app-install__mark {
        background: #000;
        color: #fff;
      }

      .app-install.is-web {
        border-color: rgba(66, 133, 244, 0.34);
        background: rgba(66, 133, 244, 0.06);
        --install-bg: #4285f4;
        --install-border: #4285f4;
        --install-color: #fff;
        --install-shadow: rgba(66, 133, 244, 0.18);
      }

      .app-install.is-web .app-install__mark {
        background: rgba(66, 133, 244, 0.16);
        color: #1a73e8;
      }

      .app-install__badge:focus {
        outline: 4px solid rgba(165, 0, 52, 0.16);
        outline-offset: 2px;
      }

      .app-install__badge span:first-child {
        display: grid;
        place-items: center;
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
      }

      .app-install__badge > span:last-child {
        display: grid;
        gap: 1px;
      }

      .app-install__badge small {
        color: currentColor;
        font-size: 0.62rem;
        font-weight: 750;
        line-height: 1;
        opacity: 0.76;
      }

      .app-install__badge strong {
        color: currentColor;
        font-size: 0.82rem;
        font-weight: 950;
        line-height: 1;
        white-space: nowrap;
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

        .app-install {
          padding: 8px;
        }

        .app-install__badge {
          min-width: 116px;
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

        .app-install__head {
          display: grid;
        }

        .app-install__badge {
          width: 100%;
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
            <div class="app-install__copy">
              <span class="app-install__mark" data-login-app-mark aria-hidden="true"></span>
              <span>
                <strong class="app-install__title" data-login-app-title>Installer Martin Sols</strong>
                <small class="app-install__text" data-login-app-text>Accès direct au CRM</small>
              </span>
            </div>
            <a class="app-install__badge" href="#" data-login-app-button rel="noopener">
              <span data-login-app-icon aria-hidden="true"></span>
              <span>
                <small data-login-app-small>Installer</small>
                <strong data-login-app-label>App</strong>
              </span>
            </a>
          </div>
          <p class="app-install__help" data-login-app-help></p>
        </section>
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
        const mark = card.querySelector('[data-login-app-mark]');
        const params = new URLSearchParams(window.location.search);

        const isStandalone = window.matchMedia?.('(display-mode: standalone)').matches === true
          || window.navigator.standalone === true;
        const isNativeApp = document.body.dataset.loginMobileApp === '1'
          || params.has('mobile_app')
          || params.has('mobile_embed')
          || Boolean(window.MartinSolsNativeApp)
          || window.Capacitor?.isNativePlatform?.() === true;

        if (isStandalone || isNativeApp || !button || !title || !text || !help || !small || !label || !icon || !mark) {
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
        const icons = {
          android: '<svg viewBox="0 0 24 24"><path d="M7.5 9.5h9a2 2 0 0 1 2 2v5.75a2 2 0 0 1-2 2h-9a2 2 0 0 1-2-2V11.5a2 2 0 0 1 2-2Z"></path><path d="M8 6.5 6.5 4.75"></path><path d="m16 6.5 1.5-1.75"></path><path d="M8.5 9.5a3.5 3.5 0 0 1 7 0"></path><path d="M9 14h.01"></path><path d="M15 14h.01"></path></svg>',
          apple: '<span aria-hidden="true"></span>',
          web: '<svg viewBox="0 0 24 24"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"></path><path d="M3.6 9h16.8"></path><path d="M3.6 15h16.8"></path><path d="M12 3c2.2 2.4 3.3 5.4 3.3 9S14.2 18.6 12 21"></path><path d="M12 3C9.8 5.4 8.7 8.4 8.7 12s1.1 6.6 3.3 9"></path></svg>',
          download: '<svg viewBox="0 0 24 24"><path d="M12 3v11"></path><path d="m7.5 9.5 4.5 4.5 4.5-4.5"></path><path d="M5 18.5h14"></path></svg>',
        };

        const configure = (kind, options) => {
          card.className = `app-install is-${kind}`;
          button.className = `app-install__badge is-${kind}`;
          button.href = options.href || '#';
          button.toggleAttribute('download', options.download === true);
          title.textContent = options.title;
          text.textContent = options.text;
          help.textContent = options.help;
          small.textContent = options.small;
          label.textContent = options.label;
          icon.innerHTML = options.icon;
          mark.innerHTML = options.mark || options.icon;
          button.onclick = options.onClick || null;
          card.hidden = false;
        };

        if (isAndroid && androidUrl) {
          configure('android', {
            href: androidUrl,
            download: true,
            icon: icons.download,
            mark: icons.android,
            small: 'Télécharger',
            label: 'APK Android',
            title: 'Application Android',
            text: 'APK hors Play Store',
            help: '',
          });

          return;
        }

        if (isIos) {
          configure('ios', {
            href: iosUrl || '#',
            icon: icons.apple,
            mark: icons.apple,
            small: iosUrl ? 'Installer via' : 'Ajouter',
            label: iosUrl ? 'iPhone' : 'iPhone',
            title: 'Sur iPhone',
            text: 'Ajout écran d’accueil',
            help: '',
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
            icon: icons.download,
            mark: icons.apple,
            small: 'Télécharger',
            label: 'macOS',
            title: 'Application Mac',
            text: 'Paquet hors App Store',
            help: '',
          });

          return;
        }

        configure('web', {
          href: '#',
          icon: icons.download,
          mark: icons.web,
          small: 'Installer',
          label: 'PWA',
          title: 'Application web',
          text: 'Chrome / Edge',
          help: '',
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
