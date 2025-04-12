# Use an official PHP image with Apache pre-installed
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies (git, zip for composer, postgresql client and dev files for pdo_pgsql)
# Also install common PHP extensions needed by CodeIgniter and Postgres
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libpq-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    zlib1g-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install intl \
    && docker-php-ext-install exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Apache Configuration ---
# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache virtual host to point to the public directory
# Create a new config file
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# --- Application Code ---
# Copy existing application directory contents (use .dockerignore to exclude vendor, .env etc)
COPY . /var/www/html

# Set ownership for Apache user (www-data)
# Make writable directory writable
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/writable

# --- Composer Install ---
# Install dependencies AFTER copying code
# Run as non-root user for better security practice if possible, but www-data might work
# USER www-data  # Might cause permission issues with later commands if not handled carefully
RUN composer install --no-interaction --no-dev --optimize-autoloader

# --- Expose Port & Start Command ---
EXPOSE 80

# Start command: Run migrations then start Apache
# Note: Migrations run every time container starts. Better: Use Render Jobs or build step if possible.
# For simplicity now, run before Apache starts.
CMD php spark migrate --all && apache2-foreground