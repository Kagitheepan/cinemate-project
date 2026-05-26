# backend/Dockerfile
FROM php:8.4-cli
# 1. Installation des outils de base indispensables (git, unzip, openssl)
RUN apt-get update && apt-get install -y git unzip openssl

# 2. Installation ultra-optimisée des extensions PHP (pdo_mysql, zip, intl, mongodb)
# On utilise le script officiel recommandé par la communauté pour accélérer le build et réduire l'usage mémoire.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo pdo_mysql zip intl mongodb

# Activer la compression GZIP globale pour PHP et OPcache pour le serveur CLI
RUN echo "zlib.output_compression = On" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "zlib.output_compression_level = 6" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.enable_cli = 1" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.memory_consumption = 256" >> /usr/local/etc/php/conf.d/optimizations.ini && \
    echo "opcache.max_accelerated_files = 20000" >> /usr/local/etc/php/conf.d/optimizations.ini

# 3. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copie de tous les fichiers du projet (backend) dans le conteneur
COPY . /app

# 4.  Expose le port 8000 (Port par défaut du serveur Symfony CLI)
EXPOSE 8000

# 5. Commande de démarrage : Installation sans scripts + Génération JWT via OpenSSL + Cache Clear + Migrations + Lancement serveur
CMD bash -c "composer install --no-interaction --optimize-autoloader --no-scripts && \
             mkdir -p config/jwt && \
             openssl genpkey -out config/jwt/private.pem -pass pass:${JWT_PASSPHRASE} -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 && \
             openssl pkey -in config/jwt/private.pem -passin pass:${JWT_PASSPHRASE} -pubout -out config/jwt/public.pem && \
             php bin/console cache:clear && \
             php bin/console doctrine:migrations:migrate --no-interaction && \
             echo 'Starting PHP Server...' && \
             php -S 0.0.0.0:8000 -t public"