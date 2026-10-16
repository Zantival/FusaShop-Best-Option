# Build stage for frontend assets
FROM node:20 AS node-builder
WORKDIR /app
COPY package*.json tailwind.config.js postcss.config.js ./
RUN npm install
COPY resources resources
RUN mkdir -p public/assets/css
RUN npm run build

# Production PHP stage
FROM php:8.3-apache
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    zip \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd intl \
    && a2enmod rewrite \
    && a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork || true \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install redis && docker-php-ext-enable redis
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
RUN if [ ! -f .env ]; then cp .env.example .env; fi
RUN php artisan key:generate --force --ansi
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
RUN rm -f public/storage && ln -s ../storage/app/public public/storage
RUN chown -R www-data:www-data storage bootstrap/cache public/storage

COPY --from=node-builder /app/public/assets public/assets

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -i 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
CMD ["sh", "-c", "a2dismod mpm_event || true && a2dismod mpm_worker || true && a2enmod mpm_prefork || true && apache2-foreground"]
