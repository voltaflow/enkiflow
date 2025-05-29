# Laravel Cloud Deployment Guide for EnkiFlow

## 📋 Overview

This guide provides step-by-step instructions for deploying EnkiFlow to Laravel Cloud, including multi-tenant database configuration, auto-scaling setup, and optimization strategies.

## 🚀 Pre-Deployment Checklist

### 1. Laravel Octane Optimization
```bash
# Already configured in composer.json, verify installation
php artisan octane:install --server=swoole

# Test Octane locally
php artisan octane:start --host=0.0.0.0 --port=8000
```

### 2. Environment Configuration
```bash
# Copy and modify for production
cp .env.example .env.cloud

# Key variables for Laravel Cloud
APP_ENV=production
APP_DEBUG=false
OCTANE_SERVER=swoole
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database
```

### 3. Database Optimization for PostgreSQL
```bash
# Run database optimization commands
php artisan db:show
php artisan migrate:status
php artisan tenants:migrate-status
```

## 🌍 Laravel Cloud Setup

### Step 1: Account and Project Setup

1. **Create Account**
   - Visit [cloud.laravel.com](https://cloud.laravel.com)
   - Sign up with GitHub/GitLab integration
   - Add payment method

2. **Create Application**
   ```yaml
   Application Settings:
     Name: EnkiFlow
     Repository: github.com/yourusername/enkiflow
     Primary Branch: main
     PHP Version: 8.3
     Framework: Laravel 12
   ```

### Step 2: Environment Configuration

#### Development Environment
```yaml
Environment: development
Auto-Deploy: true
Branch: develop
Region: us-east-1
Compute: Small (1 vCPU, 1GB RAM)
```

#### Staging Environment  
```yaml
Environment: staging
Auto-Deploy: true
Branch: staging
Region: us-east-1
Compute: Medium (2 vCPU, 2GB RAM)
Workers: 2 (default queue)
```

#### Production Environment
```yaml
Environment: production
Auto-Deploy: false (manual)
Branch: main
Region: us-east-1, eu-west-1
Compute: Large (4 vCPU, 4GB RAM)
Auto-Scaling: Enabled
Workers: 4 (default), 2 (high-priority)
```

### Step 3: Database Configuration

#### Central Database (Users, Tenants, Subscriptions)
```yaml
Database Name: enkiflow_central
Type: PostgreSQL 15
Region: us-east-1
Compute Units: 2
Storage: 100GB
Backup: Daily
Hibernation: Disabled (always active)
```

#### Tenant Databases (Projects, Tasks, Time Entries)
```yaml
Database Strategy: Dynamic Creation
Naming Pattern: enkiflow_tenant_{tenant_id}
Type: PostgreSQL 15
Region: Auto (based on tenant location)
Compute Units: 1 (auto-scale to 4)
Storage: 20GB (auto-scale to 500GB)
Hibernation: 30 minutes inactivity
Backup: Daily
```

### Step 4: Environment Variables

```bash
# Core Application
APP_NAME="EnkiFlow"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://app.enkiflow.com

# Database (Central)
DB_CONNECTION=pgsql
DB_HOST=${LARAVEL_CLOUD_DB_HOST}
DB_PORT=5432
DB_DATABASE=enkiflow_central
DB_USERNAME=${LARAVEL_CLOUD_DB_USERNAME}
DB_PASSWORD=${LARAVEL_CLOUD_DB_PASSWORD}

# Cache & Sessions  
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=${LARAVEL_CLOUD_REDIS_HOST}
REDIS_PASSWORD=${LARAVEL_CLOUD_REDIS_PASSWORD}
REDIS_PORT=6379

# Queue System
QUEUE_CONNECTION=database
HORIZON_BALANCE=auto
HORIZON_PROCESSES=4

# Performance
OCTANE_SERVER=swoole
OCTANE_WORKERS=auto
OCTANE_TASK_WORKERS=6
OCTANE_MAX_REQUESTS=1000

# Multi-Tenancy
TENANCY_CENTRAL_DOMAINS=enkiflow.com,www.enkiflow.com,app.enkiflow.com
TENANCY_DOMAIN_SUFFIX=.enkiflow.com

# Stripe
STRIPE_KEY=${STRIPE_PUBLISHABLE_KEY}
STRIPE_SECRET=${STRIPE_SECRET_KEY}
STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET}
CASHIER_CURRENCY=usd

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=${POSTMARK_TOKEN}
MAIL_PASSWORD=${POSTMARK_TOKEN}

# Monitoring & Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
SENTRY_LARAVEL_DSN=${SENTRY_DSN}

# AI Features (Future)
OPENAI_API_KEY=${OPENAI_API_KEY}
OPENAI_ORGANIZATION=${OPENAI_ORG_ID}
```

## ⚙️ Auto-Scaling Configuration

### Compute Auto-Scaling
```yaml
Triggers:
  CPU Usage: > 70% for 2 minutes
  Memory Usage: > 80% for 2 minutes
  Request Queue: > 50 pending requests

Scaling Rules:
  Min Instances: 1
  Max Instances: 10
  Scale Up: +1 instance
  Scale Down: -1 instance (after 5 minutes low usage)
  Cool Down: 3 minutes between scaling events
```

### Database Auto-Scaling
```yaml
Central Database:
  Auto-Scale: Disabled (predictable load)
  Monitoring: Enabled

Tenant Databases:
  Auto-Scale: Enabled
  CPU Trigger: > 75% for 5 minutes
  Storage Trigger: > 80% used
  Scale Up: +1 compute unit, +50GB storage
  Scale Down: -1 compute unit (after 30 minutes)
  Hibernation: After 30 minutes inactivity
```

### Queue Worker Auto-Scaling
```yaml
Default Queue:
  Min Workers: 2
  Max Workers: 8
  Jobs Per Worker: 10
  Scale Up Trigger: > 20 jobs waiting
  Scale Down Trigger: < 5 jobs waiting

High Priority Queue:
  Min Workers: 1
  Max Workers: 4
  Jobs Per Worker: 5
  Scale Up Trigger: > 5 jobs waiting
  Scale Down Trigger: < 2 jobs waiting
```

## 🔄 Deployment Pipeline

### Build Commands
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Laravel optimizations
php artisan config:cache
php artisan route:cache  
php artisan view:cache
php artisan event:cache

# Generate application key (if not set)
php artisan key:generate --force
```

### Deploy Commands
```bash
# Run migrations (central)
php artisan migrate --force

# Run tenant migrations
php artisan tenants:migrate --force

# Restart services
php artisan octane:restart
php artisan horizon:restart

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### Health Checks
```yaml
HTTP Health Check:
  URL: /health
  Expected: 200 OK
  Timeout: 10 seconds
  Interval: 30 seconds

Database Health Check:
  Query: SELECT 1
  Timeout: 5 seconds
  Interval: 60 seconds

Queue Health Check:
  Check: horizon:status
  Expected: running
  Interval: 60 seconds
```

## 📊 Monitoring & Alerts

### Key Metrics to Monitor
```yaml
Performance:
  - Response Time (< 500ms average)
  - Throughput (requests/minute)
  - Error Rate (< 1%)
  - Queue Processing Time (< 30s average)

Resource Usage:
  - CPU Usage (< 80% average)
  - Memory Usage (< 85% average)  
  - Database Connections (< 80% of pool)
  - Disk Usage (< 85% per database)

Business Metrics:
  - Active Tenants
  - New User Registrations
  - Subscription Events
  - Time Tracking Sessions
```

### Alert Configuration
```yaml
Critical Alerts (PagerDuty):
  - Application Down (> 5 minutes)
  - Database Connection Failed
  - Payment Processing Errors
  - Queue Backlog > 1000 jobs

Warning Alerts (Slack):
  - Response Time > 1 second
  - CPU Usage > 80%
  - Memory Usage > 85%
  - Failed Job Rate > 5%

Info Alerts (Email):
  - New Tenant Registration
  - Subscription Changes
  - Weekly Performance Report
```

## 🚀 Go-Live Checklist

### Pre-Launch (1 week before)
- [ ] Complete staging environment testing
- [ ] Load testing with realistic traffic
- [ ] Security audit and penetration testing
- [ ] Backup and recovery procedures tested
- [ ] Monitoring and alerting configured
- [ ] SSL certificates configured
- [ ] CDN setup for static assets

### Launch Day
- [ ] Final code deployment to production
- [ ] DNS migration to Laravel Cloud
- [ ] SSL certificate activation
- [ ] Health checks passing
- [ ] Monitoring dashboards active
- [ ] Support team briefed
- [ ] Rollback plan ready

### Post-Launch (first week)
- [ ] Monitor performance metrics
- [ ] Verify auto-scaling functionality  
- [ ] Check database hibernation/wake cycles
- [ ] Validate backup procedures
- [ ] Gather user feedback
- [ ] Performance optimization based on real usage

## 💰 Cost Optimization

### Expected Monthly Costs (Production)
```yaml
Base Infrastructure:
  Compute: $200-500/month (auto-scaling)
  Central Database: $100/month (always on)
  Tenant Databases: $50-300/month (hibernation savings)
  Redis Cache: $50/month
  Load Balancer: $25/month
  SSL Certificates: $0 (included)

Traffic-Based:
  Data Transfer: $20-100/month
  API Calls: $10-50/month
  Background Jobs: $30-150/month

Total Estimated: $485-1,275/month
Traditional Server Cost: $1,200-3,000/month
Savings: 40-60% with Laravel Cloud
```

### Cost Optimization Strategies
- Aggressive database hibernation for inactive tenants
- Smart caching to reduce database queries
- Efficient queue processing to minimize worker hours
- Regional deployment to reduce data transfer costs
- Monitoring to prevent resource waste

## 🔧 Troubleshooting

### Common Issues

#### Octane Memory Leaks
```bash
# Monitor memory usage
php artisan octane:status

# Restart workers if memory issues
php artisan octane:restart

# Adjust max requests per worker
OCTANE_MAX_REQUESTS=500
```

#### Database Connection Limits
```bash
# Check connection count
SELECT count(*) FROM pg_stat_activity;

# Optimize connection pooling
DB_POOL_SIZE=20
DB_POOL_TIMEOUT=30
```

#### Queue Processing Issues
```bash
# Check queue status
php artisan horizon:status

# Clear failed jobs
php artisan queue:clear

# Restart horizon
php artisan horizon:restart
```

#### Tenant Database Issues
```bash
# Check tenant connectivity
php artisan tenants:list

# Test tenant database
php artisan tenants:run --tenant=demo "db:show"

# Repair tenant migrations
php artisan tenants:migrate-status
```

## 📞 Support Contacts

- **Laravel Cloud Support**: support@laravel.com
- **Emergency Escalation**: See Laravel Cloud dashboard
- **Documentation**: [cloud.laravel.com/docs](https://cloud.laravel.com/docs)
- **Community**: Laravel Discord #cloud channel

---

**Last Updated:** May 29, 2025  
**Next Review:** After production deployment
