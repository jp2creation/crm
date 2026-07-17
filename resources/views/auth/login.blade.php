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

      .brand h1 {
        margin: 0;
        color: var(--ink);
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.15;
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

        .brand h1 {
          font-size: 1.34rem;
        }

        form {
          gap: 14px;
        }

        input[type="email"],
        input[type="password"] {
          min-height: 48px;
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
  <body>
    <main>
      <section class="login-card" aria-label="Connexion Martin Sols CRM">
        <div class="brand">
          <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
          <div>
            <h1>Connexion CRM</h1>
          </div>
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
      </section>
    </main>
    @include('partials.pwa-scripts')
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
  </body>
</html>
