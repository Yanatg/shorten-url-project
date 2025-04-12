# Use an official PHP image with Apache pre-installed (Using 8.2 as a stable choice)
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies + PHP extensions for CI4, Postgres, GD, and Node.js/npm
# Add curl and gnupg for NodeSource setup
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    zip \
    unzip \
    curl \
    gnupg \
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
    # Clean up apt lists
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Node.js (e.g., LTS version - check NodeSource for current LTS) and npm
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# --- Apache Configuration ---
# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache virtual host to point to the public directory and allow .htaccess
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# --- Build Frontend Assets ---
# Copy package files first for Docker cache optimization
COPY package.json package-lock.json* ./
# Install Node dependencies
RUN npm install
# --- End Frontend Build ---

# --- Application Code ---
# Copy the rest of the application code (ensure .dockerignore excludes node_modules, vendor, writable, .env etc.)
COPY . /var/www/html

# Run Tailwind build AFTER code is copied (so it can scan .php files)
# Replace "npm run build" if your script is named differently
RUN npm run build

# --- Composer Install ---
# Install PHP dependencies AFTER copying code
RUN composer install --no-interaction --no-dev --optimize-autoloader

# --- Permissions ---
# Create writable dirs (if they weren't copied/exist) and set permissions
RUN mkdir -p /var/www/html/writable/cache \
             /var/www/html/writable/logs \
             /var/www/html/writable/session \
             /var/www/html/writable/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/writable

# --- Expose Port & Start Command ---
EXPOSE 80

# Start command: Run migrations then start Apache
CMD php spark migrate --all && apache2-foreground