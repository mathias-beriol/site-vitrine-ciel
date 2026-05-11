FROM php:8.1-apache

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite
RUN a2enmod rewrite

# Configuration Apache
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Copier les fichiers
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
