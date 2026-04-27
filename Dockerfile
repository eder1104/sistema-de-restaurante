FROM php:8.2-cli

# Instalar dependencias del sistema y Node.js
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    git \
    unzip \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de PHP y Node
RUN composer install --optimize-autoloader --no-dev
RUN npm install
RUN npm run build

# Preparar base de datos SQLite
RUN touch database/database.sqlite
RUN php artisan migrate --force

# Dar permisos de ejecución al script de inicio
RUN chmod +x render-start.sh

# El puerto lo asigna Render automáticamente
EXPOSE $PORT

# Comando para iniciar todo
CMD ["./render-start.sh"]
