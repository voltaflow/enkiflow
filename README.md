# Laravel Project Setup for macOS

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Development Environment Options](#development-environment-options)
   - [Option A: Laravel Herd (Native macOS)](#option-a-laravel-herd-native-macos)
   - [Option B: Laravel Sail (Docker)](#option-b-laravel-sail-docker)
3. [First-Run Tasks](#first-run-tasks)
4. [Useful Scripts & Troubleshooting](#useful-scripts--troubleshooting)

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
