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
        --ink: #111827;
        --muted: #6b7280;
        --line: #e5e7eb;
        --surface: #ffffff;
        --page: #f7f8fa;
      }

      * {
        box-sizing: border-box;
      }

      body {
        min-height: 100vh;
        margin: 0;
        display: grid;
        place-items: center;
        padding: 24px;
        background:
          linear-gradient(140deg, rgba(149, 0, 46, 0.09), transparent 34%),
          var(--page);
        color: var(--ink);
        font-family: "DM Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      }

      main {
        width: min(100%, 430px);
      }

      .brand {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 22px;
      }

      .brand img {
        width: 58px;
        height: 58px;
        object-fit: contain;
      }

      .brand-title {
        display: grid;
        gap: 2px;
      }

      .brand-title strong {
        font-size: 1.18rem;
        font-weight: 700;
      }

      .brand-title span {
        color: var(--muted);
        font-size: 0.92rem;
      }

      form {
        display: grid;
        gap: 18px;
        padding: 26px;
        background: var(--surface);
        border: 1px solid var(--line);
        border-radius: 8px;
        box-shadow: 0 20px 60px rgba(17, 24, 39, 0.08);
      }

      h1 {
        margin: 0 0 2px;
        font-size: 1.45rem;
        line-height: 1.2;
      }

      .field {
        display: grid;
        gap: 7px;
      }

      label {
        font-size: 0.9rem;
        font-weight: 600;
      }

      input[type="email"],
      input[type="password"] {
        width: 100%;
        min-height: 46px;
        padding: 0 13px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        color: var(--ink);
        font: inherit;
      }

      input:focus {
        outline: 3px solid rgba(149, 0, 46, 0.15);
        border-color: var(--primary);
      }

      .remember {
        display: flex;
        align-items: center;
        gap: 9px;
        color: var(--muted);
        font-size: 0.92rem;
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
        min-height: 46px;
        border: 0;
        border-radius: 6px;
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

      @media (max-width: 480px) {
        body {
          padding: 18px;
        }

        form {
          padding: 22px;
        }
      }
    </style>
  </head>
  <body>
    <main>
      <div class="brand">
        <img src="{{ asset('martin-sols-logo.png') }}" alt="Martin Sols" />
        <div class="brand-title">
          <strong>Martin Sols CRM</strong>
          <span>Acces equipe</span>
        </div>
      </div>

      <form method="post" action="{{ route('login') }}">
        @csrf

        <h1>Connexion</h1>

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
    </main>
  </body>
</html>
