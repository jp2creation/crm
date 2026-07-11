# Martin Sols CRM Mobile

Application mobile Capacitor connectee a l'API Laravel Sanctum.

## Commandes

```bash
npm install
npm run dev
npm run build
npm run cap:sync
npm run cap:open:android
```

## Configuration

Copier `.env.example` vers `.env` si l'API Laravel n'est pas sur `http://127.0.0.1:8000`.

```env
VITE_API_BASE_URL=https://crm.example.com
```

L'app utilise `POST /api/mobile/token`, puis envoie le token avec `Authorization: Bearer ...` sur les endpoints CRM existants.
