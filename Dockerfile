FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev nodejs npm nginx \
    && docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default

EXPOSE 8000

CMD cp /etc/secrets/.env /var/www/.env && \
    php artisan config:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan migrate --force && \
    php artisan mqtt:listen & \
    php artisan queue:listen --tries=1 --timeout=0 & \
    (while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done) & \
    php-fpm -D && \
    nginx -g "daemon off;"