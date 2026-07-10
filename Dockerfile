FROM php:8.2-apache

# 1. Establecer el puerto por defecto (Cloud Run lo sobrescribirá automáticamente)
ENV PORT=8000
EXPOSE 8000

# 2. Instalar dependencias del sistema y la extensión PDO MySQL para tu base de datos
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo_mysql

# 3. Habilitar mod_rewrite de Apache (esencial para las rutas de Laravel)
RUN a2enmod rewrite

# 4. Cambiar la raíz de Apache a la carpeta /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 5. Configurar Apache para que use el puerto dinámico de Cloud Run ($PORT)
RUN sed -s -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf
RUN sed -s -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/*.conf

# 6. Copiar los archivos del proyecto al contenedor
WORKDIR /var/www/html
COPY . .

# 7. Descargar Composer e instalar las dependencias de producción de Laravel
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 8. Asignar los permisos correctos a las carpetas de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Iniciar Apache
CMD ["apache2-foreground"]
