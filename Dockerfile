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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js v22 dan npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
RUN apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy package files
COPY package*.json ./

# Install Node.js dependencies
RUN npm ci --only=production

# Copy application code
COPY . .

# Build assets
RUN npm run build

# Create necessary directories
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache

# Set proper permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 755 storage \
    && chmod -R 755 bootstrap/cache

# Create .env file if not exists
RUN if [ ! -f .env ]; then cp env.example .env; fi

# Generate application key
RUN php artisan key:generate --no-interaction

# Expose port 9000 untuk PHP-FPM
EXPOSE 9000

# Start PHP-FPM directly
CMD ["php-fpm"]
