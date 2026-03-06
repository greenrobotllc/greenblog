FROM php:7.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libsqlite3-dev \
    zip \
    unzip \
    && docker-php-ext-install zip pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www

# Configure Apache to use public_html as document root
RUN sed -i 's/\/var\/www\/html/\/var\/www\/public_html/g' /etc/apache2/sites-available/000-default.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Create and set permissions on writable directories
RUN mkdir -p /var/www/data /var/www/public_html/static /var/www/public_html/uploads
RUN chown -R www-data:www-data /var/www/data /var/www/public_html/static /var/www/public_html/uploads
RUN chmod -R 700 /var/www/data && chmod -R 755 /var/www/public_html/static /var/www/public_html/uploads

# Install dependencies
RUN cd /var/www && composer install

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
