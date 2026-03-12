# ------- php -------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        icu-libs \
        libpq \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        postgresql-dev \
    && docker-php-ext-install -j$(nproc) \
        intl \
        opcache \
        pdo_pgsql \
        zip \
    && apk del .build-deps

COPY docker/php/php.ini "$PHP_INI_DIR/conf.d/app.ini"

WORKDIR /app

# ------- composer & vendor -------
FROM base AS vendor

ENV APP_ENV=prod

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && composer run-script post-install-cmd

# ------- frontend & release -------
FROM node:22-alpine AS frontend

WORKDIR /app
COPY package.json bun.lock* ./
RUN npm install --legacy-peer-deps

COPY webpack.config.js ./
COPY assets/ assets/
RUN npm run build

# ------- compress image -------
FROM base

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY . /app
COPY --from=vendor /app/vendor /app/vendor
COPY --from=frontend /app/public/build /app/public/build

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
EXPOSE 9000
