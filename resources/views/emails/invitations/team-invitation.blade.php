@component('mail::message')
# Has sido invitado a {{ $space->name }}

{{ $inviterName }} te ha invitado a unirte a **{{ $space->name }}** en Enkiflow.

## Detalles de la invitación:

- **Espacio de trabajo:** {{ $space->name }}
- **Tu rol:** {{ $role }}
- **Invitado por:** {{ $inviterName }}
- **Válida hasta:** {{ $expiresAt->format('d/m/Y H:i') }}

@component('mail::button', ['url' => $acceptUrl])
Aceptar Invitación
@endcomponent

### ¿Qué es Enkiflow?

Enkiflow es una plataforma de gestión de tiempo y proyectos que te ayuda a:
- Registrar tu tiempo de trabajo
- Gestionar proyectos y tareas
- Colaborar con tu equipo
- Generar reportes detallados

### ¿Qué pasa después?

1. Haz clic en el botón "Aceptar Invitación"
2. Si no tienes cuenta, podrás crear una
3. Serás añadido automáticamente al espacio

**Nota:** Esta invitación expira en 7 días. Si expira, solicita una nueva al administrador del espacio.

Si no esperabas esta invitación, puedes ignorar este correo de forma segura.

Saludos,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Si tienes problemas con el botón, copia y pega este enlace en tu navegador:
{{ $acceptUrl }}
@endcomponent
@endcomponent