# Use an official PHP image with Apache pre-installed (Using 8.2 as a stable choice)
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies + PHP extensions + Node.js/npm...
# Includes libraries for common extensions (gd, intl, pgsql) and tools (git, composer, node)
RUN apt-get update && apt-get install -y --no-install-recommends \
    # Common tools
    git \
    zip \
    unzip \
    curl \
    gnupg \
    # PHP Extension Dependencies
    libpq-dev \       # For PostgreSQL (pdo_pgsql, pgsql)
    libicu-dev \      # For Intl (intl)
    libpng-dev \      # For GD
    libjpeg-dev \     # For GD
    libwebp-dev \     # For GD
    zlib1g-dev \      # For GD (and other compression)
    # Install PHP Extensions needed for CodeIgniter + Postgres + GD
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install intl \
    && docker-php-ext-install exif \
    # Install Node.js (LTS version)
    && curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    # Clean up apt cache
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN a2enmod rewrite
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# --- Build Frontend Assets ---
# Copy package files first for Docker cache optimization
COPY package.json package-lock.json* ./
# Install Node dependencies
RUN npm install
# --- End Frontend Build ---

# --- Application Code ---
# Copy ALL application code (ensure .dockerignore excludes node_modules, vendor, writable, .env etc.)
COPY . /var/www/html
# --- End Application Code ---

# --- Run Tailwind Build ---
# Needs to run AFTER code is copied
# Replace "npm run build" with your actual build script name if different
RUN npm run build
# --- End Tailwind Build ---

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

# --- Expose Port & Start Command ---
EXPOSE 80

# Start command: Just start Apache
CMD ["apache2-foreground"]