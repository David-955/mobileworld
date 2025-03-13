# Utiliser une image PHP avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires pour Symfony
RUN docker-php-ext-install pdo pdo_mysql mbstring

# Activer mod_rewrite pour Symfony
RUN a2enmod rewrite

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html/

# Définir les permissions correctes pour les dossiers
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 775 /var/www/html/var/

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les dépendances du projet
WORKDIR /var/www/html/
RUN composer install --no-dev --optimize-autoloader

# Nettoyer le cache Symfony en mode production
RUN php bin/console cache:clear --env=prod

# Exposer le port 80 pour Apache
EXPOSE 80