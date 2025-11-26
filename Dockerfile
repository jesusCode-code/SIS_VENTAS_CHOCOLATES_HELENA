# Imagen base con PHP 8.2 y Apache
FROM php:8.2-apache

# Instalar dependencias necesarias y herramientas de compilación
RUN apt-get update && apt-get install -y \
    gnupg \
    unixodbc-dev \
    curl \
    $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# Configurar repositorio Microsoft ODBC (Método correcto para Debian 12)
# 1. Descargar y desencriptar la llave GPG en la ruta específica
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    # 2. Descargar la lista de fuentes
    && curl https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    # 3. Actualizar e instalar los drivers
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 \
    # 4. Instalar extensiones de PHP
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Copiar tu código al contenedor
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html/
