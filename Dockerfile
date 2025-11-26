# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    gnupg \
    unixodbc-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Agregar repositorio Microsoft ODBC
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Copiar tu código al contenedor
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html/