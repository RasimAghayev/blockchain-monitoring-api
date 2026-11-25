# syntax=docker/dockerfile:1
# Consolidated PHP image for blockchain-monitoring-api.
# Targets: runtime (php-fpm), cli (composer/artisan php-cli), test (phpunit/pint).
# Same pinned PHP/Composer bases as previous root Dockerfiles.

FROM php:8.6-fpm@sha256:df9ead21fc3a43c80587a5461db3b859b0f6e3effe5da33a22fc762d5d8a4364 AS runtime-build
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
        pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd zip pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

FROM php:8.6-fpm@sha256:df9ead21fc3a43c80587a5461db3b859b0f6e3effe5da33a22fc762d5d8a4364 AS runtime
COPY --from=runtime-build /usr/local/bin/composer /usr/local/bin/composer
COPY --from=runtime-build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=runtime-build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=runtime-build /usr/local/bin/docker-php-ext-enable /usr/local/bin/
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libpq-dev libonig-dev libxml2-dev libzip-dev libfreetype6-dev libjpeg62-turbo-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-enable pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd zip pdo_pgsql
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

FROM php:8.6-cli@sha256:9bc69093cdff73d2dcf12bc34f4c308b8496a27dc3e6a4d0c332877289c6bf13 AS cli
RUN apt-get update && apt-get install -y --no-install-recommends \
    git libjpeg62-turbo-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-install bcmath exif pcntl posix zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer/composer:2-bin@sha256:269d47ef954e254227bccb35e3e40f642756abee44dded405e1fbb6f5e52af0c /composer /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1
USER www-data
WORKDIR /app
CMD ["composer"]

FROM php:8.6-fpm@sha256:df9ead21fc3a43c80587a5461db3b859b0f6e3effe5da33a22fc762d5d8a4364 AS test
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential libpng-dev libpq-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libsqlite3-dev zip curl unzip git && \
    docker-php-ext-install \
        pdo pdo_mysql mysqli mbstring exif pcntl posix bcmath gd zip pdo_sqlite pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer/composer:2-bin@sha256:269d47ef954e254227bccb35e3e40f642756abee44dded405e1fbb6f5e52af0c /composer /usr/local/bin/composer
WORKDIR /var/www/html/be
USER www-data
CMD ["php-fpm"]
