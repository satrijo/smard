# Gunakan image dasar PHP dengan FPM
FROM php:8.2-fpm

# Install dependensi sistem dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    gnupg \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql

# Install Node.js v22 dan npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
RUN apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Salin file proyek Laravel ke dalam container
COPY . /var/www

# Ganti kepemilikan file
RUN chown -R www-data:www-data /var/www

# Expose port 9000 untuk PHP-FPM
EXPOSE 9000

# Proses utama container tetap PHP-FPM
CMD ["php-fpm"]