# Use an official PHP image with Apache pre-installed
FROM php:8.2-apache

WORKDIR /var/www/html

# Install system dependencies + PHP extensions + Node.js/npm...
RUN apt-get update && apt-get install -y --no-install-recommends \
    # --- list of packages ---
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql pgsql intl exif \
    && curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer globally...
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache...
RUN a2enmod rewrite
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# --- Build Frontend Assets ---
COPY package.json package-lock.json* ./
RUN npm install
# --- End Frontend Build ---

# --- Application Code ---
# Copy ALL application code first
COPY . /var/www/html
# --- End Application Code ---

# --- Run Tailwind Build ---
# Needs to run AFTER code is copied
RUN npm run build
# --- End Tailwind Build ---

# !!! ===> START NEW ORDER <=== !!!

# --- Permissions & Writable Directory ---
# Create writable dirs and set permissions BEFORE composer and spark
RUN mkdir -p /var/www/html/writable/cache \
             /var/www/html/writable/logs \
             /var/www/html/writable/session \
             /var/www/html/writable/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/writable
# --- End Permissions ---

# --- Composer Install ---
# Now run composer install AFTER writable exists and has permissions
RUN composer install --no-interaction --no-dev --optimize-autoloader
# --- End Composer Install ---

# --- Run Migrations ---
# Now run migrations AFTER composer install and writable exists
RUN php spark migrate --all
# --- End Migrations ---

# !!! ===> END NEW ORDER <=== !!!

# --- Expose Port & Start Command ---
EXPOSE 80
CMD ["apache2-foreground"]