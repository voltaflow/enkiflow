# Configuración de Mail para Laravel Herd Pro

## Desarrollo Local con Laravel Herd Pro

Laravel Herd Pro incluye Mailpit para capturar correos en desarrollo. Para configurarlo:

### 1. Configuración para Mailpit (Recomendado)

En tu archivo `.env`, usa:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@enkiflow.test"
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Alternativa: Log Driver (Sin servidor de correo)

Si prefieres ver los correos en los logs:

```env
MAIL_MAILER=log
```

Los correos se guardarán en: `storage/logs/laravel.log`

### 3. Verificar que el Queue Worker esté ejecutándose

Las invitaciones usan colas para enviar correos. Ejecuta:

```bash
php artisan queue:work
```

O para desarrollo, puedes usar sync:

```env
QUEUE_CONNECTION=sync
```

### 4. Acceder a Mailpit

- URL: http://localhost:8025
- Aquí podrás ver todos los correos enviados por tu aplicación

## Solución de Problemas

Si los correos no se envían:

1. Verifica que Mailpit esté corriendo en Herd Pro
2. Revisa los logs: `tail -f storage/logs/laravel.log`
3. Asegúrate de que el queue worker esté corriendo
4. Verifica que no haya errores 403 en la consola del navegador

## Configuración de Producción

Para producción, configura un servicio SMTP real como:
- SendGrid
- Mailgun
- Amazon SES
- Resend

Ejemplo con Resend:

```env
MAIL_MAILER=resend
RESEND_KEY=tu-api-key-aqui
```