# Use the composer image as the base
FROM composer:latest

# Install necessary packages for exif extension on Alpine
RUN apk update && \
    apk add --no-cache \
    libjpeg-turbo-dev \
    libexif-dev \
    && docker-php-ext-install exif
