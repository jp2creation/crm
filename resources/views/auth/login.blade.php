<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex,nofollow" />
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
      rel="stylesheet"
    />
    <title>Connexion - Martin Sols CRM</title>
    <style>
      :root {
        color-scheme: light;
        --primary: #95002e;
        --primary-dark: #760025;
        --primary-soft: rgba(149, 0, 46, 0.08);
        --ink: #111827;
        --ink-soft: #26354a;
        --muted: #6b7280;
        --line: #e5e7eb;
        --surface: #ffffff;
        --page: #f5f7fb;
      }

      * {
        box-sizing: border-box;
      }

      body {
        min-height: 100vh;
        min-height: 100svh;
        margin: 0;
        display: grid;
        place-items: center;
        padding: clamp(18px, 4vw, 42px);
        background:
          radial-gradient(circle at 18% 18%, rgba(149, 0, 46, 0.08), transparent 28%),
          linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(245, 247, 251, 0.78)),
          var(--page);
        color: var(--ink);
        font-family: "DM Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      main {
        width: min(100%, 980px);
      }

      .login-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.02fr) minmax(360px, 0.82fr);
        gap: clamp(20px, 4vw, 46px);
        align-items: center;
      }

      .brand-panel {
        display: grid;
        gap: 24px;
        align-content: center;
        min-height: 500px;
        padding: clamp(26px, 5vw, 52px);
        border: 1px solid rgba(229, 231, 235, 0.9);
        border-radius: 8px;
        background:
          linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.78)),
          linear-gradient(135deg, rgba(149, 0, 46, 0.08), rgba(38, 53, 74, 0.04));
        box-shadow: 0 24px 70px rgba(17, 24, 39, 0.08);
      }

      .brand-logo {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        padding: 18px 20px;
        border: 1px solid rgba(229, 231, 235, 0.92);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 18px 50px rgba(17, 24, 39, 0.08);
      }

      .brand-logo img {
        display: block;
        width: min(300px, 66vw);
        height: auto;
        max-height: 108px;
        object-fit: contain;
      }

      .brand-copy {
        display: grid;
        gap: 10px;
      }

      .brand-copy strong {
        color: var(--ink);
        font-size: clamp(2rem, 4vw, 3.1rem);
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.02;
      }

      .brand-copy span {
        max-width: 30rem;
        color: var(--muted);
        font-size: clamp(1rem, 1.6vw, 1.12rem);
        font-weight: 600;
        line-height: 1.45;
      }

      .login-panel {
        display: grid;
        gap: 18px;
      }

      .mobile-logo {
        display: none;
      }

      form {
        display: grid;
        gap: 20px;
        padding: clamp(24px, 4vw, 34px);
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(17, 24, 39, 0.1);
      }

      h1 {
        margin: 0;
        color: var(--ink);
        font-size: clamp(1.8rem, 4vw, 2.15rem);
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.2;
      }

      .form-intro {
        display: grid;
        gap: 6px;
      }

      .form-intro p {
        margin: 0;
        color: var(--muted);
        font-size: 0.98rem;
        font-weight: 600;
      }

      .field {
        display: grid;
        gap: 8px;
      }

      label {
        color: var(--ink-soft);
        font-size: 0.94rem;
        font-weight: 700;
      }

      input[type="email"],
      input[type="password"] {
        width: 100%;
        min-height: 50px;
        padding: 0 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: var(--ink);
        font: inherit;
        font-weight: 600;
      }

      input:focus {
        outline: 3px solid rgba(149, 0, 46, 0.15);
        border-color: var(--primary);
      }

      .remember {
        display: flex;
        align-items: center;
        gap: 10px;
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
        padding: 11px 12px;
        border: 1px solid rgba(185, 28, 28, 0.25);
        border-radius: 6px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 0.92rem;
      }

      button {
        min-height: 52px;
        border: 0;
        border-radius: 8px;
        background: var(--primary);
        color: #fff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
      }

      button:focus {
        outline: 3px solid rgba(149, 0, 46, 0.24);
        outline-offset: 2px;
      }

      button:hover {
        background: var(--primary-dark);
      }

      @media (max-width: 820px) {
        body {
          align-items: start;
          padding: 24px 18px;
        }

        .login-shell {
          grid-template-columns: 1fr;
          gap: 18px;
        }

        .brand-panel {
          display: none;
        }

        .mobile-logo {
          display: grid;
          justify-items: center;
          gap: 12px;
          padding: 18px 0 2px;
          text-align: center;
        }

        .mobile-logo img {
          width: min(250px, 72vw);
          height: auto;
          max-height: 96px;
          object-fit: contain;
        }

        .mobile-logo strong {
          color: var(--ink);
          font-size: 1.52rem;
          font-weight: 800;
          line-height: 1.08;
        }

        .mobile-logo span {
          color: var(--muted);
          font-size: 1rem;
          font-weight: 700;
        }

        form {
          padding: 22px;
        }

        h1 {
          font-size: 1.8rem;
        }
      }

      @media (max-width: 380px) {
        body {
          padding-inline: 14px;
        }

        form {
          padding: 20px;
        }
      }
    </style>
  </head>
  <body>
    <main>
      <div class="login-shell">
        <section class="brand-panel" aria-label="Martin Sols CRM">
          <div class="brand-logo">
            <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
          </div>
          <div class="brand-copy">
            <strong>Martin Sols CRM</strong>
            <span>Acces equipe aux reservations, locations, conges et modules internes.</span>
          </div>
        </section>

        <div class="login-panel">
          <div class="mobile-logo" aria-label="Martin Sols CRM">
            <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
            <strong>Martin Sols CRM</strong>
            <span>Acces equipe</span>
          </div>

          <form method="post" action="{{ route('login') }}">
            @csrf

            <div class="form-intro">
              <h1>Connexion</h1>
              <p>Connectez-vous a votre espace CRM.</p>
            </div>

            @if ($errors->any())
              <div class="error">{{ $errors->first() }}</div>
            @endif

            <div class="field">
              <label for="email">Email</label>
              <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
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

            <label class="remember">
              <input name="remember" type="checkbox" value="1" />
              <span>Rester connecte</span>
            </label>

            <button type="submit">Se connecter</button>
          </form>
        </div>
      </div>
    </main>
  </body>
</html>
