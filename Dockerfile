FROM php:8.1-apache
 
# Activer mod_rewrite
RUN a2enmod rewrite
 
# Installer l'extension MySQL pour PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql
 
# Copier les fichiers du site
COPY . /var/www/html/
 
# Permissions
RUN chown -R www-data:www-data /var/www/html/
 
# Port 80
EXPOSE 80
 
