
# Stage 1: Composer dependencies
FROM composer:2 AS composer-deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs


# Stage 2: Node — build frontend assets (Vite + Tailwind)
FROM node:20-alpine AS node-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build


# Stage 3: Final runtime — PHP 8.3 + Apache (Menggunakan Bookworm/Stable)
FROM php:8.3-apache-bookworm
# System deps for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev \
    libxml2-dev unzip curl default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip gd bcmath opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Apache: point DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy app source
COPY . .

# Copy composer vendor from stage 1
COPY --from=composer-deps /app/vendor/ vendor/

# Re-run autoloader with full source
COPY --from=composer-deps /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev

# Copy built frontend assets from stage 2
COPY --from=node-build /app/public/build/ public/build/

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Queue worker entrypoint (used by queue-worker service)
COPY queue-worker-entrypoint.sh /var/www/html/queue-worker-entrypoint.sh
RUN chmod +x /var/www/html/queue-worker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
