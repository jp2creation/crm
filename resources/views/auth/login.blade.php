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
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap"
      rel="stylesheet"
    />
    <title>Connexion - Martin Sols CRM</title>
    <style>
      :root {
        color-scheme: light;
        --primary: #a50034;
        --primary-dark: #85002b;
        --primary-soft: #f7e8ee;
        --gold: #f3bd18;
        --gold-soft: #fff7df;
        --ink: #102033;
        --ink-strong: #071525;
        --muted: #6a7484;
        --line: #dfe4ea;
        --line-strong: #ccd3dd;
        --surface: #ffffff;
        --surface-soft: #f6f8fb;
        --page: #eef2f6;
        --success: #15803d;
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
        padding: 32px;
        background:
          linear-gradient(90deg, rgba(165, 0, 52, 0.08), transparent 32%),
          linear-gradient(180deg, #ffffff 0%, #eef2f6 100%);
        color: var(--ink);
        font-family: "DM Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      main {
        width: min(100%, 1140px);
      }

      .login-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(380px, 0.78fr);
        min-height: 650px;
        overflow: hidden;
        border: 1px solid rgba(204, 211, 221, 0.92);
        border-radius: 14px;
        background: var(--surface);
        box-shadow: 0 28px 80px rgba(16, 32, 51, 0.14);
      }

      .brand-panel {
        position: relative;
        display: grid;
        align-content: space-between;
        gap: 32px;
        padding: 42px;
        overflow: hidden;
        background:
          linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(255, 248, 226, 0.82)),
          linear-gradient(160deg, rgba(165, 0, 52, 0.08), rgba(243, 189, 24, 0.11));
        border-right: 1px solid var(--line);
      }

      .brand-top {
        display: grid;
        gap: 28px;
        max-width: 560px;
      }

      .logo-card {
        width: fit-content;
        max-width: 100%;
        padding: 18px 22px;
        border: 1px solid rgba(204, 211, 221, 0.94);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 38px rgba(16, 32, 51, 0.09);
      }

      .logo-card img {
        display: block;
        width: 280px;
        max-width: 66vw;
        height: auto;
        max-height: 104px;
        object-fit: contain;
      }

      .headline {
        display: grid;
        gap: 14px;
      }

      .headline .eyebrow,
      .form-heading .eyebrow {
        margin: 0;
        color: var(--primary);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0;
        text-transform: uppercase;
      }

      .headline h1 {
        max-width: 560px;
        margin: 0;
        color: var(--ink-strong);
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.03;
      }

      .headline p {
        max-width: 500px;
        margin: 0;
        color: var(--muted);
        font-size: 1.02rem;
        font-weight: 600;
        line-height: 1.55;
      }

      .preview {
        display: grid;
        gap: 14px;
        width: min(100%, 620px);
        padding: 18px;
        border: 1px solid rgba(204, 211, 221, 0.88);
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.78);
        box-shadow: 0 22px 54px rgba(16, 32, 51, 0.1);
      }

      .preview-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
      }

      .preview-title {
        display: grid;
        gap: 4px;
      }

      .preview-title strong {
        color: var(--ink-strong);
        font-size: 1rem;
        font-weight: 800;
      }

      .preview-title span {
        color: var(--muted);
        font-size: 0.82rem;
        font-weight: 700;
      }

      .site-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 12px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: #fff;
        color: var(--ink);
        font-size: 0.82rem;
        font-weight: 800;
      }

      .site-pill::before {
        content: "";
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--success);
      }

      .preview-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
      }

      .preview-stat {
        min-height: 92px;
        display: grid;
        align-content: center;
        gap: 6px;
        padding: 14px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: #fff;
      }

      .preview-stat span {
        color: var(--muted);
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
      }

      .preview-stat strong {
        color: var(--ink-strong);
        font-size: 1.45rem;
        font-weight: 800;
      }

      .timeline {
        display: grid;
        gap: 8px;
        padding: 14px;
        border: 1px solid var(--line);
        border-radius: 10px;
        background: var(--surface-soft);
      }

      .timeline-row {
        display: grid;
        grid-template-columns: 86px 1fr 54px;
        align-items: center;
        gap: 10px;
        min-height: 28px;
        color: var(--muted);
        font-size: 0.78rem;
        font-weight: 800;
      }

      .bar {
        height: 8px;
        border-radius: 999px;
        background: var(--line);
        overflow: hidden;
      }

      .bar span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: var(--primary);
      }

      .login-panel {
        display: grid;
        align-content: center;
        padding: 42px;
        background:
          linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
      }

      .mobile-logo {
        display: none;
      }

      form {
        width: 100%;
        display: grid;
        gap: 20px;
      }

      .form-heading {
        display: grid;
        gap: 8px;
      }

      .form-heading h2 {
        margin: 0;
        color: var(--ink-strong);
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.15;
      }

      .form-heading p {
        margin: 0;
        color: var(--muted);
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.45;
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
        min-height: 54px;
        padding: 0 15px;
        border: 1px solid var(--line-strong);
        border-radius: 10px;
        background: #fff;
        color: var(--ink-strong);
        font: inherit;
        font-size: 1rem;
        font-weight: 700;
        box-shadow: 0 1px 0 rgba(16, 32, 51, 0.04);
      }

      input::placeholder {
        color: #9aa4b2;
        font-weight: 600;
      }

      input:focus {
        outline: 4px solid rgba(165, 0, 52, 0.14);
        border-color: var(--primary);
      }

      .remember-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        min-height: 32px;
      }

      .remember {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--muted);
        font-size: 0.94rem;
        font-weight: 800;
      }

      .remember input {
        width: 20px;
        height: 20px;
        margin: 0;
        accent-color: var(--primary);
      }

      .secure-note {
        color: var(--muted);
        font-size: 0.86rem;
        font-weight: 700;
      }

      .error {
        padding: 12px 14px;
        border: 1px solid rgba(185, 28, 28, 0.24);
        border-radius: 10px;
        background: #fef2f2;
        color: #991b1b;
        font-size: 0.93rem;
        font-weight: 700;
      }

      button {
        min-height: 56px;
        border: 0;
        border-radius: 10px;
        background: var(--primary);
        color: #fff;
        font: inherit;
        font-size: 1rem;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 14px 28px rgba(165, 0, 52, 0.22);
      }

      button:focus {
        outline: 4px solid rgba(165, 0, 52, 0.2);
        outline-offset: 2px;
      }

      button:hover {
        background: var(--primary-dark);
      }

      .footnote {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 0.82rem;
        font-weight: 700;
        text-align: center;
      }

      @media (max-width: 920px) {
        body {
          padding: 22px;
          place-items: start center;
        }

        .login-shell {
          grid-template-columns: 1fr;
          min-height: 0;
        }

        .brand-panel {
          display: none;
        }

        .login-panel {
          padding: 26px;
        }

        .mobile-logo {
          display: grid;
          justify-items: center;
          gap: 12px;
          margin-bottom: 24px;
          text-align: center;
        }

        .mobile-logo img {
          width: min(270px, 76vw);
          height: auto;
          max-height: 106px;
          object-fit: contain;
        }

        .mobile-logo strong {
          color: var(--ink-strong);
          font-size: 1.35rem;
          font-weight: 800;
          line-height: 1.1;
        }

        .form-heading h2 {
          font-size: 1.75rem;
        }

        .remember-row {
          gap: 12px;
        }

        .secure-note {
          font-size: 0.78rem;
        }
      }

      @media (max-width: 460px) {
        body {
          padding: 0;
          background: #fff;
        }

        main {
          min-height: 100svh;
        }

        .login-shell {
          min-height: 100svh;
          border: 0;
          border-radius: 0;
          box-shadow: none;
        }

        .login-panel {
          align-content: start;
          padding: 34px 24px 28px;
        }

        form {
          gap: 18px;
        }

        input[type="email"],
        input[type="password"],
        button {
          min-height: 54px;
        }
      }

      @media (max-width: 360px) {
        .remember-row {
          align-items: flex-start;
          flex-direction: column;
          gap: 8px;
        }
      }
    </style>
  </head>
  <body>
    <main>
      <div class="login-shell">
        <section class="brand-panel" aria-label="Martin Sols CRM">
          <div class="brand-top">
            <div class="logo-card">
              <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
            </div>

            <div class="headline">
              <p class="eyebrow">CRM interne</p>
              <h1>Piloter les opérations du jour avec un accès sécurisé.</h1>
              <p>Un espace centralisé pour les équipes Martin Sols, pensé pour aller vite sur ordinateur comme sur smartphone.</p>
            </div>
          </div>

          <div class="preview" aria-hidden="true">
            <div class="preview-head">
              <div class="preview-title">
                <strong>Tableau de bord</strong>
                <span>Synthèse du jour</span>
              </div>
              <span class="site-pill">Palissy</span>
            </div>

            <div class="preview-grid">
              <div class="preview-stat">
                <span>Réservations</span>
                <strong>12</strong>
              </div>
              <div class="preview-stat">
                <span>Matériel</span>
                <strong>4/4</strong>
              </div>
              <div class="preview-stat">
                <span>Caisses</span>
                <strong>OK</strong>
              </div>
            </div>

            <div class="timeline">
              <div class="timeline-row">
                <span>Véhicules</span>
                <div class="bar"><span style="width: 72%"></span></div>
                <span>7 j</span>
              </div>
              <div class="timeline-row">
                <span>Locations</span>
                <div class="bar"><span style="width: 54%"></span></div>
                <span>4 j</span>
              </div>
              <div class="timeline-row">
                <span>Congés</span>
                <div class="bar"><span style="width: 34%"></span></div>
                <span>2 j</span>
              </div>
            </div>
          </div>
        </section>

        <div class="login-panel">
          <div class="mobile-logo" aria-label="Martin Sols CRM">
            <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
            <strong>CRM équipe</strong>
          </div>

          <form method="post" action="{{ route('login') }}">
            @csrf

            <div class="form-heading">
              <p class="eyebrow">Connexion</p>
              <h2>Accès Martin Sols</h2>
              <p>Identifiez-vous pour ouvrir votre espace CRM.</p>
            </div>

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

            <div class="remember-row">
              <label class="remember">
                <input name="remember" type="checkbox" value="1" />
                <span>Rester connecté</span>
              </label>
              <span class="secure-note">Session sécurisée</span>
            </div>

            <button type="submit">Se connecter</button>
            <p class="footnote">Accès réservé aux comptes autorisés.</p>
          </form>
        </div>
      </div>
    </main>
    @include('partials.pwa-scripts')
  </body>
</html>
