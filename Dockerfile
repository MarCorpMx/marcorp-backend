FROM php:8.4.16-apache

# Instalar dependencias necesarias para Laravel
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Habilitar mod_rewrite (CRÍTICO para Laravel)
RUN a2enmod rewrite

# Copiar configuración personalizada de Apache
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar el proyecto al contenedor
COPY . /var/www/html

# Permisos correctos para Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
