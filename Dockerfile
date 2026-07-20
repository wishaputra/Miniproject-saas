# Stage 1: Build PHP/Composer dependencies and Node.js assets
FROM php:8.3-fpm-alpine as builder

# Install system dependencies
RUN apk add --no-cache \
    zip \
    unzip \
    curl \
    git \
    nodejs \
    npm \
    libzip-dev \
    libpng-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql zip gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Install Node dependencies and build assets
RUN npm install && npm run build

# Stage 2: Production image
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    libzip-dev \
    libpng-dev

RUN docker-php-ext-install pdo_mysql zip gd

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

# Copy built files from builder stage
COPY --from=builder /app /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["sh", "-c", "nginx && php-fpm"]
