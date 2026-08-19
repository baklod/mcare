# syntax=docker/dockerfile:1.7

FROM php:8.2.32-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libonig-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j1 \
        bcmath \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.1 /usr/bin/composer /usr/local/bin/composer

RUN mv "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini" \
    && { \
        echo "memory_limit=512M"; \
        echo "max_execution_time=120"; \
        echo "post_max_size=25M"; \
        echo "upload_max_filesize=20M"; \
        echo "date.timezone=Asia/Manila"; \
    } > "${PHP_INI_DIR}/conf.d/99-mcare-development.ini"

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
