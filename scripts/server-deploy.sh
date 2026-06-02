#!/usr/bin/env bash
# Incremental deploy on the server (safe to run on every push).
# Requires .env.local and JWT keys already present (see server-bootstrap.sh once).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose --env-file .env.local -f docker-compose.server.yml"

if [[ ! -f .env.local ]]; then
  echo "ERROR: .env.local missing. Run scripts/server-bootstrap.sh once on this server."
  exit 1
fi

echo "Pulling latest code..."
git fetch origin
git reset --hard "origin/${DEPLOY_BRANCH:-main}"

echo "Rebuilding containers..."
$COMPOSE up -d --build

echo "Waiting for PostgreSQL..."
until $COMPOSE exec -T matisse-db pg_isready -U app -q; do sleep 1; done

echo "Ensuring pgvector extension..."
$COMPOSE exec -T matisse-db psql -U app -d app -c "CREATE EXTENSION IF NOT EXISTS vector;"

echo "Running migrations..."
$COMPOSE exec -T matisse-app php bin/console doctrine:migrations:migrate --no-interaction

echo "Clearing Symfony cache..."
$COMPOSE exec -T matisse-app php bin/console cache:clear --no-warmup
$COMPOSE exec -T matisse-app php bin/console cache:warmup

echo "Deploy finished at $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
