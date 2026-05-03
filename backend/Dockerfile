# backend/Dockerfile
FROM php:8.4-cli
# 1. Installation des dépendances système (git, zip, lib pour mongo/mysql)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl

# 2. Installation de l'extension MongoDB 
RUN pecl install mongodb && docker-php-ext-enable mongodb

# 3. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 4.  Expose le port 8000 (Port par défaut du serveur Symfony CLI)
EXPOSE 8000

# 5. Commande de démarrage : Installation des dépendances + Lancement serveur
CMD bash -c "composer install --no-interaction && echo 'Starting PHP Server...' && php -S 0.0.0.0:8000 -t public || echo 'Server failed'; tail -f /dev/null"