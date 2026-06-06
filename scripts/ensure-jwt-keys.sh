#!/usr/bin/env bash
# Ensures config/jwt/private.pem and public.pem exist with correct permissions.
# Safe to run on every deploy (idempotent).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env.local ]]; then
  echo "ERROR: .env.local missing. Cannot ensure JWT keys."
  exit 1
fi

fix_permissions() {
  if [[ -d config/jwt ]]; then
    # Apache (www-data) must read private.pem inside the container volume mount.
    chmod 644 config/jwt/public.pem config/jwt/private.pem 2>/dev/null || true
    chown -R 1000:1000 config/jwt/ 2>/dev/null || true
  fi
}

if [[ -f config/jwt/private.pem && -f config/jwt/public.pem ]]; then
  echo "JWT keys already present."
  fix_permissions
  exit 0
fi

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

fix_permissions
echo "JWT keys generated."
