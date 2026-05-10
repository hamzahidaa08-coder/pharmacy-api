# Use PHP 8.2 CLI as the base image
FROM php:8.2-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions required for Laravel and PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd xml

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the application code
COPY . .

# Ensure storage and bootstrap/cache directories are writable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Install Composer dependencies optimized for production
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Expose port 10000
EXPOSE 10000

# Start the application using php artisan serve
CMD php artisan serve --host=0.0.0.0 --port=10000
