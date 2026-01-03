FROM php:8.3-fpm

# Install dependencies untuk ekstensi
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libmagickwand-dev \
    zip unzip git curl \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mbstring \
    exif \
    pcntl \
    bcmath \
    opcache \
    intl \
    pdo_mysql \
    mysqli \
    pdo_pgsql \
    pgsql \
    zip \
    xml \
    sockets \
    sysvsem \
    sysvshm \
    sysvmsg

# Install Imagick via PECL
RUN pecl install imagick && docker-php-ext-enable imagick

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Konfigurasi Git safe directory untuk mengatasi masalah ownership
RUN git config --global --add safe.directory /var/www/html

# Naikkan limit upload dan memory
RUN { \
    echo "upload_max_filesize=512M"; \
    echo "post_max_size=512M"; \
    echo "memory_limit=1024M"; \
    echo "max_execution_time=300"; \
    echo "max_input_time=300"; \
    } > /usr/local/etc/php/conf.d/custom.ini