FROM bnussbau/php:8.3-fpm-opcache-imagick-puppeteer-alpine3.20

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create required directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p storage/logs \
    && mkdir -p storage/framework/{cache,sessions,views} \
    && chmod -R 775 storage \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 bootstrap/cache \
    && mkdir -p database \
    && touch database/database.sqlite \
    && chmod -R 777 database

# Copy application files
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data ./.env.example ./.env

# Install application dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN npm ci && npm run build

# Expose port 80
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
