# Test image. Same PHP version and extension set as php.dockerfile, plus the
# drivers needed by the test suite. Kept out of php.dockerfile so the runtime
# image does not carry test-only extensions.
FROM php:8.5-fpm@sha256:36b66e02388a304d90cfa548172831b2ba3fd2a1a9cb5aa97ce6df01360518dd

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

COPY --from=composer/composer:2-bin@sha256:206a6dc365fd66aac01e2744d8a6bc898108e802b1bcdde1cd782da788bf1db9 \
     /composer /usr/local/bin/composer

WORKDIR /var/www/html/be
USER www-data

CMD ["php-fpm"]
