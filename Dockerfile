# Étape 1 : Image de base
FROM php:8.2-apache

# Étape 2 : Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    default-libmysqlclient-dev \
    && docker-php-ext-install pdo_mysql zip gd

# Étape 3 : Activer le module rewrite d'Apache
RUN a2enmod rewrite

# Étape 4 : Créer explicitement le répertoire /var/www/html
RUN mkdir -p /var/www/html

# Étape 5 : Copier les fichiers du projet
COPY . /var/www/html/

# Créer explicitement le répertoire var/cache et var/log
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Étape 6 : Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var \
    && chmod -R 775 /var/www/html/var/cache /var/www/html/var/log

# Étape 7 : Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Étape 8 : Installer les dépendances sans exécuter les scripts
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-scripts
USER root

# Étape 9 : Nettoyer le cache Symfony en mode production
RUN rm -rf /var/www/html/var/cache/* && su -s /bin/sh -c "php /var/www/html/bin/console cache:clear --env=prod --no-debug" www-data

# Étape 10 : Copier une configuration personnalisée pour Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Étape 11 : Exposer le port
EXPOSE 80

# Étape 12 : Démarrer Apache
CMD ["apache2-foreground"]
