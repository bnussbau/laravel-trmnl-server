# Composer dependencies stage
FROM composer:latest AS composer
WORKDIR /app
COPY composer.* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --ignore-platform-req=ext-imagick

# PHP Stage
FROM php:8.3-fpm-alpine3.20

# Install system dependencies in a single layer
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpq \
    nodejs \
    npm \
    git \
    curl \
    zip \
    unzip \
    imagemagick-dev \
    chromium

# Configure environment variables
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium \
    PUPPETEER_DOCKER=1

# Install PHP extensions and configure imagick in a single layer
RUN mkdir -p /usr/src/php/ext/imagick \
    && chmod 777 /usr/src/php/ext/imagick \
    && curl -fsSL https://github.com/Imagick/imagick/archive/refs/tags/3.7.0.tar.gz | tar xvz -C "/usr/src/php/ext/imagick" --strip 1 \
    && docker-php-ext-install imagick opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create required directories and set permissions in a single layer
RUN mkdir -p /var/log/supervisor \
    storage/logs \
    storage/framework/{cache,sessions,views} \
    bootstrap/cache \
    database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 777 database

# Copy composer dependencies from composer stage
COPY --from=composer /app/vendor ./vendor
COPY --chown=www-data:www-data composer.* ./

COPY package*.json ./
RUN npm ci

# Copy application files
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data ./.env.example ./.env

RUN composer dump-autoload --optimize
RUN npm run build

# Expose port 80
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
