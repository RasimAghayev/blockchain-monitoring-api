# Use the official PHP image with FPM
# Digest pinned (R91): the lock file is resolved against PHP 8.4, so a floating
# tag would let the runtime drift away from the platform the lock was built for.
FROM php:8.4-fpm@sha256:f5431b57bda75455d5271967cc6b67df8f0d0b943832756e0c78eccdc98767dd AS build

# Install system dependencies and PHP extensions
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
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        pdo_pgsql && \
    docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && \
    # Clean up apt and unnecessary files to minimize image size
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    # Install Composer (only in the build stage)
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create a smaller final image
FROM php:8.4-fpm@sha256:f5431b57bda75455d5271967cc6b67df8f0d0b943832756e0c78eccdc98767dd

# Copy necessary artifacts from the build stage
COPY --from=build /usr/local/bin/composer /usr/local/bin/composer
COPY --from=build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=build /usr/local/bin/docker-php-ext-enable /usr/local/bin/

# Install necessary runtime dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable PHP extensions
RUN docker-php-ext-enable \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    pdo_pgsql

# Set working directory
WORKDIR /var/www/html/be

USER root
# Copy application source code and set ownership/permissions
COPY --chown=www-data:www-data ./src/be /var/www/html/be
RUN chown -R www-data:www-data /var/www/html/be/storage /var/www/html/be/bootstrap/cache && \
    chmod -R 777 /var/www/html/be/storage /var/www/html/be/bootstrap/cache

# Switch to non-root user
USER www-data

# Expose the PHP-FPM port
EXPOSE 9000

# Healthcheck: FPM listens on TCP 9000. A bare "is the process up" check is not
# enough, so probe the port with the runtime's own socket support.
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=10s \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);'

# Start PHP-FPM
CMD ["php-fpm"]