# Utiliser PHP 8.2 avec FPM et extensions nécessaires
FROM php:8.2-fpm-alpine

# Installer les dépendances système
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nginx \
    supervisor \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath opcache

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer l'utilisateur www-data et les répertoires nécessaires
RUN addgroup -g 1000 -S www-data || true && \
    adduser -u 1000 -S www-data -G www-data || true && \
    mkdir -p /var/log/supervisor /var/cache/nginx /run/nginx && \
    chown -R www-data:www-data /var/log/supervisor /var/cache/nginx /run/nginx || true

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de configuration
COPY composer.json composer.lock package.json package-lock.json ./

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copier le reste du code
COPY . .

# Permissions pour Laravel
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Copier les configurations
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh

# Rendre le script exécutable
RUN chmod +x /usr/local/bin/start.sh

# Exposer le port 80
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Démarrer avec supervisor
CMD ["/usr/local/bin/start.sh"]