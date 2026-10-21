# Composer image pinned to the same PHP version and extension set as php.dockerfile.
# Resolving dependencies against a different PHP version than the runtime
# produces a lock file that is not installable in production.
#
# No HEALTHCHECK: this image runs a batch command and exits. There is no long
# running process to probe.
FROM php:8.5-cli@sha256:8e5d746f87655d89573e76d2b9125ed1b1578e47d3c466a2b2fdfc35593d9b41

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
        bcmath \
        exif \
        pcntl \
        posix \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:2-bin@sha256:696bfbbb82d8ab6ad3672c505bedd659e3815fd1c03cb5ef65ef7ee07a083fa6 \
     /composer /usr/local/bin/composer

ENV COMPOSER_MEMORY_LIMIT=-1

# Run as the same uid the runtime uses. Every service bind-mounts the same
# ./src/be, so a second uid in the mix leaves root-owned files behind that
# www-data then cannot rewrite. That surfaced as an empty 200 on every API
# request: Monolog could not open storage/logs/laravel.log and threw from inside
# the exception handler.
USER www-data

WORKDIR /app

CMD ["composer"]
