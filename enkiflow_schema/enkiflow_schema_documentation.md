# Documentación Técnica del Esquema de Base de Datos de EnkiFlow

## Índice de Contenidos

1. [Visión General del Modelo de Datos](#visión-general-del-modelo-de-datos)
2. [Arquitectura Multi-tenant](#arquitectura-multi-tenant)
3. [Documentación Detallada de Tablas](#documentación-detallada-de-tablas)
   - [Sistema Multi-tenant](#sistema-multi-tenant)
   - [Gestión de Proyectos](#gestión-de-proyectos)
   - [Sistema de Etiquetado y Comentarios](#sistema-de-etiquetado-y-comentarios)
   - [Seguimiento de Tiempo](#seguimiento-de-tiempo)
   - [Reportes y Dashboards](#reportes-y-dashboards)
   - [Facturación](#facturación)
   - [Extensibilidad](#extensibilidad)
4. [Relaciones Entre Entidades](#relaciones-entre-entidades)
5. [Estrategias de Optimización](#estrategias-de-optimización)
6. [Guía para Desarrolladores](#guía-para-desarrolladores)
7. [Consideraciones de Rendimiento y Escalabilidad](#consideraciones-de-rendimiento-y-escalabilidad)

## Visión General del Modelo de Datos

El esquema de base de datos de EnkiFlow implementa una arquitectura multi-tenant altamente escalable diseñada para soportar una plataforma completa de gestión de proyectos, seguimiento de tiempo y facturación. El diseño se ha estructurado en siete módulos principales, cada uno orientado a un aspecto funcional específico del sistema.

### Principios de Diseño Fundamentales

1. **Aislamiento de Datos por Tenant**: Cada entidad de negocio incluye un `tenant_id` que garantiza la separación estricta de datos entre diferentes espacios de trabajo.

2. **Relaciones Flexibles**: El esquema utiliza relaciones polimórficas y campos opcionales para maximizar la flexibilidad sin comprometer la integridad de los datos.

3. **Extensibilidad**: El uso estratégico de campos JSON permite almacenar configuraciones complejas y extensibles sin necesidad de modificar constantemente el esquema.

4. **Optimización para Consultas Frecuentes**: Los índices están cuidadosamente posicionados para acelerar las operaciones más comunes basadas en los patrones de acceso previstos.

5. **Trazabilidad y Auditoría**: La inclusión universal de timestamps (`created_at`, `updated_at`) y campos para soft delete (`deleted_at`) facilita la trazabilidad de datos.

### Diagrama Conceptual de Entidades Principales

A continuación se presenta un diagrama conceptual de las entidades principales y sus relaciones:

```
USERS ──────┐
  │         │
  │         ▼
  │      SPACE_USERS
  │         ▲
  │         │
  │         │
  │      TENANTS ────────────┐
  │         │                │
  │         │                │
  ▼         ▼                ▼
USER_PROFILES  DOMAINS    PROJECTS ─────┐
                             │          │
                             │          │
                             ▼          ▼
                          CLIENTS    INVOICE_ITEMS
                                        ▲
                                        │
                                        │
                                     INVOICES
                                        │
                                        │
                                        ▼
                                 INVOICE_TEMPLATES

TASKS ─────────────┐
  │                │
  │                │
  ▼                ▼
TASK_ASSIGNEES  COMMENTS
  ▲
  │
  │
USERS

TIME_ENTRIES ───────────┐
  │                     │
  │                     │
  ▼                     ▼
TIME_CATEGORIES    ACTIVITY_LOGS

DASHBOARDS ───────────┐
  │                   │
  │                   │
  ▼                   ▼
USERS           DASHBOARD_WIDGETS
```

## Arquitectura Multi-tenant

EnkiFlow implementa una arquitectura multi-tenant robusta que permite a múltiples organizaciones (tenants) utilizar la misma instancia de la aplicación mientras mantiene sus datos completamente aislados.

### Implementación del Modelo Multi-tenant

La implementación se basa en un enfoque de discriminador por ID, donde:

1. La tabla `tenants` almacena los espacios de trabajo con un identificador único (`id`) de tipo varchar.

2. Cada tabla específica de tenant contiene un campo `tenant_id` que referencia a `tenants.id`.

3. Todas las consultas se filtran implícitamente por `tenant_id` a través de middleware de tenancy, garantizando un aislamiento completo de datos.

### Ventajas de este Enfoque

- **Simplicidad**: Facilita la comprensión y mantenimiento del esquema al seguir un patrón consistente.
- **Rendimiento**: Evita joins complejos al incluir el discriminador directamente en cada tabla.
- **Flexibilidad**: Permite políticas de permisos granulares y configuraciones específicas por tenant.
- **Escalabilidad**: Facilita estrategias de particionamiento y sharding futuras basadas en `tenant_id`.

## Documentación Detallada de Tablas

### Sistema Multi-tenant

#### Tabla: `users`

**Propósito**: Almacena la información básica de todos los usuarios del sistema.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del usuario |
| name | varchar | NOT NULL | Nombre completo del usuario |
| email | varchar | UNIQUE, NOT NULL | Email usado para autenticación |
| password | varchar | NOT NULL | Contraseña hasheada |
| remember_token | varchar | NULL | Token para funcionalidad "recordarme" |
| email_verified_at | timestamp | NULL | Momento de verificación del email |
| two_factor_secret | text | NULL | Secreto para autenticación de dos factores |
| two_factor_recovery_codes | text | NULL | Códigos de recuperación para 2FA |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Índice único: `email`

**Relaciones**:
- Uno-a-uno con `user_profiles`
- Uno-a-muchos con `time_entries`
- Muchos-a-muchos con `tasks` a través de `task_assignees`
- Muchos-a-muchos con `tenants` a través de `space_users`

**Notas**:
- El campo `email` se utiliza como identificador único para autenticación
- Los campos relacionados con 2FA permiten implementar autenticación de dos factores opcional
- El campo `deleted_at` implementa soft deletes para preservar la integridad referencial

#### Tabla: `user_profiles`

**Propósito**: Almacena información extendida del perfil de usuario, separada de la tabla principal de usuarios para mejor rendimiento.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del perfil |
| user_id | int | FK (users.id), NOT NULL | Usuario al que pertenece el perfil |
| avatar | varchar | NULL | URL o path a la imagen de avatar |
| job_title | varchar | NULL | Cargo o título profesional |
| phone | varchar | NULL | Número de teléfono |
| timezone | varchar | DEFAULT: 'UTC' | Zona horaria para mostrar fechas |
| locale | varchar | DEFAULT: 'en' | Preferencia de idioma |
| theme_preference | varchar | DEFAULT: 'light' | Preferencia de tema (claro/oscuro) |
| notification_preferences | json | NOT NULL | Configuración de notificaciones |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Uno-a-uno inversa con `users`

**Notas**:
- El campo JSON `notification_preferences` permite almacenar configuraciones complejas de notificaciones sin necesidad de tablas adicionales
- Los valores por defecto para `timezone`, `locale` y `theme_preference` facilitan la creación de perfiles con valores sensatos

#### Tabla: `tenants`

**Propósito**: Almacena la información de los espacios de trabajo (tenants) dentro del sistema multi-tenant.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | varchar | PK | Identificador único del tenant |
| name | varchar | NOT NULL | Nombre del espacio de trabajo |
| plan | varchar | DEFAULT: 'free' | Plan de suscripción |
| trial_ends_at | timestamp | NULL | Fecha de finalización del período de prueba |
| data | json | NULL | Datos adicionales del tenant |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`

**Relaciones**:
- Uno-a-muchos con `domains`
- Uno-a-muchos con `space_users`
- Uno-a-muchos con todas las tablas específicas de tenant

**Notas**:
- Se utiliza un campo `id` de tipo varchar para facilitar identificadores legibles y personalizados
- El campo `plan` se usa para implementar límites según el nivel de suscripción
- El campo JSON `data` proporciona extensibilidad para metadatos adicionales del tenant

#### Tabla: `domains`

**Propósito**: Gestiona los dominios personalizados asociados a cada tenant para acceso personalizado.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del dominio |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el dominio |
| domain | varchar | UNIQUE, NOT NULL | Nombre de dominio completo |
| is_primary | boolean | DEFAULT: false | Indica si es el dominio principal |
| verified_at | timestamp | NULL | Momento de verificación del dominio |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Índice único: `domain`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`

**Notas**:
- El sistema permite múltiples dominios por tenant pero solo uno puede ser marcado como primario
- El campo `verified_at` null indica que el dominio aún no ha sido verificado

#### Tabla: `space_users`

**Propósito**: Implementa la relación muchos-a-muchos entre usuarios y tenants, con información de pertenencia.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la relación |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el usuario |
| user_id | int | FK (users.id), NOT NULL | Usuario que pertenece al tenant |
| role | varchar | NOT NULL | Rol del usuario en este espacio |
| permissions | json | NULL | Permisos específicos adicionales |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Índice único compuesto: `(tenant_id, user_id)`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users`

**Notas**:
- El índice único compuesto garantiza que un usuario no pueda pertenecer múltiples veces al mismo tenant
- El campo JSON `permissions` permite permisos granulares adicionales además del rol principal

#### Tabla: `invitations`

**Propósito**: Gestiona las invitaciones pendientes para unirse a un tenant.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la invitación |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant que envía la invitación |
| email | varchar | NOT NULL | Email del destinatario |
| role | varchar | NOT NULL | Rol que se asignará al aceptar |
| token | varchar | UNIQUE, NOT NULL | Token único de invitación |
| expires_at | timestamp | NOT NULL | Fecha de expiración |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Índice único: `token`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`

**Notas**:
- El campo `token` debe ser único y seguro para prevenir adivinación
- Las invitaciones con `expires_at` en el pasado se consideran expiradas y no válidas

### Gestión de Proyectos

#### Tabla: `clients`

**Propósito**: Almacena información de los clientes para los que se realizan proyectos.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del cliente |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el cliente |
| name | varchar | NOT NULL | Nombre del cliente |
| email | varchar | NULL | Email de contacto principal |
| phone | varchar | NULL | Teléfono de contacto |
| address | text | NULL | Dirección postal |
| notes | text | NULL | Notas adicionales |
| is_active | boolean | DEFAULT: true | Estado activo/inactivo del cliente |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Uno-a-muchos con `projects`
- Uno-a-muchos con `invoices`

**Notas**:
- El campo `is_active` permite desactivar clientes sin eliminarlos
- Los campos de contacto son opcionales para facilitar la creación rápida de clientes

#### Tabla: `projects`

**Propósito**: Gestiona los proyectos que contienen tareas y registros de tiempo.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del proyecto |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el proyecto |
| client_id | int | FK (clients.id), NULL | Cliente asociado al proyecto (opcional) |
| name | varchar | NOT NULL | Nombre del proyecto |
| description | text | NULL | Descripción detallada |
| budget | decimal | NULL | Presupuesto asignado |
| budget_type | varchar | NULL | Tipo de presupuesto ('fixed', 'hourly', etc.) |
| status | varchar | DEFAULT: 'active' | Estado del proyecto |
| start_date | date | NULL | Fecha de inicio planificada |
| due_date | date | NULL | Fecha límite |
| completed_at | timestamp | NULL | Momento de finalización real |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `client_id` referencia `clients.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `clients` (opcional)
- Uno-a-muchos con `tasks`
- Uno-a-muchos con `time_entries`
- Uno-a-muchos con `invoice_items`

**Notas**:
- La relación con `client_id` es opcional para permitir proyectos internos
- El campo `budget_type` determina cómo interpretar y utilizar el campo `budget`
- Los estados comunes incluyen 'active', 'completed', 'on_hold', pero son personalizables

#### Tabla: `task_states`

**Propósito**: Define los estados personalizables para tareas en un flujo de trabajo kanban.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del estado |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el estado |
| name | varchar | NOT NULL | Nombre del estado |
| color | varchar | DEFAULT: '#3498db' | Color para representación visual |
| position | int | NOT NULL | Orden para visualización (menor = izquierda) |
| is_default | boolean | DEFAULT: false | Indica si es el estado por defecto para nuevas tareas |
| is_completed | boolean | DEFAULT: false | Indica si tareas en este estado se consideran completadas |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Uno-a-muchos con `tasks`

**Notas**:
- El campo `position` permite ordenar estados en un flujo de trabajo visual (kanban)
- Solo un estado puede tener `is_default = true` por tenant
- El campo `is_completed` facilita la generación de reportes de tareas completadas

#### Tabla: `tasks`

**Propósito**: Almacena las tareas que pueden organizarse jerárquicamente y asignarse a proyectos.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la tarea |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la tarea |
| project_id | int | FK (projects.id), NULL | Proyecto asociado (opcional) |
| parent_id | int | FK (tasks.id), NULL | Tarea padre para jerarquía |
| task_state_id | int | FK (task_states.id), NOT NULL | Estado actual de la tarea |
| name | varchar | NOT NULL | Nombre/título de la tarea |
| description | text | NULL | Descripción detallada |
| priority | varchar | DEFAULT: 'medium' | Prioridad ('low', 'medium', 'high', 'urgent') |
| estimated_time | int | NULL | Tiempo estimado en minutos |
| start_date | date | NULL | Fecha de inicio planificada |
| due_date | date | NULL | Fecha límite |
| completed_at | timestamp | NULL | Momento de finalización real |
| position | int | NOT NULL | Posición dentro del estado (para ordenación) |
| is_recurring | boolean | DEFAULT: false | Indica si es una tarea recurrente |
| recurrence_pattern | json | NULL | Patrón de recurrencia si aplica |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `project_id` referencia `projects.id`
- Clave foránea: `parent_id` referencia `tasks.id`
- Clave foránea: `task_state_id` referencia `task_states.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `projects` (opcional)
- Muchos-a-uno con `task_states`
- Uno-a-muchos consigo misma (auto-referencia para jerarquía)
- Uno-a-muchos con `time_entries`
- Muchos-a-muchos con `users` a través de `task_assignees`
- Polimórfica con `comments` y `taggables`

**Notas**:
- El campo `parent_id` permite crear estructuras jerárquicas de tareas y subtareas
- El campo JSON `recurrence_pattern` almacena configuración compleja para tareas recurrentes
- El campo `position` permite ordenación personalizada dentro de un estado
- La relación con `project_id` es opcional para permitir tareas no asociadas a proyectos

#### Tabla: `task_assignees`

**Propósito**: Implementa la relación muchos-a-muchos entre tareas y usuarios asignados.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la asignación |
| task_id | int | FK (tasks.id), NOT NULL | Tarea asignada |
| user_id | int | FK (users.id), NOT NULL | Usuario asignado |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Índice único compuesto: `(task_id, user_id)`
- Clave foránea: `task_id` referencia `tasks.id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Muchos-a-uno con `tasks`
- Muchos-a-uno con `users`

**Notas**:
- El índice único compuesto garantiza que un usuario no pueda ser asignado múltiples veces a la misma tarea
- Esta tabla implementa asignación múltiple, permitiendo que una tarea tenga varios responsables

### Sistema de Etiquetado y Comentarios

#### Tabla: `tags`

**Propósito**: Almacena etiquetas para clasificación flexible de entidades.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la etiqueta |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la etiqueta |
| name | varchar | NOT NULL | Nombre de la etiqueta |
| color | varchar | DEFAULT: '#3498db' | Color para representación visual |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Índice único compuesto: `(tenant_id, name)`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Uno-a-muchos con `taggables`

**Notas**:
- El índice único compuesto garantiza nombres únicos de etiquetas dentro de cada tenant
- El campo `color` permite personalización visual en la interfaz

#### Tabla: `taggables`

**Propósito**: Implementa relaciones polimórficas para el sistema de etiquetado.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la relación |
| tag_id | int | FK (tags.id), NOT NULL | Etiqueta aplicada |
| taggable_id | int | NOT NULL | ID del objeto etiquetado |
| taggable_type | varchar | NOT NULL | Tipo de modelo etiquetado |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Índice compuesto: `(taggable_id, taggable_type)`
- Clave foránea: `tag_id` referencia `tags.id`

**Relaciones**:
- Muchos-a-uno con `tags`
- Relación polimórfica con múltiples modelos

**Notas**:
- Esta implementación polimórfica permite etiquetar cualquier tipo de entidad en el sistema
- El índice compuesto optimiza búsquedas de todas las etiquetas de una entidad específica
- `taggable_type` contiene el nombre completo de la clase del modelo (ejemplo: 'App\\Models\\Task')

#### Tabla: `comments`

**Propósito**: Almacena comentarios que pueden asociarse a cualquier entidad mediante relación polimórfica.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del comentario |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el comentario |
| user_id | int | FK (users.id), NOT NULL | Usuario que creó el comentario |
| commentable_id | int | NOT NULL | ID del objeto comentado |
| commentable_type | varchar | NOT NULL | Tipo de modelo comentado |
| content | text | NOT NULL | Contenido del comentario |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Índice compuesto: `(commentable_id, commentable_type)`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users`
- Relación polimórfica con múltiples modelos

**Notas**:
- Esta implementación polimórfica permite comentar cualquier tipo de entidad en el sistema
- El índice compuesto optimiza búsquedas de todos los comentarios de una entidad específica
- El soft delete (`deleted_at`) permite ocultar comentarios sin perder el contexto histórico

### Seguimiento de Tiempo

#### Tabla: `time_categories`

**Propósito**: Define categorías para clasificar entradas de tiempo.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la categoría |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la categoría |
| name | varchar | NOT NULL | Nombre de la categoría |
| color | varchar | DEFAULT: '#3498db' | Color para representación visual |
| is_billable | boolean | DEFAULT: true | Indica si el tiempo en esta categoría es facturable |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Uno-a-muchos con `time_entries`

**Notas**:
- El campo `is_billable` facilita la separación de tiempo facturable y no facturable
- Las categorías permiten análisis más detallado del tiempo dedicado

#### Tabla: `time_entries`

**Propósito**: Registra entradas de tiempo dedicado a tareas y proyectos.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la entrada |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la entrada |
| user_id | int | FK (users.id), NOT NULL | Usuario que registró el tiempo |
| project_id | int | FK (projects.id), NULL | Proyecto asociado (opcional) |
| task_id | int | FK (tasks.id), NULL | Tarea asociada (opcional) |
| time_category_id | int | FK (time_categories.id), NULL | Categoría asociada (opcional) |
| description | text | NULL | Descripción de la actividad |
| start_time | timestamp | NOT NULL | Momento de inicio |
| end_time | timestamp | NULL | Momento de finalización (NULL si en progreso) |
| duration | int | NULL | Duración en segundos (calculado o manual) |
| is_billable | boolean | DEFAULT: true | Indica si es tiempo facturable |
| is_running | boolean | DEFAULT: false | Indica si el temporizador está activo |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`
- Clave foránea: `project_id` referencia `projects.id`
- Clave foránea: `task_id` referencia `tasks.id`
- Clave foránea: `time_category_id` referencia `time_categories.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users`
- Muchos-a-uno con `projects` (opcional)
- Muchos-a-uno con `tasks` (opcional)
- Muchos-a-uno con `time_categories` (opcional)
- Uno-a-muchos con `activity_logs`

**Notas**:
- Las relaciones con `project_id`, `task_id` y `time_category_id` son opcionales para máxima flexibilidad
- Si `end_time` es NULL, la entrada se considera en progreso con `is_running = true`
- El campo `duration` puede calcularse como la diferencia entre `end_time` y `start_time` o introducirse manualmente
- El campo `is_billable` puede heredarse de `time_categories.is_billable` pero puede modificarse individualmente

#### Tabla: `activity_logs`

**Propósito**: Registra detalles de actividad asociados a entradas de tiempo para detección automática.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del registro |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el registro |
| user_id | int | FK (users.id), NOT NULL | Usuario relacionado con la actividad |
| time_entry_id | int | FK (time_entries.id), NOT NULL | Entrada de tiempo asociada |
| activity_type | varchar | NOT NULL | Tipo de actividad detectada |
| metadata | json | NULL | Datos adicionales sobre la actividad |
| timestamp | timestamp | NOT NULL | Momento exacto de la actividad |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`
- Clave foránea: `time_entry_id` referencia `time_entries.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users`
- Muchos-a-uno con `time_entries`

**Notas**:
- El campo `activity_type` puede contener valores como 'keyboard', 'mouse', 'application_focus'
- El campo JSON `metadata` almacena información específica del tipo de actividad (ejemplo: aplicación activa)
- Esta tabla facilita la detección automática de actividad y ajustes inteligentes de tiempo

### Reportes y Dashboards

#### Tabla: `dashboards`

**Propósito**: Almacena configuraciones de dashboards personalizados por usuario.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del dashboard |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el dashboard |
| user_id | int | FK (users.id), NOT NULL | Usuario propietario del dashboard |
| name | varchar | NOT NULL | Nombre del dashboard |
| is_default | boolean | DEFAULT: false | Indica si es el dashboard predeterminado |
| layout | json | NULL | Configuración del layout |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users`
- Uno-a-muchos con `dashboard_widgets`

**Notas**:
- Solo un dashboard puede tener `is_default = true` por usuario
- El campo JSON `layout` almacena configuración de distribución y tamaño general

#### Tabla: `dashboard_widgets`

**Propósito**: Define widgets individuales que componen un dashboard.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del widget |
| dashboard_id | int | FK (dashboards.id), NOT NULL | Dashboard al que pertenece |
| widget_type | varchar | NOT NULL | Tipo de widget |
| title | varchar | NOT NULL | Título personalizable |
| position | json | NOT NULL | Posición en el dashboard (x, y, ancho, alto) |
| settings | json | NOT NULL | Configuración específica del widget |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `dashboard_id` referencia `dashboards.id`

**Relaciones**:
- Muchos-a-uno con `dashboards`

**Notas**:
- El campo `widget_type` define el tipo de visualización (gráfico, tabla, contador, etc.)
- El campo JSON `position` facilita interfaces drag-and-drop para personalización visual
- El campo JSON `settings` almacena configuración específica según el tipo de widget (filtros, colores, etc.)

#### Tabla: `saved_reports`

**Propósito**: Almacena configuraciones de reportes personalizados y programados.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del reporte |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el reporte |
| user_id | int | FK (users.id), NOT NULL | Usuario creador del reporte |
| name | varchar | NOT NULL | Nombre del reporte |
| report_type | varchar | NOT NULL | Tipo de reporte |
| filters | json | NOT NULL | Filtros aplicados |
| columns | json | NOT NULL | Columnas seleccionadas |
| sort_by | varchar | NULL | Campo de ordenación |
| sort_direction | varchar | DEFAULT: 'asc' | Dirección de ordenación ('asc'/'desc') |
| schedule | json | NULL | Programación de envío automático |
| recipients | json | NULL | Destinatarios para envío automático |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users`

**Notas**:
- El campo `report_type` define la naturaleza del reporte ('time', 'project', 'task', etc.)
- Los campos JSON `filters` y `columns` permiten alta personalización sin cambios de esquema
- El campo JSON `schedule` almacena configuración compleja de periodicidad para envíos automáticos
- El campo JSON `recipients` almacena lista de destinatarios y preferencias de formato

### Facturación

#### Tabla: `invoice_templates`

**Propósito**: Almacena plantillas personalizables para generación de facturas.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la plantilla |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la plantilla |
| name | varchar | NOT NULL | Nombre de la plantilla |
| is_default | boolean | DEFAULT: false | Indica si es la plantilla predeterminada |
| content | text | NOT NULL | Contenido HTML/CSS de la plantilla |
| settings | json | NOT NULL | Configuración adicional |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Uno-a-muchos con `invoices`

**Notas**:
- Solo una plantilla puede tener `is_default = true` por tenant
- El campo `content` contiene la estructura HTML/CSS de la plantilla
- El campo JSON `settings` almacena configuraciones como márgenes, numeración, etc.

#### Tabla: `invoices`

**Propósito**: Almacena facturas generadas a clientes.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la factura |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la factura |
| client_id | int | FK (clients.id), NOT NULL | Cliente facturado |
| invoice_template_id | int | FK (invoice_templates.id), NULL | Plantilla utilizada (opcional) |
| invoice_number | varchar | NOT NULL | Número único de factura |
| status | varchar | DEFAULT: 'draft' | Estado de la factura |
| issue_date | date | NOT NULL | Fecha de emisión |
| due_date | date | NOT NULL | Fecha de vencimiento |
| subtotal | decimal | NOT NULL | Subtotal antes de impuestos |
| tax_rate | decimal | DEFAULT: 0 | Tasa de impuesto aplicada |
| tax_amount | decimal | DEFAULT: 0 | Monto de impuesto calculado |
| discount_amount | decimal | DEFAULT: 0 | Monto de descuento aplicado |
| total | decimal | NOT NULL | Monto total |
| notes | text | NULL | Notas adicionales |
| terms | text | NULL | Términos y condiciones |
| sent_at | timestamp | NULL | Momento de envío al cliente |
| paid_at | timestamp | NULL | Momento de pago |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |
| deleted_at | timestamp | NULL | Para implementar soft deletes |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `client_id` referencia `clients.id`
- Clave foránea: `invoice_template_id` referencia `invoice_templates.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `clients`
- Muchos-a-uno con `invoice_templates` (opcional)
- Uno-a-muchos con `invoice_items`

**Notas**:
- Los estados comunes incluyen 'draft', 'sent', 'paid', 'overdue', 'cancelled'
- La relación con `invoice_template_id` es opcional para facilitar cambios posteriores en plantillas
- Los campos `sent_at` y `paid_at` permiten análisis de tiempos de pago y seguimiento de facturas pendientes

#### Tabla: `invoice_items`

**Propósito**: Almacena ítems individuales de una factura.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del ítem |
| invoice_id | int | FK (invoices.id), NOT NULL | Factura a la que pertenece |
| project_id | int | FK (projects.id), NULL | Proyecto asociado (opcional) |
| description | text | NOT NULL | Descripción del ítem |
| quantity | decimal | NOT NULL | Cantidad |
| unit_price | decimal | NOT NULL | Precio unitario |
| amount | decimal | NOT NULL | Monto total (quantity * unit_price) |
| tax_rate | decimal | DEFAULT: 0 | Tasa de impuesto específica del ítem |
| tax_amount | decimal | DEFAULT: 0 | Monto de impuesto calculado |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `invoice_id` referencia `invoices.id`
- Clave foránea: `project_id` referencia `projects.id`

**Relaciones**:
- Muchos-a-uno con `invoices`
- Muchos-a-uno con `projects` (opcional)

**Notas**:
- La relación con `project_id` es opcional pero facilita reportes de facturación por proyecto
- Los campos `tax_rate` y `tax_amount` a nivel de ítem permiten aplicar diferentes tasas de impuesto

### Extensibilidad

#### Tabla: `integrations`

**Propósito**: Gestiona integraciones con servicios externos como Google, Slack, GitHub, etc.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único de la integración |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece la integración |
| user_id | int | FK (users.id), NULL | Usuario propietario (si es a nivel de usuario) |
| provider | varchar | NOT NULL | Proveedor de servicio ('google', 'slack', etc.) |
| name | varchar | NULL | Nombre personalizado para la integración |
| credentials | json | NOT NULL, encriptado | Tokens y credenciales encriptados |
| settings | json | NULL | Configuración específica |
| status | varchar | DEFAULT: 'active' | Estado de la integración |
| last_used_at | timestamp | NULL | Momento de último uso |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`
- Clave foránea: `user_id` referencia `users.id`

**Relaciones**:
- Muchos-a-uno con `tenants`
- Muchos-a-uno con `users` (opcional)

**Notas**:
- El campo `user_id` puede ser NULL para integraciones a nivel de tenant
- El campo JSON `credentials` se almacena encriptado por seguridad
- Los estados comunes incluyen 'active', 'error', 'revoked'

#### Tabla: `webhook_endpoints`

**Propósito**: Define puntos de conexión para webhooks que notifican a sistemas externos.

| Columna | Tipo | Restricciones | Descripción |
|---------|------|---------------|-------------|
| id | int | PK, incremento automático | Identificador único del endpoint |
| tenant_id | varchar | FK (tenants.id), NOT NULL | Tenant al que pertenece el endpoint |
| url | varchar | NOT NULL | URL del endpoint |
| events | json | NOT NULL | Eventos suscritos |
| secret | varchar | NOT NULL, encriptado | Secreto para verificación |
| is_active | boolean | DEFAULT: true | Estado activo/inactivo |
| created_at | timestamp | NOT NULL | Momento de creación del registro |
| updated_at | timestamp | NOT NULL | Momento de última actualización |

**Índices**:
- Clave primaria: `id`
- Clave foránea: `tenant_id` referencia `tenants.id`

**Relaciones**:
- Muchos-a-uno con `tenants`

**Notas**:
- El campo JSON `events` almacena lista de eventos a los que está suscrito el webhook
- El campo `secret` se almacena encriptado y se usa para firmar payloads
- Los webhooks permiten integraciones de terceros con el sistema

## Relaciones Entre Entidades

### Relaciones Uno-a-Uno

1. **Usuario y Perfil**
   - `users.id` → `user_profiles.user_id`
   - Cada usuario tiene exactamente un perfil extendido
   - Propósito: Separar datos de autenticación de preferencias y datos personales

### Relaciones Uno-a-Muchos

1. **Tenant y Entidades Dependientes**
   - `tenants.id` → `*.tenant_id` (todas las tablas específicas de tenant)
   - Cada tenant contiene múltiples entidades propias (proyectos, tareas, clientes, etc.)
   - Propósito: Implementar aislamiento de datos en arquitectura multi-tenant

2. **Proyectos y Tareas**
   - `projects.id` → `tasks.project_id`
   - Un proyecto puede contener múltiples tareas
   - Propósito: Organizar tareas en grupos lógicos para gestión de proyectos

3. **Tareas y Subtareas** (Auto-referencia)
   - `tasks.id` → `tasks.parent_id`
   - Una tarea puede tener múltiples subtareas
   - Propósito: Permitir descomposición jerárquica de tareas complejas

4. **Estado de Tarea y Tareas**
   - `task_states.id` → `tasks.task_state_id`
   - Un estado puede aplicar a múltiples tareas
   - Propósito: Implementar flujos de trabajo personalizables tipo kanban

5. **Dashboard y Widgets**
   - `dashboards.id` → `dashboard_widgets.dashboard_id`
   - Un dashboard contiene múltiples widgets
   - Propósito: Crear interfaces personalizadas para visualización de datos

6. **Factura e Ítems**
   - `invoices.id` → `invoice_items.invoice_id`
   - Una factura contiene múltiples ítems
   - Propósito: Desglosar facturas en conceptos individuales

### Relaciones Muchos-a-Muchos

1. **Usuarios y Tenants**
   - Implementada mediante tabla pivot `space_users`
   - Un usuario puede pertenecer a múltiples tenants
   - Un tenant puede tener múltiples usuarios
   - Propósito: Permitir a usuarios participar en diferentes espacios de trabajo

2. **Tareas y Usuarios Asignados**
   - Implementada mediante tabla pivot `task_assignees`
   - Una tarea puede tener múltiples usuarios asignados
   - Un usuario puede estar asignado a múltiples tareas
   - Propósito: Facilitar trabajo colaborativo y asignación múltiple

3. **Etiquetas y Entidades Etiquetables**
   - Implementada mediante tabla polimórfica `taggables`
   - Una etiqueta puede aplicarse a múltiples entidades de diferentes tipos
   - Una entidad puede tener múltiples etiquetas
   - Propósito: Sistema flexible de categorización aplicable a cualquier entidad

### Relaciones Polimórficas

1. **Sistema de Comentarios**
   - Implementada mediante campos `commentable_id` y `commentable_type` en `comments`
   - Permite comentarios en cualquier tipo de entidad (tareas, proyectos, etc.)
   - Propósito: Centralizar la funcionalidad de comentarios reutilizando la misma tabla

2. **Sistema de Etiquetado**
   - Implementada mediante campos `taggable_id` y `taggable_type` en `taggables`
   - Permite etiquetar cualquier tipo de entidad (tareas, proyectos, tiempo, etc.)
   - Propósito: Sistema unificado de categorización aplicable a cualquier modelo

## Estrategias de Optimización

### Índices Estratégicos

El esquema incluye índices cuidadosamente ubicados para optimizar las consultas más frecuentes:

1. **Índices Únicos**
   - `users.email`: Garantiza emails únicos para autenticación
   - `domains.domain`: Previene duplicación de dominios
   - `invitations.token`: Asegura unicidad de tokens de invitación
   - `(tenant_id, name)` en `tags`: Evita nombres duplicados por tenant

2. **Índices Compuestos**
   - `(tenant_id, user_id)` en `space_users`: Optimiza consultas de pertenencia
   - `(task_id, user_id)` en `task_assignees`: Agiliza búsquedas de asignaciones
   - `(taggable_id, taggable_type)` en `taggables`: Mejora recuperación de etiquetas por entidad
   - `(commentable_id, commentable_type)` en `comments`: Optimiza búsqueda de comentarios por entidad

3. **Índices de Claves Foráneas**
   - Todas las claves foráneas están indexadas para optimizar joins

### Optimización de Tipos de Datos

1. **Uso de `varchar` para IDs de Tenant**
   - Permite identificadores legibles y personalizados
   - Facilita la depuración y mejora usabilidad en desarrollo

2. **Campos JSON para Configuraciones Complejas**
   - Evita la necesidad de tablas adicionales para almacenar configuraciones
   - Permite extensibilidad sin cambios de esquema
   - Ejemplos: `notification_preferences`, `recurrence_pattern`, `settings`

3. **Uso de `decimal` para Valores Monetarios**
   - Garantiza precisión en cálculos financieros
   - Evita errores de redondeo típicos de tipos flotantes

### Soft Deletes

Las tablas principales implementan soft deletes mediante el campo `deleted_at`:

1. **Ventajas**
   - Preserva la integridad referencial
   - Permite recuperación de datos
   - Facilita auditoría e históricos

2. **Tablas con Soft Delete**
   - `users`, `tenants`, `clients`, `projects`, `tasks`, `time_entries`, `comments`, `invoices`, etc.

## Guía para Desarrolladores

### Patrones de Consulta Comunes

#### 1. Consultas en Contexto Multi-tenant

Todas las consultas deben incluir el filtro de tenant para garantizar el aislamiento de datos:

```php
// Incorrecto
$projects = Project::all();

// Correcto
$projects = Project::where('tenant_id', $currentTenantId)->get();
```

Idealmente, este filtrado se implementa automáticamente mediante middleware o scopes globales:

```php
// Scope global
public function boot()
{
    Project::addGlobalScope('tenant', function (Builder $builder) {
        $builder->where('tenant_id', $currentTenantId);
    });
}
```

#### 2. Optimización de Consultas Relacionales

Para reducir el número de consultas al obtener relaciones:

```php
// Ineficiente (N+1 problem)
$tasks = Task::all();
foreach ($tasks as $task) {
    $project = $task->project;  // Consulta adicional por cada tarea
}

// Optimizado
$tasks = Task::with('project')->get();  // Eager loading
```

#### 3. Consultas con Relaciones Polimórficas

Para obtener comentarios de una entidad específica:

```php
$taskComments = Comment::where('commentable_type', Task::class)
                       ->where('commentable_id', $taskId)
                       ->get();
```

Para obtener todas las etiquetas de una entidad:

```php
$taskTags = Tag::whereHas('taggables', function ($query) use ($taskId) {
    $query->where('taggable_type', Task::class)
          ->where('taggable_id', $taskId);
})->get();
```

#### 4. Consultas para Dashboards y Reportes

Para obtener tiempo registrado agrupado por proyecto:

```php
$timeByProject = TimeEntry::select('project_id', DB::raw('SUM(duration) as total_duration'))
                         ->whereNotNull('end_time')
                         ->groupBy('project_id')
                         ->with('project:id,name')  // Seleccionar solo campos necesarios
                         ->get();
```

### Mejores Prácticas

#### 1. Filtrado por Tenant

- **Siempre** filtrar por `tenant_id` en todas las consultas
- Implementar middleware o scopes globales para automatizar este filtrado
- Verificar el acceso a nivel de tenant antes de cualquier operación

#### 2. Manejo de Campos JSON

- Definir esquemas claros para todos los campos JSON
- Utilizar casts en modelos Eloquent para trabajar con campos JSON como arrays/objetos:

```php
protected $casts = [
    'notification_preferences' => 'array',
    'settings' => 'array',
    'recurrence_pattern' => 'array',
];
```

#### 3. Relaciones Polimórficas

- Utilizar nombres de clase completos para `*_type` (ej: `App\Models\Task`)
- Implementar interfaces comunes para modelos polimórficos relacionados

#### 4. Gestión de Transacciones

- Utilizar transacciones para operaciones que afectan múltiples tablas
- Especialmente importante en operaciones como creación de proyectos con tareas iniciales

```php
DB::transaction(function () {
    $project = Project::create([...]);
    $tasks = $project->tasks()->createMany([...]);
});
```

#### 5. Caché Efectivo

- Cachear resultados de consultas frecuentes y costosas
- Utilizar etiquetas por tenant para invalidación selectiva

```php
$projects = Cache::tags(["tenant:{$tenantId}", 'projects'])->remember(
    "tenant:{$tenantId}:projects", 
    3600, 
    fn() => Project::where('tenant_id', $tenantId)->get()
);
```

### Extensión del Esquema

Para extender el esquema con nuevas funcionalidades:

1. **Para Propiedades Nuevas en Entidades Existentes**
   - Si son pocos campos simples: Crear migración de modificación para añadir columnas
   - Si son campos complejos o numerosos: Utilizar campos JSON existentes
   - Si requiere comportamiento específico: Crear tabla relacionada nueva

2. **Para Nuevos Tipos de Entidades**
   - Seguir el patrón establecido (`tenant_id`, timestamps, etc.)
   - Documentar propósito y relaciones
   - Considerar utilizar sistemas polimórficos existentes (tags, comments)

3. **Consideraciones de Rendimiento**
   - Añadir índices para campos frecuentemente consultados
   - Documentar patrones de acceso esperados
   - Evaluar impacto en consultas existentes

## Consideraciones de Rendimiento y Escalabilidad

### Tablas de Alto Volumen

Las siguientes tablas crecerán significativamente y requieren estrategias específicas:

1. **`time_entries`**
   - Volumen esperado: Miles por usuario activo por mes
   - Estrategias:
     - Particionamiento por fecha (mensual/trimestral)
     - Índices compuestos para consultas frecuentes
     - Archivado de datos históricos

2. **`activity_logs`**
   - Volumen esperado: Decenas de miles por usuario activo por mes
   - Estrategias:
     - Particionamiento por fecha (semanal/mensual)
     - Compresión de datos
     - Políticas de retención para purgar datos antiguos

3. **`tasks`**
   - Volumen esperado: Cientos por proyecto
   - Estrategias:
     - Consultas optimizadas para jerarquías profundas
     - Caché para estructuras de árbol completas

### Optimizaciones para Consultas Frecuentes

1. **Reportes de Tiempo**
   - Precalcular agregados periódicamente
   - Utilizar vistas materializadas para reportes comunes
   - Implementar particionamiento para búsquedas por rango de fechas

2. **Dashboards en Tiempo Real**
   - Cachear datos de widgets con TTL corto
   - Utilizar actualizaciones incrementales vía WebSockets
   - Limitar la granularidad de datos según necesidad

3. **Búsquedas y Filtros**
   - Implementar búsqueda de texto completo para contenido extenso
   - Utilizar índices para filtros comunes (estado, fecha, asignado)
   - Considerar soluciones especializadas para búsqueda (Elasticsearch) en crecimiento futuro

### Estrategias de Caché

1. **Caché por Tenant**
   - Utilizar prefijos o tags para aislar caché por tenant
   - Facilita invalidación selectiva sin afectar otros tenants

2. **Caché de Modelos Frecuentes**
   - Cachear entidades de referencia como `task_states`, `time_categories`
   - Invalidar solo en cambios específicos

3. **Resultados Agregados**
   - Cachear resultados de reportes y dashboards
   - Implementar regeneración automática en segundo plano

### Escalamiento Futuro

1. **Particionamiento de Base de Datos**
   - Particionamiento horizontal por `tenant_id` para escalamiento masivo
   - Separación de tablas de alto volumen en bases de datos dedicadas

2. **Microservicios**
   - El diseño modular facilita futura migración a microservicios
   - Separación natural entre módulos (proyectos, tiempo, facturación)

3. **Archivado de Datos**
   - Implementar políticas de archivado para datos históricos
   - Mantener solo datos recientes/activos en tablas principales

4. **Índices Adicionales**
   - A medida que se establezcan patrones de uso real, añadir índices específicos
   - Monitorear consultas lentas para identificar oportunidades de optimización