# Laravel Project Setup for macOS

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Development Environment Options](#development-environment-options)
   - [Option A: Laravel Herd (Native macOS)](#option-a-laravel-herd-native-macos)
   - [Option B: Laravel Sail (Docker)](#option-b-laravel-sail-docker)
3. [First-Run Tasks](#first-run-tasks)
4. [Laravel Cloud Deployment](#laravel-cloud-deployment)
5. [Useful Scripts & Troubleshooting](#useful-scripts--troubleshooting)

## Prerequisites

- macOS 12.0 or higher
- Terminal/Command Line access
- Git (pre-installed on macOS)

## Development Environment Options

Choose **one** of the following methods to set up your development environment:

### Option A: Laravel Herd (Native macOS)

[Laravel Herd](https://herd.laravel.com) is a blazing fast, native development environment for macOS with zero configuration.

```bash
# 1. Download Herd from the official site
open https://herd.laravel.com/download

# 2. Install Herd by dragging to Applications folder when prompted

# 3. Launch Herd from Applications folder to complete onboarding process
# (Herd will request admin permissions for its background services)

# 4. Verify installation
herd --version
php --version
composer --version
laravel --version
node --version
```

Herd automatically configures:
- PHP (latest version)
- Nginx
- DnsMasq (for *.test domains)
- Node.js
- Composer

### Option B: Laravel Sail (Docker)

Laravel Sail provides a Docker-based development environment with additional services.

**Prerequisites for Sail:**
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running

```bash
# 1. Navigate to your project directory
cd /path/to/your/project

# 2. Copy example .env file and adjust values
cp .env.example .env

# 3. Set APP_URL in .env to match your Herd domain
# APP_URL=http://your-project.test

# 4. Configure database credentials in .env
# DB_HOST=mysql
# DB_USERNAME=sail
# DB_PASSWORD=password

# 5. Start Sail containers (first time will download required images)
./vendor/bin/sail up -d
```

### Common Sail Commands

```bash
# Stop all containers
./vendor/bin/sail down

# Start containers
./vendor/bin/sail up -d

# Run artisan commands
./vendor/bin/sail artisan migrate

# Execute shell in container
./vendor/bin/sail shell

# Configure a shell alias for convenience
echo "alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'" >> ~/.zshrc
source ~/.zshrc

# After configuring alias, you can use:
sail up -d
sail artisan migrate
```

## First-Run Tasks

```bash
# Install PHP dependencies
sail composer install

# Generate application key
sail artisan key:generate

# Install frontend dependencies (choose one)
sail npm install
# OR
sail pnpm install

# Run database migrations and seeders
sail artisan migrate --seed

# Build frontend assets
sail npm run build
```

## Laravel Cloud Deployment

EnkiFlow is optimized for deployment on **Laravel Cloud**, the next-generation serverless platform for Laravel applications.

### Why Laravel Cloud for EnkiFlow?

- **Auto-scaling**: Handles tenant load spikes automatically
- **Serverless Postgres**: Hibernates inactive tenant databases to save costs
- **Edge Computing**: Global deployment for reduced latency
- **Native Laravel Integration**: Perfect compatibility with Laravel Octane, Horizon, and Telescope
- **Cost Optimization**: Pay-per-use model ideal for multi-tenant SaaS

### Deployment Setup

```bash
# 1. Ensure Laravel Octane is configured (already included in composer.json)
php artisan octane:install --server=swoole

# 2. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Set up environment variables for Laravel Cloud
# These will be configured in the Laravel Cloud dashboard:
# DB_CONNECTION=pgsql
# CACHE_DRIVER=redis
# QUEUE_CONNECTION=database
# SESSION_DRIVER=redis
# OCTANE_SERVER=swoole
```

### Laravel Cloud Configuration

1. **Create Account**: Sign up at [cloud.laravel.com](https://cloud.laravel.com)
2. **Connect Repository**: Link your GitHub/GitLab repository
3. **Configure Environments**: 
   - Development (auto-deploy from `develop` branch)
   - Staging (auto-deploy from `staging` branch)  
   - Production (manual deploy from `main` branch)
4. **Provision Databases**:
   - Central database for users/tenants/subscriptions
   - Auto-scaling tenant databases
5. **Configure Queue Workers**:
   - Default workers for general processing
   - High-priority workers for critical tenant operations
   - AI processing workers for future ML features

### Multi-Tenant Database Strategy

```yaml
Central Database:
  Purpose: Users, tenants, subscriptions, domains
  Region: Primary (us-east-1)
  Scaling: Fixed size with monitoring

Tenant Databases:
  Purpose: Projects, tasks, time entries per tenant
  Region: Auto-selected based on tenant location
  Scaling: Auto-scale with hibernation for inactive tenants
  Backup: Automated daily backups
```

### Environment Variables for Laravel Cloud

```bash
# Core Application
APP_NAME="EnkiFlow"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.enkiflow.com

# Database (managed by Laravel Cloud)
DB_CONNECTION=pgsql
DB_HOST=auto-configured
DB_PORT=5432
DB_DATABASE=auto-configured
DB_USERNAME=auto-configured
DB_PASSWORD=auto-configured

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=auto-configured

# Queue System
QUEUE_CONNECTION=database
HORIZON_BALANCE=auto

# Performance
OCTANE_SERVER=swoole
OCTANE_WORKERS=auto

# Multi-tenancy
TENANCY_DOMAIN_SUFFIX=.enkiflow.com
TENANCY_CENTRAL_DOMAINS=enkiflow.com,www.enkiflow.com

# Stripe Integration
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=usd
```

### Deployment Pipeline

```yaml
Automated Deployment:
  1. Push to branch → Trigger build
  2. Run tests (PHPUnit + PHPStan)
  3. Build Docker image with Octane
  4. Deploy with zero downtime
  5. Run health checks
  6. Auto-rollback if health checks fail

Manual Commands (via dashboard):
  - php artisan migrate --force
  - php artisan db:seed --class=ProductionSeeder
  - php artisan tenants:migrate --force
  - php artisan horizon:restart
  - php artisan octane:restart
```

## Useful Scripts & Troubleshooting

### Useful Commands

```bash
# Clear all caches
sail artisan optimize:clear

# Create a new controller
sail artisan make:controller UserController

# Run tests
sail test

# Restart PHP-FPM with Herd
herd restart php

# Check available PHP versions in Herd
herd php:list

# Push the Docker image to Docker Hub
./scripts/push-image.sh $(git rev-parse --short HEAD)
```

### Common macOS Issues

1. **Port conflicts**
   ```bash
   # Edit docker-compose.yml to use different ports or stop conflicting services
   # Common conflicts: MySQL (3306), Redis (6379), Mailhog (1025, 8025)
   lsof -i :3306  # Check what's using port 3306
   ```

2. **Docker performance issues**
   - In Docker Desktop settings, reduce CPU/Memory allocation if your Mac is struggling
   - Consider using Herd's native services instead of Docker when possible

3. **DNS resolution problems**
   - Reset macOS DNS cache:
   ```bash
   sudo dscacheutil -flushcache; sudo killall -HUP mDNSResponder
   ```

4. **PHP extensions missing**
   - Install additional PHP extensions with Herd:
   ```bash
   herd php:config --edit  # Edit PHP config
   ```

5. **File permissions**
   - Fix storage directory permissions:
   ```bash
   sail artisan storage:link
   sail root-shell
   chown -R sail:sail storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

Happy coding!
