#!/bin/bash
set -e

cd /var/www/html

mkdir -p var/log var/cache
chown -R appuser:appuser var/

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

if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
    echo "Generating JWT keypair (missing config/jwt/*.pem)..."
    mkdir -p config/jwt
    su -s /bin/bash appuser -c "php bin/console lexik:jwt:generate-keypair --skip-if-exists"
fi

chown -R appuser:appuser var/
chmod -R 777 var/

exec "$@"
