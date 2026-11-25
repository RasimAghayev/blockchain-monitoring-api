# syntax=docker/dockerfile:1
# Consolidated PHP image for blockchain-monitoring-api.
# Targets: runtime (php-fpm), cli (composer/artisan php-cli), test (phpunit/pint).
#
# PHP 8.6 (latest stable, standing decision 1). pdo_mysql/mysqli are not
# installed: the project exclusively uses PostgreSQL (docker-compose.yml has
# no MySQL service, DB_CONNECTION=pgsql is the only configured default) —
# see docs/TECHNICAL_DEBT.md item 15. phpredis is likewise not installed:
# REDIS_CLIENT defaults to the pure-PHP `predis/predis` package already in
# composer.json, so no native Redis extension is needed in any image.

FROM php:8.6-fpm@sha256:23da58abd99055b97191fcca104126d552cc7b96c8cfe25f724311d52635696d AS runtime-build
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    libpng-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    curl \
    unzip \
    git && \
    docker-php-ext-install \
        pdo mbstring exif pcntl bcmath gd zip pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

FROM php:8.6-fpm@sha256:23da58abd99055b97191fcca104126d552cc7b96c8cfe25f724311d52635696d AS runtime
COPY --from=runtime-build /usr/local/bin/composer /usr/local/bin/composer
COPY --from=runtime-build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=runtime-build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=runtime-build /usr/local/bin/docker-php-ext-enable /usr/local/bin/
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libpq-dev libonig-dev libxml2-dev libzip-dev libfreetype6-dev libjpeg62-turbo-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-enable pdo mbstring exif pcntl bcmath gd zip pdo_pgsql
WORKDIR /var/www/html/be
USER root
COPY --chown=www-data:www-data ./src/be /var/www/html/be
RUN chown -R www-data:www-data /var/www/html/be/storage /var/www/html/be/bootstrap/cache && \
    chmod -R 777 /var/www/html/be/storage /var/www/html/be/bootstrap/cache
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=10s \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);'
CMD ["php-fpm"]

FROM php:8.6-cli@sha256:64340b5b5eb25273bdd0986124c8efb08dadfbd4fda6929c8a44618a318c7b17 AS cli
RUN apt-get update && apt-get install -y --no-install-recommends \
    git libjpeg62-turbo-dev libpng-dev libzip-dev libpq-dev unzip \
    && docker-php-ext-install bcmath exif pcntl posix zip pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer/composer:2-bin@sha256:2990de2e55e336422625cdac47b76dcf66a19e5b624d9c8b2f2c30a016242a58 /composer /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1
USER www-data
WORKDIR /app
CMD ["composer"]

FROM php:8.6-fpm@sha256:23da58abd99055b97191fcca104126d552cc7b96c8cfe25f724311d52635696d AS test
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential libpng-dev libpq-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libsqlite3-dev zip curl unzip git && \
    docker-php-ext-install \
        pdo mbstring exif pcntl posix bcmath gd zip pdo_sqlite pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer/composer:2-bin@sha256:2990de2e55e336422625cdac47b76dcf66a19e5b624d9c8b2f2c30a016242a58 /composer /usr/local/bin/composer
WORKDIR /var/www/html/be
USER www-data
CMD ["php-fpm"]
