# syntax=docker/dockerfile:1

# ==========================================================================
#  Couture Flow — image de production pour Render
# ==========================================================================
#  Trois étapes :
#    1. assets  : compilation Vite / Tailwind
#    2. vendor  : dépendances Composer (couche cachée, invalidée uniquement
#                 par composer.lock)
#    3. runtime : PHP-FPM + Nginx dans un seul conteneur
#
#  Construction locale :
#    docker build -t coutureflow .
#    docker run --rm -p 8080:8080 -e PORT=8080 --env-file .env coutureflow
#
#  Render impose le port d'écoute via la variable PORT : Nginx l'écoute
# dynamiquement (voir docker/entrypoint.sh), aucun port n'est figé ici.
# ==========================================================================


# ------------------------------------------------------------------ 1. assets
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


# ------------------------------------------------------------------ 2. vendor
FROM composer:2.7 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# Ni scripts ni autoloader ici : package:discover et l'autoloader sont
# générés dans l'étape finale, une fois toute la source disponible.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress


# ---------------------------------------------------------------- 3. runtime
FROM php:8.3-fpm-alpine AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PORT=10000

# Nginx sert les fichiers statiques et transmet PHP à PHP-FPM.
# pdo_pgsql est compilé pour permettre de basculer sur la base PostgreSQL
# fournie par Render sans avoir à reconstruire l'image.
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        tzdata \
        icu-libs \
        oniguruma \
        libzip \
        libpq \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
        postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && apk del .build-deps \
    && rm -rf /tmp/*

# Les journaux partent sur stdout/stderr, lus par Render.
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

WORKDIR /var/www/html

COPY --from=vendor --chown=root:root /app/vendor ./vendor
COPY --chown=root:root . .
COPY --from=assets --chown=root:root /app/public/build ./public/build

COPY docker/php/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Dossiers inscriptibles par le compte www-data utilisé par PHP-FPM.
RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# package:discover a besoin de toute la source applicative. La clé utilisée
# ici est jetable et n'est jamais conservée : la clé réelle provient de la
# variable APP_KEY fournie par Render au démarrage.
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction --no-scripts \
    && APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')" \
       php artisan package:discover --ansi

ENTRYPOINT ["/usr/local/bin/entrypoint"]
