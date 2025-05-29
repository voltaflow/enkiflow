#!/bin/bash

# Configuración de base de datos
DB_HOST="127.0.0.1"
DB_PORT="5432"
DB_USER="postgres"
DB_PASS="postgres"

echo "🔧 Configurando bases de datos PostgreSQL para EnkiFlow..."

# Crear base de datos central
echo "📦 Creando base de datos central..."
PGPASSWORD=$DB_PASS psql -h $DB_HOST -p $DB_PORT -U $DB_USER -c "CREATE DATABASE enkiflow_central;" 2>/dev/null || echo "La base de datos enkiflow_central ya existe o no se pudo crear"

echo "✅ Configuración de base de datos completada"
echo ""
echo "Ahora puedes ejecutar:"
echo "  php artisan migrate"
echo "  php artisan tenant:create-test"