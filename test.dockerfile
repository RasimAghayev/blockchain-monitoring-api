# Test image. Same PHP version and extension set as php.dockerfile, plus the
# drivers needed by the test suite. Kept out of php.dockerfile so the runtime
# image does not carry test-only extensions.
FROM php:8.4-fpm@sha256:b0d7dff8f2d57155aebb5150dfcb5056a8c8d977aac3c2e21cfce32b8a4b004d

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    libpng-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libsqlite3-dev \
    zip \
    curl \
    unzip \
    git && \
    docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        posix \
        bcmath \
        gd \
        zip \
        pdo_sqlite \
        pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:2-bin@sha256:696bfbbb82d8ab6ad3672c505bedd659e3815fd1c03cb5ef65ef7ee07a083fa6 \
     /composer /usr/local/bin/composer

WORKDIR /var/www/html/be
USER www-data

CMD ["php-fpm"]
