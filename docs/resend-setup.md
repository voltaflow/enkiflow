# Configuración de Resend para EnkiFlow

## ¿Por qué Resend?

Resend es una excelente opción para EnkiFlow porque:
- 🚀 API moderna y rápida
- 💰 Precio competitivo (3,000 emails gratis/mes)
- 📊 Excelente analytics y tracking
- 🔧 Soporte nativo en Laravel 12
- 🌍 Mejor deliverability que servicios tradicionales

## Configuración Paso a Paso

### 1. Crear cuenta en Resend
1. Ve a [resend.com](https://resend.com)
2. Crea una cuenta gratuita
3. Verifica tu dominio (enkiflow.com)

### 2. Obtener API Key
1. En el dashboard de Resend, ve a "API Keys"
2. Crea una nueva API key con permisos de envío
3. Copia la key (formato: `re_xxxxxxxxxxxxx`)

### 3. Configurar en Laravel Cloud

#### Variables de Entorno
```bash
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="hello@enkiflow.com"
MAIL_FROM_NAME="EnkiFlow"
RESEND_KEY="re_xxxxxxxxxxxxx"
```

#### Para desarrollo local (.env)
```bash
MAIL_MAILER=resend
MAIL_FROM_ADDRESS="test@enkiflow.test"
MAIL_FROM_NAME="EnkiFlow Dev"
RESEND_KEY="re_test_xxxxxxxxxxxxx"
```

### 4. Verificar Configuración

Ejecuta este comando para probar:
```bash
php artisan tinker
>>> Mail::raw('Test email from EnkiFlow', function ($message) {
...     $message->to('test@example.com')->subject('Test');
... });
```

## Configuración Avanzada para Multi-tenancy

### Email por Tenant
```php
// En app/Models/Space.php
public function getMailFromAddress()
{
    return $this->email_from ?? 'hello@' . $this->slug . '.enkiflow.com';
}

// En un Mailable
public function build()
{
    return $this->from(tenant()->getMailFromAddress())
                ->subject('Welcome to ' . tenant()->name);
}
```

### Templates Personalizados
```php
// app/Mail/TenantWelcome.php
class TenantWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Space $tenant
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $this->tenant->getMailFromAddress(),
                $this->tenant->name
            ),
            subject: 'Welcome to ' . $this->tenant->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant-welcome',
            with: [
                'userName' => $this->user->name,
                'tenantName' => $this->tenant->name,
                'loginUrl' => 'https://' . $this->tenant->domain,
            ],
        );
    }
}
```

### Queue para mejor performance
```php
// Enviar emails en background
Mail::to($user)->queue(new TenantWelcome($user, $tenant));

// O con delay
Mail::to($user)->later(now()->addMinutes(5), new TenantWelcome($user, $tenant));
```

## Webhooks de Resend (Opcional)

Para tracking avanzado de emails:

1. En Resend dashboard, configura webhook URL:
   ```
   https://app.enkiflow.com/webhooks/resend
   ```

2. Crea el controlador:
   ```php
   // app/Http/Controllers/ResendWebhookController.php
   public function handle(Request $request)
   {
       $event = $request->input('type');
       
       match($event) {
           'email.sent' => $this->handleSent($request->input('data')),
           'email.delivered' => $this->handleDelivered($request->input('data')),
           'email.bounced' => $this->handleBounced($request->input('data')),
           'email.complained' => $this->handleComplained($request->input('data')),
           default => null,
       };
       
       return response()->json(['status' => 'ok']);
   }
   ```

## Monitoreo y Analytics

### Dashboard de Resend
- Tasa de apertura
- Tasa de clicks
- Bounces y complaints
- Performance por dominio

### Logs en Laravel
```php
// config/logging.php
'channels' => [
    'mail' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mail.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

## Costos Estimados

- **Free**: 3,000 emails/mes
- **Pro**: $20/mes por 50,000 emails
- **Enterprise**: Contactar ventas

Para EnkiFlow con 1,000 usuarios activos:
- ~10,000 emails/mes (notificaciones, reportes, etc.)
- Costo: ~$20/mes

## Troubleshooting

### Email no se envía
1. Verificar API key en .env
2. Verificar dominio verificado en Resend
3. Revisar logs: `tail -f storage/logs/laravel.log`

### Rate limits
- Free: 10 emails/segundo
- Pro: 100 emails/segundo
- Usa queues para evitar límites

### SPF/DKIM
Resend configura automáticamente SPF y DKIM al verificar tu dominio.