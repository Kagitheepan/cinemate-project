# backend/Dockerfile
FROM php:8.4-apache

# 1. Installation des outils de base indispensables
RUN apt-get update && apt-get install -y git unzip openssl

# Configuration d'Apache pour Render et Symfony
RUN a2enmod rewrite
ENV PORT=8000
RUN sed -i "s/Listen 80/Listen \${PORT}/g" /etc/apache2/ports.conf
RUN sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:\${PORT}>/g" /etc/apache2/sites-available/000-default.conf
RUN sed -i "s!/var/www/html!/app/public!g" /etc/apache2/sites-available/000-default.conf
RUN echo "<Directory /app/public>\n    AllowOverride All\n    Require all granted\n    FallbackResource /index.php\n    SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=\$1\n</Directory>" >> /etc/apache2/apache2.conf

# 2. Installation ultra-optimisée des extensions PHP
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql zip intl mongodb sodium

# Activer la compression GZIP globale pour PHP et OPcache
RUN echo "zlib.output_compression = On" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "zlib.output_compression_level = 6" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.enable = 1" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.memory_consumption = 256" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.max_accelerated_files = 20000" >> /usr/local/etc/php/conf.d/optimizations.ini

# 3. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copie de tous les fichiers du projet
COPY . /app

# On installe les dépendances PENDANT la création de l'image (beaucoup plus rapide au démarrage !)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader --no-scripts --no-dev

RUN touch .env

# 5. Script de démarrage : JWT + Migrations + Lancement d'Apache
CMD bash -c "php bin/console lexik:jwt:generate-keypair --skip-if-exists && \
             php bin/console cache:clear && \
             php bin/console doctrine:migrations:migrate --no-interaction && \
             echo 'Starting Apache Server...' && \
             apache2-foreground"