FROM bnussbau/serversideup-php:8.3-fpm-nginx-alpine-imagick-chromium

USER www-data

# Set working directory
WORKDIR /var/www/html

# Create required directories
RUN mkdir -p storage/logs \
    && mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p bootstrap/cache \
    && mkdir -p database

COPY --chown=www-data:www-data ./.env.example ./.env

# Install application dependencies
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

COPY --chown=www-data:www-data package.json package-lock.json ./
RUN npm ci

# Copy application files
COPY --chown=www-data:www-data . .
RUN npm run build

ENV AUTORUN_ENABLED=true
# Expose port 80
EXPOSE 8080
