FROM php:8.3-fpm-alpine

ARG CONTAINER_EXEC_USER_ID=1000
ARG CONTAINER_EXEC_GROUP_ID=1000
RUN apk add --no-cache shadow && \
    groupmod -o -g $CONTAINER_EXEC_GROUP_ID www-data && \
    usermod -o -u $CONTAINER_EXEC_USER_ID -g www-data www-data && \
    apk del shadow;

# Install Composer
COPY --from=composer:2.9.5 /usr/bin/composer /usr/local/bin/composer

# 1. Постоянные пакеты, необходимые для работы приложения и расширений PHP
RUN apk add --no-cache \
    bash \
    supervisor \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    rabbitmq-c \
    zlib \
    postgresql-client \
    icu-libs \
    libevent

# 2. Временные зависимости, которые нужны только для компиляции
RUN apk update \
    && apk upgrade --available \
    && apk add --no-cache --virtual build-deps \
        autoconf \
        build-base \
        icu-dev \
        libevent-dev \
        openssl-dev \
        libzip-dev \
        zlib-dev \
        bzip2 \
        git \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        postgresql-dev \
        linux-headers \
        curl \
        wget \
        rabbitmq-c-dev \
&& docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
&& docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) \
    intl \
    gd \
    bcmath \
    pdo_pgsql \
    sockets \
    zip \
&& pecl channel-update pecl.php.net \
    && pecl install -o -f \
        redis \
        event \
        amqp \
        xdebug-3.5.0 \
    && rm -rf /tmp/pear \
    && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini \
    && echo "extension=event.so" > /usr/local/etc/php/conf.d/event.ini \
    && echo "extension=amqp.so" > /usr/local/etc/php/conf.d/amqp.ini \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" \
    && echo "xdebug.start_with_request=trigger" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" \
    && echo "xdebug.client_host=host.docker.internal" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini" \
    && apk del build-deps

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY deploy/prod/supervisor/supervisord.conf /etc/supervisord.conf

# Задаем рабочую директорию, чтобы пути в Supervisor (например /app/bin/console) работали корректно
WORKDIR /app

CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]
