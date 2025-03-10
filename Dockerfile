FROM php:8.3-fpm-alpine3.20

# Install system dependencies
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

# Configure Chromium Path
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium
ENV PUPPETEER_DOCKER=1

#RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS imagemagick-dev \
#&& pecl install imagick \
#&& docker-php-ext-enable imagick \
#&& apk del .build-deps \

#RUN docker-php-ext-install imagick \
#    && docker-php-ext-enable imagick

RUN mkdir -p /usr/src/php/ext/imagick
RUN chmod 777 /usr/src/php/ext/imagick
RUN curl -fsSL https://github.com/Imagick/imagick/archive/refs/tags/3.7.0.tar.gz | tar xvz -C "/usr/src/php/ext/imagick" --strip 1

# Install PHP extensions
RUN docker-php-ext-install opcache imagick

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data ./.env.example ./.env

# Install application dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN npm install && npm run build

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create required directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p storage/logs \
    && mkdir -p storage/framework/{cache,sessions,views} \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R 777 database

# Expose port 80
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
