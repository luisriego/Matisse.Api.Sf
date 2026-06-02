#!/bin/bash
set -e

cd /var/www/html

mkdir -p var/log var/cache

if command -v dos2unix >/dev/null; then
    dos2unix bin/console bin/phpunit 2>/dev/null || true
    [ -f tools/php-cs-fixer/vendor/bin/php-cs-fixer ] && dos2unix tools/php-cs-fixer/vendor/bin/php-cs-fixer 2>/dev/null || true
fi

if [ -f composer.json ]; then
    LOCK_HASH="$(md5sum composer.lock | awk '{print $1}')"
    MARKER="var/.composer-installed"

    if [ ! -f "${MARKER}" ] || [ "$(cat "${MARKER}")" != "${LOCK_HASH}" ]; then
        echo "Installing Composer dependencies (composer.lock changed)..."
        su -s /bin/bash appuser -c "composer install --no-interaction --prefer-dist"
        su -s /bin/bash appuser -c "echo '${LOCK_HASH}' > '${MARKER}'"
    fi
fi

chown -R appuser:appuser var/

exec "$@"
