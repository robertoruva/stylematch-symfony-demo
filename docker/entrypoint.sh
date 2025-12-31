#!/bin/sh
set -e

echo "🚀 Iniciando Symfony API (${APP_ENV:-local})..."

# ✅ Si te pasan un comando (como "test -f ..."), ejecútalo y sal
if [ "$#" -gt 0 ]; then
  exec "$@"
fi

# Verificar que Symfony existe
if [ ! -f /var/www/html/bin/console ]; then
  echo "❌ Symfony no encontrado en /var/www/html"
  exit 1
fi

# ========================================
# DESARROLLO: Reinstalar si vendor no existe
# (porque en dev se monta volumen sin vendor)
# ========================================
if [ "$APP_ENV" != "production" ]; then
  if [ ! -d /var/www/html/vendor ] || [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "📦 [DEV] Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "✅ [DEV] Dependencias instaladas"
  else
    echo "✅ [DEV] Dependencias ya instaladas"
  fi
  
  # Limpiar cache en desarrollo
  echo "🧹 [DEV] Limpiando cache..."
  php bin/console cache:clear --no-interaction 2>/dev/null || true
fi

# ========================================
# PRODUCCIÓN: Verificar que vendor existe
# ========================================
if [ "$APP_ENV" = "production" ]; then
  if [ ! -d /var/www/html/vendor ]; then
    echo "❌ [PROD] vendor/ no encontrado - imagen mal construida"
    exit 1
  fi
  echo "✅ [PROD] Dependencias verificadas"
  
  # Warming up cache en producción
  echo "🔥 [PROD] Warming up cache..."
  php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true
fi

# ========================================
# HEALTHCHECKS (solo en dev)
# ========================================
if [ "$APP_ENV" != "production" ]; then
  echo "⏳ Esperando servicios..."
  
  # PostgreSQL
  COUNTER=0
  until nc -z ${DB_HOST:-postgres} 5432 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -gt 30 ]; then
      echo "❌ Timeout esperando PostgreSQL"
      exit 1
    fi
    sleep 1
  done
  echo "✅ PostgreSQL listo"

  # Redis
  COUNTER=0
  until nc -z ${REDIS_HOST:-redis} 6379 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -gt 30 ]; then
      echo "❌ Timeout esperando Redis"
      exit 1
    fi
    sleep 1
  done
  echo "✅ Redis listo"

  # RabbitMQ
  COUNTER=0
  until nc -z ${RABBITMQ_HOST:-auth_rabbitmq} 5672 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -gt 30 ]; then
      echo "❌ Timeout esperando RabbitMQ"
      exit 1
    fi
    sleep 1
  done
  echo "✅ RabbitMQ listo"
fi

echo "✅ Symfony listo - Iniciando servidor en 0.0.0.0:8000"

# Inicia servidor PHP embebido
exec php -S 0.0.0.0:8000 -t public
