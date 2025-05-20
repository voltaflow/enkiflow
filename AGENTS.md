# AGENTS.md

## Purpose
Describe **exactly** how Codex (or cualquier runner CI) debe preparar, probar y analizar el
repositorio **Enkiflow (Laravel 12)**.  
Las instrucciones están pensadas para el setup script que instala:

- PHP 8.3 + pdo_sqlite y pgsql  
- Node 22 LTS + pnpm 9  
- PostgreSQL 16 (base `laravel_test`, user `laravel/secret`)

> Si cambias alguno de estos valores, actualiza este archivo.

---

## 1. Instalar dependencias

```bash
# PHP (usa composer.lock)
composer install --no-interaction --prefer-dist

# Node (usa pnpm-lock.yaml si existe)
pnpm install --frozen-lockfile || npm ci