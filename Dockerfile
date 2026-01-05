# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Enable mysqli and pdo_mysql extensions (needed for your db.php)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your project into the web root
COPY . /var/www/html/

# Give Apache ownership
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
