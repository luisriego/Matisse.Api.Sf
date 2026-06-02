#!/usr/bin/env bash
# One-time bootstrap on a fresh Ubuntu server (Docker already installed).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose --env-file .env.local -f docker-compose.server.yml"

if [[ ! -f .env.local ]]; then
  echo "Creating .env.local from .env.server.dist — edit secrets before exposing the server!"
  cp .env.server.dist .env.local
  echo "Generated .env.local — update APP_SECRET, POSTGRES_PASSWORD, JWT_PASSPHRASE, DATABASE_URL."
fi

# JWT keys (gitignored)
if [[ ! -f config/jwt/private.pem ]]; then
  echo "Generating JWT keys..."
  mkdir -p config/jwt
  JWT_PASSPHRASE="$(grep '^JWT_PASSPHRASE=' .env.local | cut -d= -f2- | tr -d '"')"
  if [[ -z "${JWT_PASSPHRASE}" || "${JWT_PASSPHRASE}" == change-me-jwt-passphrase ]]; then
    JWT_PASSPHRASE="$(openssl rand -hex 16)"
    sed -i "s/^JWT_PASSPHRASE=.*/JWT_PASSPHRASE=${JWT_PASSPHRASE}/" .env.local
    echo "Set random JWT_PASSPHRASE in .env.local"
  fi
  docker run --rm -v "$ROOT/config/jwt:/out" alpine/openssl \
    genpkey -out /out/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass "pass:${JWT_PASSPHRASE}"
  docker run --rm -v "$ROOT/config/jwt:/out" alpine/openssl \
    pkey -in /out/private.pem -out /out/public.pem -pubout -passin "pass:${JWT_PASSPHRASE}"
  chmod 600 config/jwt/private.pem
fi

echo "Building and starting containers..."
$COMPOSE up -d --build

echo "Waiting for PostgreSQL..."
until $COMPOSE exec -T matisse-db pg_isready -U app -q; do sleep 1; done

echo "Enabling pgvector..."
$COMPOSE exec -T matisse-db psql -U app -d app -c "CREATE EXTENSION IF NOT EXISTS vector;"

echo "Running migrations..."
$COMPOSE exec -T matisse-app php bin/console doctrine:migrations:migrate --no-interaction

echo "Seeding catalogs (expense + income types)..."
$COMPOSE exec -T matisse-app php bin/console app:seed:expense-types --all
$COMPOSE exec -T matisse-app php bin/console app:seed:income-types --all

echo ""
echo "Done. API should be up on port \${APP_PORT:-80}."
echo "Swagger UI: http://<server-ip>/api/v1/doc"
echo "Register first user: POST /api/v1/users/register then activate."
