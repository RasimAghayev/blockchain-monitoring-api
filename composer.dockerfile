# Composer image pinned to the same PHP version and extension set as php.dockerfile.
# Resolving dependencies against a different PHP version than the runtime
# produces a lock file that is not installable in production.
#
# No HEALTHCHECK: this image runs a batch command and exits. There is no long
# running process to probe.
FROM php:8.4-cli@sha256:0177c7589c0715b63e5343342a7c1f1887db759ad0b0d6ad3bb949938bba6b47

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

COPY --from=composer/composer:2-bin@sha256:016c41066558559142758092f7ef286d66ae113df23d49e086fdda0166dd5f01 \
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
