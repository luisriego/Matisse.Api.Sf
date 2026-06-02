# Frontend Vite + API en Hetzner

Repositorio frontend: [Matisse.front.React-TailAdmin](https://github.com/luisriego/Matisse.front.React-TailAdmin)

El frontend (Vite/React/TailAdmin) vive en **otro repositorio**. La API sigue en este repo. En producción/play conviene servir ambos desde el **mismo origen** (misma IP o dominio) para simplificar CORS y cookies.

## Ventaja de Matisse React

Las llamadas usan rutas **relativas** (`/api/v1/...`), no `VITE_API_BASE_URL`. En dev, Vite hace proxy a `localhost:1000`:

```18:24:/home/luisriego/Dev/React/Matisse.front.React-TailAdmin/vite.config.ts
  server: {
    proxy: {
      "/api": {
        target: "http://localhost:1000",
        changeOrigin: true,
      },
    },
  },
```

En el servidor solo hace falta **Caddy** sirviendo el `dist/` y proxy `/api` → Symfony. **No hay variables de entorno en el build.**

## Arquitectura recomendada (play)

```text
Internet :80
    │
    ▼
 Caddy (en el VPS, puerto 80)
    ├── /           →  /opt/matisse-web/dist   (build estático de Vite)
    └── /api/*      →  matisse-app:8080        (Symfony, solo red Docker)
```

- El navegador pide `http://91.99.215.92/` → SPA.
- La SPA llama `http://91.99.215.92/api/v1/...` → mismo host, sin CORS complicado.
- `CORS_ALLOW_ORIGIN` en la API puede ser la URL exacta del frontend o el mismo origen.

### Cambio en docker-compose.server.yml (API)

Expón la API **solo en localhost/red interna**, no en el puerto 80 público:

```yaml
matisse-app:
  ports:
    - '127.0.0.1:8080:80'   # Caddy hace proxy desde :80
```

Caddy escucha en `:80` y reenvía `/api` al contenedor.

---

## Variables de entorno en Vite

Vite **incrusta** las variables en build time. En el repo frontend:

```env
# .env.production
VITE_API_BASE_URL=http://91.99.215.92/api/v1
```

En código:

```ts
const apiBase = import.meta.env.VITE_API_BASE_URL;
fetch(`${apiBase}/login_check`, …);
```

Tras cambiar la URL hay que **volver a hacer build** (`npm run build`).

Con dominio:

```env
VITE_API_BASE_URL=https://api.tudominio.com/api/v1
# o mismo origen:
VITE_API_BASE_URL=/api/v1
```

`/api/v1` relativo es ideal si frontend y API comparten dominio vía Caddy.

---

## GitHub Actions (repo del frontend)

Workflow en el repo frontend: `.github/workflows/deploy.yml`

- Tras **Test** exitoso en `main` → `npm ci && npm run build` → sube `dist/` a `/opt/matisse-web`.
- Mismos secrets que la API: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`.
- Deploy manual: Actions → **Deploy Frontend to Hetzner**.

Workflow de referencia (ya en el repo frontend):

```yaml
name: Deploy Frontend

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: npm

      - name: Install & build
        run: |
          npm ci
          npm run build
        env:
          VITE_API_BASE_URL: /api/v1

      - name: Upload dist to server
        uses: appleboy/scp-action@v0.1.7
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          source: dist/*
          target: /opt/matisse-web
          strip_components: 1

      - name: Reload Caddy (optional)
        uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          script: sudo systemctl reload caddy
```

Mismos secrets `DEPLOY_*` que la API (o un usuario/deploy path distinto).

---

## Caddy en el VPS (una vez)

```bash
sudo apt install -y caddy
sudo mkdir -p /opt/matisse-web
```

`/etc/caddy/Caddyfile`:

```caddyfile
:80 {
    handle /api/* {
        reverse_proxy 127.0.0.1:8080
    }

    handle {
        root * /opt/matisse-web
        try_files {path} /index.html
        file_server
    }
}
```

```bash
sudo systemctl reload caddy
```

Para HTTPS con dominio, cambia `:80` por `matisse.tudominio.com` y Caddy obtiene el certificado solo.

---

## Orden de despliegue

1. **API** — bootstrap + GHA (`Deploy to Hetzner` en este repo).
2. **Caddy** — proxy `/api` → `127.0.0.1:8080`.
3. **Frontend** — build Vite + subir `dist/` a `/opt/matisse-web`.
4. **CORS** en `.env.local` de la API:

```env
CORS_ALLOW_ORIGIN='^https?://91\.99\.215\.92$'
# o con dominio:
# CORS_ALLOW_ORIGIN='^https?://matisse\.tudominio\.com$'
```

Si usas **mismo origen** (`VITE_API_BASE_URL=/api/v1`), CORS casi no importa para XHR same-origin.

---

## Alternativas más simples (solo jugar)

| Opción | Pros | Contras |
|--------|------|---------|
| **Caddy mismo VPS** (arriba) | Un solo servidor, barato | Config manual |
| **Frontend en Netlify/Cloudflare Pages** | Deploy automático, HTTPS gratis | CORS + URL API pública |
| **Vite preview en el VPS** | Rápido para pruebas | No usar en prod (`vite preview`) |
| **Dos puertos** (API :1000, front :80) | Sin Caddy | CORS obligatorio, URLs distintas |

Para **jugar**, Cloudflare Pages + API en Hetzner funciona si pones en la API:

```env
CORS_ALLOW_ORIGIN='^https://tu-app\.pages\.dev$'
```

---

## CI coordinado (opcional)

- **Opción A:** dos repos, dos workflows independientes (lo más habitual).
- **Opción B:** monorepo con `frontend/` + `api/` y un workflow con dos jobs.
- **Opción C:** workflow en API que dispara `repository_dispatch` en el repo frontend tras deploy API (overkill para play).

---

## Checklist frontend

- [ ] `VITE_API_BASE_URL` apunta al servidor correcto
- [ ] Login guarda JWT (localStorage / cookie) y lo envía en `Authorization: Bearer …`
- [ ] Rutas SPA: el server estático debe devolver `index.html` en rutas profundas (`try_files`)
- [ ] Tras deploy API, migraciones aplicadas antes de probar el wizard
