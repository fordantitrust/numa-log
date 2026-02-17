FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    unzip \
    git \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_sqlite \
    zip \
    gd \
    mbstring

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Create data directory for persistent storage (database + backups)
RUN mkdir -p /var/www/html/data/backups \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/data

# PHP configuration
RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/app.ini \
    && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/app.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/app.ini

EXPOSE 80
