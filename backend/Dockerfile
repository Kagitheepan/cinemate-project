# backend/Dockerfile
FROM php:8.4-apache

# 1. Installation des outils de base indispensables (git, unzip, openssl)
RUN apt-get update && apt-get install -y git unzip openssl

# Configuration d'Apache pour Render et Symfony
RUN a2enmod rewrite
# Render fournit la variable d'environnement PORT (ou 8000 par défaut)
ENV PORT=8000
RUN sed -i "s/Listen 80/Listen \${PORT}/g" /etc/apache2/ports.conf
RUN sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:\${PORT}>/g" /etc/apache2/sites-available/000-default.conf
# On pointe le DocumentRoot vers le dossier public de Symfony
RUN sed -i "s!/var/www/html!/app/public!g" /etc/apache2/sites-available/000-default.conf
# Autoriser les règles de réécriture (AllowOverride)
RUN echo "<Directory /app/public>\n    AllowOverride All\n    Require all granted\n</Directory>" >> /etc/apache2/apache2.conf

# 2. Installation ultra-optimisée des extensions PHP (pdo_mysql, zip, intl, mongodb)
# On utilise le script officiel recommandé par la communauté pour accélérer le build et réduire l'usage mémoire.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql zip intl mongodb sodium

# Activer la compression GZIP globale pour PHP et OPcache pour le serveur
RUN echo "zlib.output_compression = On" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "zlib.output_compression_level = 6" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.enable = 1" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.memory_consumption = 256" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.max_accelerated_files = 20000" >> /usr/local/etc/php/conf.d/optimizations.ini

# 3. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copie de tous les fichiers du projet (backend) dans le conteneur
COPY . /app

# S'assure que le fichier .env existe (même vide) pour éviter l'erreur Dotenv\Exception\PathException
RUN touch .env

# 4.  Expose le port 8000 (Sera écrasé par la variable d'environnement PORT de Render)
EXPOSE 8000

# 5. Commande de démarrage : Installation sans scripts + Génération JWT + Cache Clear + Migrations + Lancement serveur
CMD bash -c "composer install --no-interaction --optimize-autoloader --no-scripts && \
             mkdir -p config/jwt && \
             openssl genpkey -out config/jwt/private.pem -pass env:JWT_PASSPHRASE -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 && \
             openssl pkey -in config/jwt/private.pem -passin env:JWT_PASSPHRASE -pubout -out config/jwt/public.pem && \
             php bin/console cache:clear && \
             php bin/console doctrine:migrations:migrate --no-interaction && \
             echo 'Starting Apache Server...' && \
             apache2-foreground"