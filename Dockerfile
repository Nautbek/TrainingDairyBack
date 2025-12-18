FROM php:8.4-fpm-alpine

# Установка системных зависимостей
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    icu-dev

# Установка PHP расширений
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Установка Redis расширения
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка PHP-FPM
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]

