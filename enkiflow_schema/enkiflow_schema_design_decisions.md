# Decisiones de Diseño de la Base de Datos de EnkiFlow

Este documento detalla las decisiones clave en el diseño de la base de datos de EnkiFlow, explicando las razones detrás de las estructuras, relaciones y optimizaciones implementadas. Su propósito es servir como guía para desarrolladores que necesiten entender la arquitectura de datos.

## Índices

Los índices han sido estratégicamente ubicados para optimizar las consultas más frecuentes según los patrones de acceso esperados en el sistema:

- Tabla `space_users`: El índice compuesto `(tenant_id, user_id) [unique]` optimiza las consultas de pertenencia a espacios y garantiza que un usuario no pueda pertenecer múltiples veces al mismo tenant, fundamental para la integridad del sistema multi-tenant.

- Tabla `tags`: El índice `(tenant_id, name) [unique]` asegura la unicidad de etiquetas por tenant, optimizando búsquedas por nombre y evitando duplicados en el mismo espacio de trabajo.

- Tabla `taggables`: El índice `(taggable_id, taggable_type)` mejora significativamente el rendimiento de las consultas polimórficas, fundamental para el sistema de etiquetado donde necesitamos recuperar rápidamente todos los tags asociados a un elemento específico.

- Tabla `comments`: El índice `(commentable_id, commentable_type)` optimiza la recuperación de comentarios para cualquier entidad, esencial para mostrar conversaciones en tareas, proyectos u otros elementos comentables.

- Tabla `task_assignees`: El índice `(task_id, user_id) [unique]` garantiza que un usuario no sea asignado múltiples veces a la misma tarea y acelera las consultas de asignaciones por tarea o por usuario.

Estos índices están diseñados para soportar los reportes y dashboards mencionados en las tareas #26 y #27 del backlog, donde se necesita acceso rápido a datos agregados por diferentes dimensiones.

## Claves Foráneas

Las relaciones en el esquema están cuidadosamente diseñadas para reflejar la arquitectura multi-tenant y facilitar consultas eficientes:

- **Relaciones Multi-tenant**: Todas las tablas específicas de negocio contienen un `tenant_id` que referencia a `tenants.id`, implementando el aislamiento de datos por tenant. Esta es una relación uno-a-muchos donde cada registro pertenece a un único tenant.

- **Pertenencia de Usuarios a Espacios**: La tabla `space_users` implementa una relación muchos-a-muchos entre `users` y `tenants`, permitiendo que un usuario pertenezca a múltiples espacios de trabajo con diferentes roles.

- **Jerarquía de Tareas**: La autorreferencia `tasks.parent_id → tasks.id` permite una estructura jerárquica de tareas anidadas de profundidad ilimitada, soportando planificación de proyectos complejos.

- **Asignaciones de Tareas**: La tabla pivot `task_assignees` implementa una relación muchos-a-muchos entre `tasks` y `users`, permitiendo múltiples asignados por tarea y múltiples tareas por usuario.

- **Sistema de Etiquetado Polimórfico**: La tabla `taggables` implementa un sistema de etiquetado flexible aplicable a cualquier entidad del sistema. Los campos `taggable_id` y `taggable_type` permiten relaciones muchos-a-muchos entre `tags` y cualquier modelo etiquetable.

- **Sistema de Comentarios Polimórfico**: Similar al de etiquetas, la tabla `comments` utiliza campos `commentable_id` y `commentable_type` para implementar comentarios en cualquier entidad del sistema.

- **Relaciones Tiempo-Proyecto-Tarea**: La tabla `time_entries` tiene relaciones opcionales a `projects` y `tasks`, permitiendo registrar tiempo a diferentes niveles de granularidad según la necesidad del usuario.

- **Dashboards Personalizados**: La relación uno-a-muchos entre `dashboards` y `dashboard_widgets` permite dashboards altamente personalizables por usuario, fundamental para la funcionalidad de reportes mencionada en la tarea #27.

## Restricciones

Las restricciones en el esquema garantizan la integridad de los datos y evitan estados inválidos:

- **Claves Únicas**: 
  - `users.email [unique]` garantiza la unicidad de emails para autenticación segura.
  - `domains.domain [unique]` asegura que cada dominio esté registrado una sola vez en el sistema.
  - `invitations.token [unique]` evita colisiones en tokens de invitación.
  - `(tenant_id, name) [unique]` en `tags` previene etiquetas duplicadas en el mismo tenant.
  - `(tenant_id, user_id) [unique]` en `space_users` evita múltiples membresías del mismo usuario en un tenant.
  - `(task_id, user_id) [unique]` en `task_assignees` impide asignaciones duplicadas.

- **Valores por Defecto**:
  - `task_states.color [default: '#3498db']` proporciona un color estándar para nuevos estados de tareas.
  - `tasks.priority [default: 'medium']` establece prioridad media como valor predeterminado.
  - `domains.is_primary [default: false]` permite un único dominio primario por tenant.
  - `tenants.plan [default: 'free']` facilita la incorporación de usuarios gratuitos como estrategia de adquisición.
  - `clients.is_active [default: true]` simplifica la creación de nuevos clientes.
  - `dashboards.is_default [default: false]` permite destacar un dashboard por usuario.

- **Campos Nulables**: 
  - `projects.client_id [null]` permite proyectos internos sin cliente asociado.
  - `tasks.parent_id [null]` hace opcional la jerarquía de tareas.
  - `time_entries.end_time [null]` soporta entradas de tiempo en progreso.
  - Los campos de perfil de usuario como `avatar`, `job_title` son nulables para facilitar un onboarding progresivo sin requerir toda la información inicialmente.

## Justificación de diseño

### Sistema Multi-tenant

El diseño multi-tenant está optimizado para escalar horizontalmente y proporcionar aislamiento de datos:

- Se implementa un enfoque de **tenant por ID** almacenado en cada tabla, facilitando consultas eficientes sin necesidad de joins complejos.
- La tabla `tenants` almacena metadatos como `plan` y `trial_ends_at` para gestionar suscripciones y límites.
- La tabla `domains` permite múltiples dominios por tenant con un dominio primario, soportando personalización avanzada.
- El campo `tenant_id` en las tablas de negocio actúa como filtro implícito en todas las consultas, reforzado por el middleware de tenancy mencionado en la tarea #4 del backlog.

Este enfoque soporta perfectamente la arquitectura modular descrita en el ROADMAP y facilita la implementación de las tareas #4-6 relacionadas con multi-tenancy.

### Gestión de Proyectos y Tareas

La estructura de proyectos y tareas está diseñada para máxima flexibilidad y rendimiento:

- **Tareas Jerárquicas**: La auto-referencia en `tasks` permite crear estructuras de descomposición del trabajo (WBS) de cualquier profundidad.
- **Estados Personalizables**: La tabla `task_states` permite a cada tenant definir su propio flujo de trabajo, con campos como `position` para ordenar visualmente estados y `is_completed` para determinar cuándo una tarea se considera finalizada.
- **Sistema de Asignación Múltiple**: La tabla pivot `task_assignees` permite asignar tareas a múltiples usuarios, soportando trabajo colaborativo.
- **Metadatos Extensibles**: Los campos JSON como `recurrence_pattern` en tareas permiten almacenar configuraciones complejas sin necesidad de modificar el esquema de la base de datos.

Esta estructura facilita la implementación de las tareas #10-11 del backlog, proporcionando una base sólida para vistas Kanban, listas y otras visualizaciones.

### Seguimiento de Tiempo

El sistema de seguimiento de tiempo está diseñado para balancear precisión, flexibilidad y rendimiento:

- **Granularidad Flexible**: Las entradas de tiempo pueden asociarse a un proyecto, una tarea específica, o ambos, proporcionando diferentes niveles de detalle.
- **Tiempo Continuo**: El modelo permite entradas en curso (con `end_time` nulo) y completadas, facilitando el registro de tiempo en tiempo real.
- **Categorización**: La tabla `time_categories` permite clasificar el tiempo por tipo de actividad, con el flag `is_billable` para distinguir tiempo facturable.
- **Seguimiento de Actividad**: La tabla `activity_logs` registra detalles específicos de actividad relacionados con cada entrada de tiempo, facilitando la detección automática y análisis detallados.
- **Campos para Facturación**: Atributos como `is_billable` y relaciones con `projects` facilitan la generación de facturas basadas en tiempo registrado.

Este diseño soporta completamente las tareas #16-17 del backlog relacionadas con seguimiento de tiempo y optimiza para los reportes mencionados en la tarea #26.

### Reportes y Dashboards

La estructura para reportes y dashboards se ha diseñado pensando en personalización y rendimiento:

- **Dashboards Personalizados**: Cada usuario puede tener múltiples dashboards con widgets configurables a través de la tabla `dashboard_widgets`.
- **Configuración Flexible**: Los campos JSON como `layout` en dashboards y `settings` en widgets permiten almacenar configuraciones complejas sin necesidad de tablas adicionales.
- **Reportes Guardados**: La tabla `saved_reports` permite guardar configuraciones de reportes incluyendo filtros, columnas y programación de envío.
- **Estructura para Visualizaciones**: Los campos `position` en widgets facilitán interfaces drag-and-drop para personalización visual.

Estos elementos facilitan la implementación de las tareas #26-27 del backlog relacionadas con reportes y análisis de datos.

### Facturación

El sistema de facturación se integra perfectamente con el seguimiento de tiempo:

- **Plantillas Personalizables**: La tabla `invoice_templates` permite múltiples formatos de factura por tenant.
- **Facturación Detallada**: La relación entre `invoice_items` y `projects` permite generar facturas detalladas por proyecto.
- **Cálculos Automáticos**: Los campos para subtotales, impuestos y descuentos permiten cálculos automáticos de totales.
- **Estados de Factura**: El campo `status` en `invoices` facilita el seguimiento del ciclo de vida completo de una factura (borrador, enviada, pagada, etc.).
- **Historial de Pagos**: Los campos `sent_at` y `paid_at` permiten análisis de tiempos de pago y seguimiento de facturas pendientes.

### Extensibilidad

El esquema incluye múltiples mecanismos para extensibilidad futura:

- **Datos Flexibles JSON**: Múltiples tablas utilizan campos JSON para almacenar configuraciones extensibles sin necesidad de migraciones frecuentes.
- **Relaciones Polimórficas**: Los sistemas de etiquetas y comentarios permiten añadir estas funcionalidades a cualquier entidad actual o futura.
- **Integraciones Externas**: Las tablas `integrations` y `webhook_endpoints` proveen infraestructura para conectar con sistemas externos.
- **Metadatos Encriptados**: Los campos sensibles como `credentials` en integraciones y `secret` en webhooks están marcados para encriptación.

Estos elementos soportan la implementación de la tarea #32 sobre escalabilidad y facilitan extensiones futuras del sistema.

## Consideraciones de Escalabilidad

- **Particionamiento**: Las tablas de alto volumen como `time_entries` y `activity_logs` están diseñadas considerando futuro particionamiento por tiempo, según lo mencionado en la tarea #32.
- **Índices Selectivos**: Se han implementado índices específicos en lugar de indexar cada clave foránea, priorizando las consultas más frecuentes.
- **Campos Timestamp**: Todas las tablas incluyen `created_at` y `updated_at` para facilitar caché invalidation y estrategias de sincronización.
- **Soft Deletes**: El campo `deleted_at` en tablas principales permite implementar soft deletes, facilitando recuperación de datos y evitando problemas de integridad referencial.

## Áreas de Mejora Futura

- **Particionamiento Temporal**: Para tablas como `time_entries` y `activity_logs` que crecerán constantemente, implementar particionamiento por fecha mejoraría el rendimiento a largo plazo.
- **Normalización de JSON**: Algunos campos JSON como `notification_preferences` podrían normalizarse en tablas separadas si requieren consultas frecuentes o índices específicos.
- **Índices Adicionales**: A medida que se establezcan patrones de consulta en producción, podrían necesitarse índices adicionales, especialmente para reportes complejos.
- **Vistas Materializadas**: Para reportes frecuentes con datos agregados, considerar vistas materializadas para mejorar rendimiento.

Estas áreas de mejora están alineadas con las funcionalidades post-lanzamiento mencionadas en el backlog, especialmente las relacionadas con enterprise (#53) e inteligencia artificial (#52).