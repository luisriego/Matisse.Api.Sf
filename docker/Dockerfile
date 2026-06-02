# ─────────────────────────────────────────────────────────────
# Stage 1: base — shared system dependencies
# ─────────────────────────────────────────────────────────────
FROM php:8.3-apache AS base

ARG UID

RUN adduser -u ${UID} --disabled-password --gecos "" appuser \
    && mkdir /home/appuser/.ssh \
    && chown -R appuser:appuser /home/appuser/ \
    && echo "StrictHostKeyChecking no" >> /home/appuser/.ssh/config

# Shell aliases (available in all stages)
RUN echo "alias sf=bin/console" >> /home/appuser/.bashrc \
    && echo "alias fix='composer fix\:standards'" >> /home/appuser/.bashrc \
    && echo "alias analyze='composer analyze\:standards'" >> /home/appuser/.bashrc \
    && echo "alias vv='bin/console messenger:consume async -vv'" >> /home/appuser/.bashrc

# System packages and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git acl openssl openssh-client wget zip dos2unix \
        libpng-dev zlib1g-dev libzip-dev libxml2-dev libicu-dev \
        libpq-dev \
    && docker-php-ext-install intl pdo zip pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer \
    && composer self-update

# Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get install -y --no-install-recommends symfony-cli \
    && rm -rf /var/lib/apt/lists/*

# Apache config
COPY ./default.conf /etc/apache2/sites-available/default.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && a2dissite 000-default \
    && a2ensite default

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN mkdir -p /var/www/html
WORKDIR /var/www/html

# ─────────────────────────────────────────────────────────────
# Stage 2: development — adds Xdebug and APCu
# ─────────────────────────────────────────────────────────────
FROM base AS development

RUN pecl channel-update pecl.php.net \
    && pecl install -f xdebug-3.3.1 \
    && pecl install -f apcu \
    && docker-php-ext-enable xdebug apcu

# Test aliases (only needed in dev)
RUN echo "alias test='bin/phpunit --colors=always'" >> /home/appuser/.bashrc \
    && echo "alias utest='bin/phpunit --colors=always --testsuite=unit'" >> /home/appuser/.bashrc \
    && echo "alias aitest='bin/phpunit --colors=always --testsuite=authenticated_integration'" >> /home/appuser/.bashrc \
    && echo "alias tf='bin/phpunit --colors=always --filter'" >> /home/appuser/.bashrc \
    && echo "alias tc='bin/phpunit --coverage-html coverage/'" >> /home/appuser/.bashrc \
    && echo "alias tcf='bin/phpunit --coverage-html coverage/ --colors=always --filter'" >> /home/appuser/.bashrc

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN dos2unix /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

# ─────────────────────────────────────────────────────────────
# Stage 3: production — clean image without dev tools
# ─────────────────────────────────────────────────────────────
FROM base AS production

# OPcache is already enabled by the base image (docker-php-ext-opcache.ini)
