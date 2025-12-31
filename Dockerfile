# Dockerfile para Symfony API - Espejo exacto de Laravel setup
FROM php:8.2-cli

# Argumentos de build
ARG INSTALL_XDEBUG=true
ARG APP_ENV=local

# Instalar extensiones y dependencias del sistema (igual que Laravel)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    zip \
    iputils-ping \
    netcat-openbsd \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    librabbitmq-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath \
        pcntl \
        sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Instalar AMQP para RabbitMQ
RUN pecl install amqp \
    && docker-php-ext-enable amqp

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar Xdebug (condicional)
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
        pecl install xdebug && docker-php-ext-enable xdebug; \
    fi

# Git safe directory
RUN git config --system --add safe.directory /var/www/html

# Configuración de Xdebug
COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar proyecto completo
COPY . /var/www/html

# Instalar dependencias de Composer
RUN if [ "$APP_ENV" = "production" ]; then \
        echo "🏭 PRODUCCIÓN: Instalando dependencias optimizadas"; \
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; \
    else \
        echo "💻 DESARROLLO: Instalando todas las dependencias"; \
        composer install --no-interaction --prefer-dist --optimize-autoloader; \
    fi

# Permisos correctos para directorios de Symfony
RUN chown -R www-data:www-data /var/www/html/var \
    && chmod -R 775 /var/www/html/var

# Limpiar cache en producción
RUN if [ "$APP_ENV" = "production" ]; then \
        php bin/console cache:clear --env=prod --no-debug || true; \
    fi

# Entrypoint
COPY ./docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# En PRODUCCIÓN ejecutar como www-data por seguridad
# En DESARROLLO el usuario se configura en docker-compose.yml con user: "${UID}:${GID}"
RUN if [ "$APP_ENV" = "production" ]; then \
        echo "🔒 Ejecutando como www-data en producción"; \
    else \
        echo "👤 Ejecutando como usuario ${UID:-1000}:${GID:-1000} en desarrollo"; \
    fi

# Nota: USER se omite aquí porque en desarrollo se configura en docker-compose.yml
# En producción, el entrypoint.sh se encargará de hacer su-exec si es necesario

ENTRYPOINT ["/entrypoint.sh"]
