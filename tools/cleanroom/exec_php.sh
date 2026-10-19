#!/usr/bin/env bash
# ==============================================================================
# Cleanroom PHP Execution Wrapper — Matisse.Api.Sf
# ==============================================================================
# Executes PHP/Composer/Vendor binaries transparently across:
#   1. Running Docker container (MATISSE_CONTAINER, default: matisse-app)
#   2. Ephemeral Docker container (MATISSE_IMAGE, default: myapp-myapp-app:latest)
#   3. Local host PHP binary (fallback)
# ==============================================================================
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
CONTAINER_NAME="${MATISSE_CONTAINER:-matisse-app}"
IMAGE_NAME="${MATISSE_IMAGE:-myapp-myapp-app:latest}"

if command -v docker >/dev/null 2>&1; then
    if docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "${CONTAINER_NAME}"; then
        exec docker exec -i "${CONTAINER_NAME}" "$@"
    fi

    if docker image inspect "${IMAGE_NAME}" >/dev/null 2>&1; then
        exec docker run --rm \
            --network host \
            -v "${REPO_ROOT}:/var/www/html" \
            -w /var/www/html \
            "${IMAGE_NAME}" "$@"
    fi
fi

if command -v php >/dev/null 2>&1; then
    cd "${REPO_ROOT}"
    exec "$@"
fi

echo "ERROR: No PHP runtime available (checked container '${CONTAINER_NAME}', image '${IMAGE_NAME}', and host 'php')." >&2
exit 1
