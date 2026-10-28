# مِداد | MIDAD — container image for hosts that build from a Dockerfile (Render, Koyeb, Fly.io…).

# 1) Front-end assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# 2) PHP dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# 3) Runtime: Apache + PHP 8.3
FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev libpng-dev libjpeg-dev libwebp-dev libicu-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql pdo_mysql zip gd intl bcmath opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/midad.conf \
    && a2enconf midad \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'opcache.enable=1\nopcache.validate_timestamps=0\nupload_max_filesize=8M\npost_max_size=10M\nexpose_php=Off\n' > "$PHP_INI_DIR/conf.d/midad.ini"

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
RUN rm -rf public/storage tests node_modules \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

EXPOSE 10000
CMD ["docker/start.sh"]
