# Estado de Implementación de Tags en Migraciones de Tenant

## Fecha: 27/05/2025

## Implementación Realizada

Se han agregado tags a todas las migraciones de tenant siguiendo las mejores prácticas para organizar las migraciones por módulo funcional:

### Migraciones Etiquetadas

1. **2025_04_30_182918_create_projects_table.php**
   - Tags: `['projects', 'core']`
   - Módulo: Gestión de proyectos

2. **2025_05_02_205810_create_tasks_table.php**
   - Tags: `['tasks', 'core']`
   - Módulo: Gestión de tareas

3. **2025_05_02_210000_create_comments_table.php**
   - Tags: `['tasks', 'comments', 'core']`
   - Módulo: Comentarios en tareas

4. **2025_05_02_210028_create_tags_table.php**
   - Tags: `['tagging', 'core']`
   - Módulo: Sistema de etiquetado

5. **2025_05_02_210038_create_taggables_table.php**
   - Tags: `['tagging', 'core']`
   - Módulo: Relaciones polimórficas de etiquetas

6. **2025_05_05_000505_create_time_categories_table.php**
   - Tags: `['time-tracking', 'core']`
   - Módulo: Categorías de tiempo

7. **2025_05_05_000506_create_time_entries_table.php**
   - Tags: `['time-tracking', 'core']`
   - Módulo: Entradas de tiempo

### Sintaxis Utilizada

Se utilizó la sintaxis de atributos PHP 8+ para definir los tags:

```php
use Stancl\Tenancy\Database\TenantMigration;

#[TenantMigration(tags: ['module-name', 'core'])]
return new class extends Migration
{
    // ...
}
```

## Estado Actual

### ✅ Completado
- Todas las migraciones de tenant tienen tags asignados
- Se utilizó una nomenclatura consistente para los tags
- Se agruparon las migraciones por módulos funcionales

### ⚠️ Limitación Encontrada
- El comando `php artisan tenants:migrate` en la versión actual (v3.9.1) de stancl/tenancy no parece tener soporte para el parámetro `--tag`
- Los tags están definidos en el código pero no se pueden usar para filtrar migraciones selectivamente en este momento

## Recomendaciones

1. **Verificar Actualizaciones**: Revisar si versiones más recientes de stancl/tenancy incluyen soporte para tags en el comando migrate.

2. **Implementación Alternativa**: Si se requiere ejecutar migraciones selectivamente por tags, se podría:
   - Crear un comando Artisan personalizado que lea los tags de los atributos
   - Usar directorios separados para diferentes módulos
   - Implementar un sistema de migración personalizado

3. **Mantener los Tags**: Aunque no se puedan usar actualmente para filtrar, los tags sirven como documentación y organización del código, y estarán listos cuando se implemente el soporte.

## Convenciones de Tags Adoptadas

- **core**: Funcionalidades esenciales del sistema
- **projects**: Relacionado con gestión de proyectos
- **tasks**: Relacionado con gestión de tareas
- **comments**: Sistema de comentarios
- **tagging**: Sistema de etiquetado
- **time-tracking**: Seguimiento de tiempo

## Próximos Pasos

1. Investigar si existe un plugin o extensión para stancl/tenancy que soporte tags
2. Considerar la creación de un comando personalizado si se requiere esta funcionalidad
3. Documentar en el README.md que los tags están preparados para uso futuro