# Docker Guide

The official image is published on **Docker Hub** and can be pulled with:

```bash
docker pull c0305/enkiflow:latest
```

## Prerequisites
- Docker 25 or newer
- `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN` for pushing images to Docker Hub
- PHP 8.3 CLI and Node LTS installed

## Local Development with Octane
1. `composer install`
2. `npm ci`
3. `php artisan octane:start --watch`

## Docker Development
```yaml
dev-app:
  build: .
  volumes:
    - .:/var/www/html
  ports:
    - "8000:8000"
```
Using SQLite for quick tests:
```bash
docker run -e DB_CONNECTION=sqlite -e APP_KEY=base64:YOURKEY -p 8000:8000 c0305/enkiflow:latest
```

## Understanding Tenancy
- Central DB via `DB_*` variables
- Tenants use databases resolved by `stancl/tenancy`
- Run central and tenant migrations separately.
  For subdomain testing locally, edit `/etc/hosts` to map tenant domains
  (e.g. `tenant.localhost`).

## Deploying to Kubernetes
Use the image from Docker Hub in a Deployment:
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: enkiflow
spec:
  replicas: 2
  selector:
    matchLabels:
      app: enkiflow
  template:
    metadata:
      labels:
        app: enkiflow
    spec:
      containers:
        - name: app
          image: c0305/enkiflow:latest
          env:
            - name: APP_KEY
              value: your-key
            - name: DB_CONNECTION
              value: pgsql
          ports:
            - containerPort: 8000
```
Configure `TENANCY_DOMAIN_BASE` to match the wildcard domain set in your Ingress.

## CI Flow
GitHub Actions builds multi-arch images on every push and publishes them to **Docker Hub**.
`main` publishes the `latest` tag, while pull requests receive a
`pr-<number>` tag for preview. After pushing, the workflow runs
`php artisan scout:index --pretend` inside the new container to ensure
Laravel Scout initializes correctly. Each release includes this document
as an asset for quick reference.

## Production Run
Pull the image from Docker Hub and run behind Nginx or Traefik.
Use Octane signals to reload without downtime.

## FAQ
- Ensure `APP_KEY` is set in production.
- Adjust file permissions for `storage` and `bootstrap/cache` if needed.
- Monitor Swoole for memory leaks.
- Tweak `--max-requests` and DB pool size based on load.
- SSL termination should happen at the proxy layer.
