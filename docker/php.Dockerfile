# syntax=docker/dockerfile:1
# Consolidated PHP image for blockchain-monitoring-api.
# Targets:
# - runtime: php-fpm for the web app (nginx upstream)
# - cli:     php-cli for artisan/batch commands
# - test:    php-cli/fpm-compatible base with sqlite + posix for PHPUnit/Pint
# All targets share the same pinned PHP patch and core extension set used by the app.

ARG PHP_FPM=php:8.4-fpm@sha256:212c3c9fc09d58f6b1a12b0b4a6a90fd44a209299a67633c2bccbc1dd53c55e0
ARG PHP_CLI=php:8.4-cli@sha256:0177c7589c0715b63e5343342a7c1f1887db759ad0b0d6ad3bb949938bba6b47
ARG COMPOSER=composer/composer:2-bin@sha256:56a1b0a72bacd78079e3fa49a49e7bf58326365753415c99385ea2b08bded0a5

FROM  AS runtime-build
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

FROM  AS runtime
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

FROM  AS cli
RUN apt-get update && apt-get install -y --no-install-recommends \
    git libjpeg62-turbo-dev libpng-dev libzip-dev unzip \
    && docker-php-ext-install bcmath exif pcntl posix zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from= /composer /usr/local/bin/composer
ENV COMPOSER_MEMORY_LIMIT=-1
USER www-data
WORKDIR /app
CMD ["composer"]

FROM  AS test
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential libpng-dev libpq-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libsqlite3-dev zip curl unzip git && \
    docker-php-ext-install \
        pdo pdo_mysql mysqli mbstring exif pcntl posix bcmath gd zip pdo_sqlite pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from= /composer /usr/local/bin/composer
WORKDIR /var/www/html/be
USER www-data
CMD ["php-fpm"]