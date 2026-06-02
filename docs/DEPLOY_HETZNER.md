# Deploy en Hetzner (modo “jugar”)

Guía mínima para levantar **Matisse API** en un VPS Ubuntu (ej. `ubuntu-matisse` en Falkenstein).

## Requisitos del servidor

- Ubuntu 22.04/24.04
- Docker Engine + plugin Compose v2
- Puertos **80** (API) abiertos en firewall Hetzner + UFW si aplica
- Repo clonado en el servidor (SSH deploy key si es privado)

## 1. Instalar Docker (una vez)

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker "$USER"
# cerrar sesión SSH y volver a entrar
```

## 2. Clonar el proyecto

```bash
git clone <tu-repo> /opt/matisse-api
cd /opt/matisse-api
```

## 3. Configurar entorno

```bash
cp .env.server.dist .env.local
nano .env.local
```

Cambia al menos:

| Variable | Qué poner |
|----------|-----------|
| `APP_SECRET` | `openssl rand -hex 16` |
| `POSTGRES_PASSWORD` | contraseña fuerte |
| `DATABASE_URL` | misma contraseña en la URL (`app:PASSWORD@matisse-db...`) |
| `JWT_PASSPHRASE` | frase secreta para las claves JWT |
| `CORS_ALLOW_ORIGIN` | URL de tu frontend, o regex amplia para pruebas |

## 4. Bootstrap automático

```bash
chmod +x scripts/server-bootstrap.sh
./scripts/server-bootstrap.sh
```

Hace: build Docker, migraciones, seed de tipos de gasto e ingreso, genera JWT si faltan.

## 5. Comprobar

```bash
curl -s http://127.0.0.1/api/v1/doc | head
# Debe devolver HTML de Swagger (Matisse API)

curl -s http://127.0.0.1/api/v1/resident-unit/health-check
```

Desde fuera (con Caddy en :80): `http://91.99.215.92/api/v1/doc`  
API directa (sin Caddy): `http://91.99.215.92:8080/api/v1/doc` — solo si cambias el mapeo de puertos.

Frontend Vite: ver [DEPLOY_FRONTEND.md](./DEPLOY_FRONTEND.md).

## Comandos útiles

```bash
# Ver logs
docker compose -f docker-compose.server.yml logs -f matisse-app

# Entrar al contenedor
docker compose -f docker-compose.server.yml exec matisse-app bash

# Actualizar código
git pull
docker compose -f docker-compose.server.yml up -d --build
docker compose -f docker-compose.server.yml exec matisse-app php bin/console doctrine:migrations:migrate --no-interaction

# Reiniciar
docker compose -f docker-compose.server.yml restart
```

## Primer usuario

1. `POST /api/v1/users/register` con email/password
2. Activar cuenta (flujo de activación del proyecto)
3. `POST /api/v1/login_check` → JWT
4. Completar wizard de setup (unidades, cuentas, saldos…)

## Frontend

Apunta el frontend a `http://91.99.215.92` (o tu dominio) y asegura que `CORS_ALLOW_ORIGIN` incluya el origen del navegador.

## Notas de seguridad (play vs producción)

- Esta stack usa imagen **development** + volumen montado: ideal para iterar, no para producción dura.
- PostgreSQL **no** expone puerto al host (solo red Docker interna).
- Cambia todas las contraseñas por defecto antes de abrir la IP al mundo.
- `gcloud-key.json` y credenciales Mailtrap **no** subir al servidor salvo que uses esas integraciones.

## HTTPS (opcional)

Para dominio propio, pon **Caddy** o **nginx** delante en el host:

```text
api.tudominio.com → reverse proxy → localhost:80 (matisse-app)
```

Certbot / Caddy automatic HTTPS recomendado antes de uso real.

## Deploy automático con GitHub Actions

Tras el **primer bootstrap manual** en el servidor, cada push a `main` que pase CI dispara el deploy.

### Secrets en GitHub (Settings → Secrets and variables → Actions)

| Secret | Ejemplo | Descripción |
|--------|---------|-------------|
| `DEPLOY_HOST` | `91.99.215.92` | IP o hostname del VPS |
| `DEPLOY_USER` | `root` | Usuario SSH |
| `DEPLOY_SSH_KEY` | contenido de `id_ed25519` | Clave privada (sin passphrase recomendado para CI) |
| `DEPLOY_SSH_PORT` | `22` | Opcional |
| `DEPLOY_PATH` | `/opt/matisse-api` | Opcional; ruta del clone en el servidor |

### Environment (opcional)

El workflow usa el environment `hetzner-play` (puedes crearlo en GitHub para approval manual o secrets por entorno).

### Flujo

1. Push/merge a `main` → corre **CI** (tests, PHPStan, CS-Fixer).
2. Si CI pasa → **Deploy to Hetzner** ejecuta `scripts/server-deploy.sh` por SSH.
3. El script hace `git pull`, `docker compose up --build`, migraciones y `cache:warmup`.
4. Smoke test: `GET /api/v1/resident-unit/health-check`.

Deploy manual: **Actions → Deploy to Hetzner → Run workflow**.

### Clave SSH de deploy (una vez)

En tu máquina:

```bash
ssh-keygen -t ed25519 -C "github-actions-matisse" -f ~/.ssh/matisse_deploy -N ""
```

En el servidor (`authorized_keys` del usuario deploy):

```bash
cat matisse_deploy.pub >> ~/.ssh/authorized_keys
```

En GitHub: secret `DEPLOY_SSH_KEY` = contenido de `matisse_deploy` (privada).

### Qué NO va en GitHub

- `.env.local`, JWT keys, `gcloud-key.json` — solo en el servidor.
- Los seeds (`expense-types`, `income-types`) **no** se re-ejecutan en cada deploy (evita truncar datos).
