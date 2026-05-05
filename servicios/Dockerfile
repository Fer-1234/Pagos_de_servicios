FROM php:8.2-apache

# Instalar extensiones de MySQL/mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite para URLs amigables
RUN a2enmod rewrite

# Copiar todos los archivos del proyecto
COPY . /var/www/html/

# Crear carpeta uploads con permisos de escritura
RUN mkdir -p /var/www/html/uploads && chmod 777 /var/www/html/uploads

# Configurar Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Puerto expuesto
EXPOSE 80