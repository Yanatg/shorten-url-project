# Use an official PHP image with Apache pre-installed
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies + PHP extensions...
RUN apt-get update && apt-get install -y --no-install-recommends \
    git zip unzip curl gnupg libpq-dev libicu-dev libpng-dev libjpeg-dev libwebp-dev zlib1g-dev \
    # php extensions install commands...
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install intl \
    && docker-php-ext-install exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Node.js and npm...
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs

# Install Composer globally...
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Apache Configuration ---
# Enable Apache rewrite module
RUN a2enmod rewrite

# Remove the old 'echo' command that created the config file:
# RUN echo '<VirtualHost ... ' > /etc/apache2/sites-available/000-default.conf

# COPY the new config file instead:
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
# --- End Apache Configuration ---


# ... (previous Dockerfile lines: FROM, WORKDIR, apt-get, nodejs, composer install, apache config) ...

# --- Build Frontend Assets ---
    COPY package.json package-lock.json* ./
    RUN npm install
    # --- End Frontend Build ---
    
    # --- Application Code ---
    COPY . /var/www/html
    # --- End Application Code ---
    
    # --- Run Tailwind Build ---
    # Make sure this runs AFTER copying all code including view files
    RUN npm run build
    # --- End Tailwind Build ---
    
    # --- Composer Install ---
    RUN composer install --no-interaction --no-dev --optimize-autoloader
    # --- End Composer Install ---
    
    # --- Run Migrations ---
    # Attempt to run migrations during the build process
    # This requires DATABASE_* env vars to be available during build
    RUN php spark migrate --all
    # --- End Migrations ---
    
    
    # --- Permissions ---
    # Create writable dirs and set permissions
    RUN mkdir -p /var/www/html/writable/cache \
                 /var/www/html/writable/logs \
                 /var/www/html/writable/session \
                 /var/www/html/writable/uploads \
        && chown -R www-data:www-data /var/www/html \
        && chmod -R 775 /var/www/html/writable
    
    # --- Expose Port & Start Command ---
    EXPOSE 80
    
    # Start command: Just start Apache (Migrations moved to RUN step above)
    CMD ["apache2-foreground"]