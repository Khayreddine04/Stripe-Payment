# Using official PHP 8.3 FPM image from Docker Hub
FROM php:8.3-fpm

# Install system dependencies, nginx, and supervisor
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer \
    && composer --version

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd

# Set working directory
WORKDIR /app

# Copy composer.json first
COPY composer.json /app/

# Install dependencies (--no-scripts to prevent potential issues during build)
RUN cd /app && \
    if [ -f composer.lock ]; then \
        composer install --no-dev --optimize-autoloader --no-scripts; \
    else \
        composer install --no-dev --optimize-autoloader --no-scripts --no-plugins; \
    fi

# Copy the rest of the application
COPY . /app

# Copy configurations
COPY nginx.conf /etc/nginx/sites-available/default
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create directories and set permissions - FIXED
RUN mkdir -p /app/logs /app/uploads /app/includes/cache \
    /var/lib/nginx/tmp/client_body \
    /var/lib/nginx/tmp/proxy \
    /var/lib/nginx/tmp/fastcgi \
    /var/lib/nginx/tmp/uwsgi \
    /var/lib/nginx/tmp/scgi \
    /run/php \
    /var/log/supervisor && \
    touch /app/error_log /app/logs/debug.log && \
    chown -R nobody:nogroup /app /var/lib/nginx /run/php && \
    chmod -R 755 /app && \
    chmod -R 777 /app/logs /app/uploads /app/includes/cache /var/lib/nginx/tmp && \
    chmod 666 /app/error_log /app/logs/debug.log

# Expose port 80
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]