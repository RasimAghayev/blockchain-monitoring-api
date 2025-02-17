# Use the official PHP image with FPM
FROM php:8.4-fpm AS build

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
FROM php:8.4-fpm

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

# Start PHP-FPM
CMD ["php-fpm"]