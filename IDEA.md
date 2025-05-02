# Plan de Desarrollo - EnkiFlow

## Visión General

EnkiFlow es una plataforma integral de productividad impulsada por IA, diseñada para optimizar la gestión del tiempo, documentación colaborativa y organización eficiente del trabajo. Inspirado en Enki, dios sumerio de la sabiduría, EnkiFlow combina múltiples herramientas populares en una solución coherente y fluida que se adapta a las necesidades de los usuarios mediante inteligencia artificial contextual.

La plataforma está diseñada como un SaaS moderno que aprovecha arquitecturas multi-tenant robustas para proporcionar aislamiento completo de datos entre clientes, escalabilidad horizontal y una experiencia de usuario personalizable.

## Inspiración e Integración de Conceptos

EnkiFlow reúne y mejora conceptos clave de herramientas líderes en el mercado:

- **Gestión del Conocimiento**: Adopta la filosofía de enlaces bidireccionales y estructuras flexibles de Logseq y Roam Research, enriquecidas con capacidades avanzadas de IA.

- **Seguimiento del Tiempo**: Incorpora la simplicidad de Harvest y los análisis detallados de Toggl, añadiendo predicciones y sugerencias basadas en patrones de productividad.

- **Edición de Contenido**: Combina la modularidad de Notion con la eficiencia de Obsidian, potenciadas por asistencia de redacción contextual mediante IA.

- **Gestión de Proyectos**: Integra la visualización intuitiva de Asana y Trello con automatizaciones inteligentes y predicciones de progreso basadas en datos históricos.

- **Asistencia por IA**: Supera las capacidades de ChatGPT y Copilot al ofrecer asistencia profundamente integrada con el contexto de trabajo del usuario y adaptativa a sus patrones específicos.

## Estado Actual del Proyecto

### Funcionalidades Implementadas (Mayo 2025)

✅ Autenticación básica de usuarios con Laravel Breeze (email/contraseña)  
✅ Arquitectura multitenancy con Stancl/Tenancy (multi-base de datos)  
✅ Sistema de Espacios (tenants) con subdominios personalizados  
✅ Gestión de usuarios por Espacio con roles (admin, miembro)  
✅ Integración con Stripe mediante Laravel Cashier  
✅ Suscripciones por Espacio con facturación por usuario  
✅ Estructura base de la aplicación frontend con React e Inertia.js  
✅ Componentes UI con Tailwind CSS y Radix  
✅ Middleware para validación de tenants activos  

### Funcionalidades en Desarrollo Activo (Q2 2025)

🔄 Modelo de proyectos con relaciones y migrations en tenant  
🔄 Sistema básico de políticas de autorización para Espacios  
🔄 Webhooks de Stripe para manejo de eventos de suscripción  
🔄 Pruebas unitarias para modelos (Space, Project, User)  
🔄 Gestión avanzada de suscripciones (cambio de planes, facturación)  
🔄 Flujo de invitación de usuarios a Espacios  
🔄 Layout responsivo para móviles y tablets  
🔄 Panel de administración para gestión de Espacios  

### Próximas Iteraciones (Q3 2025)

⏱️ Seguimiento de tiempo con cronómetro y registro manual  
⏱️ Editor de bloques para notas y documentación  
⏱️ Gestión visual de proyectos con vistas Kanban y lista  
⏱️ Sistema de tareas y subtareas en el contexto de proyectos  
⏱️ Autenticación avanzada con proveedores sociales y MFA  
⏱️ Integraciones con APIs externas (calendarios, comunicación)  
⏱️ Implementación inicial de asistente IA contextual  

## Arquitectura Técnica

### Principios Arquitectónicos

- **Enfoque API-First**: Todas las funcionalidades están disponibles a través de una API REST bien documentada
- **Arquitectura por Capacidades**: Organización del código por características funcionales en lugar de capas técnicas
- **Separación de Responsabilidades**: Utilización de servicios especializados para cada dominio de negocio
- **CQRS Simplificado**: Separación conceptual entre operaciones de lectura y escritura
- **Desarrollo Guiado por Eventos**: Utilización de eventos para la comunicación entre componentes
- **Diseño Hexagonal**: Separación clara entre dominio, aplicación e infraestructura

### Backend

- **Framework**: Laravel 12.0 con PHP 8.2
- **Estructura**: Aplicación de principios SOLID y patrones de diseño
  - Repositorios para abstracción de acceso a datos
  - Servicios para lógica de negocio compleja
  - Observadores para manejo de eventos
  - DTOs para transferencia de datos entre capas
- **Multi-tenancy**: Stancl/Tenancy 3.9 con enfoque multi-base de datos y subdominios
- **Pagos**: Laravel Cashier 15.6 para integración con Stripe
- **Comunicación**: Inertia.js para interacción cliente-servidor sin duplicación de lógica
- **Seguridad**: Implementación de CORS, sanitización de inputs, y protección contra CSRF
- **Caché**: Utilización estratégica de Redis para datos frecuentemente accedidos
- **Colas**: Sistema robusto de colas para procesamiento asíncrono de tareas pesadas
- **Búsqueda**: Meilisearch para búsqueda rápida y relevante en documentos y proyectos

### Frontend

- **Framework**: React 19.0 con TypeScript para tipado estático
- **Arquitectura**: Componentes funcionales con hooks y patrones de composición
- **Estado**: Gestión mediante Context API y el patrón de reducers para estados complejos
- **Estilos**: Sistema de diseño basado en Tailwind CSS 4.0 con tokens personalizables
- **Componentes UI**: Biblioteca propia basada en Radix UI y Headless UI para accesibilidad
- **Iconografía**: Sistema flexible con Lucide React para consistencia visual
- **Rendimiento**: Implementación de Code Splitting, lazy loading y memorización selectiva
- **Offline**: Capacidades offline mediante Service Workers para funcionalidad básica sin conexión
- **Optimización Móvil**: Diseño responsive con optimizaciones específicas para dispositivos móviles

### Herramientas de Desarrollo

- **Bundler**: Vite 6.0 para compilación y HMR ultrarrápidos
- **Linting**: ESLint con configuraciones personalizadas para mantener consistencia
- **Formateo**: Prettier para código frontend y PHP-CS-Fixer para backend
- **Testing**: Vitest para frontend, PHPUnit para backend, Cypress para E2E
- **CI/CD**: GitHub Actions para integración y despliegue continuos
- **Documentación**: Storybook para componentes y OpenAPI para endpoints
- **Análisis**: Lighthouse para performance y accesibilidad, SonarQube para calidad
- **Asistente IA**: Claude Code como copiloto de ingeniería

## Funcionalidades Detalladas

### Autenticación y Gestión de Usuarios

- Registro con verificación por email y posibilidad de invitación
- Inicio de sesión social con proveedores populares (Google, GitHub, Microsoft)
- Autenticación multifactor configurable (TOTP, SMS, Email)
- Sistema granular de roles y permisos a nivel tenant y proyecto
- Recuperación de contraseñas mediante enlaces temporales seguros
- Gestión de sesiones con detección de dispositivos no reconocidos
- Auditoría completa de acciones de usuario para seguridad y cumplimiento

### Seguimiento del Tiempo

- Cronómetro intuitivo activable con un solo clic o atajo de teclado
- Registro manual con detección inteligente de proyectos y tareas
- Visualización personalizable en gráficos diarios, semanales y mensuales
- Vinculación automática a tareas y proyectos mediante AI contextual
- Detección automática de inactividad y sugerencia de pausas
- Alertas configurables para tiempos excesivos o pausas no registradas
- Informes personalizables por cliente, proyecto, tarea o etiqueta
- Exportación en múltiples formatos para facturación e informes externos

### Diario Personal Basado en Bloques

- Editor modular WYSIWYG con soporte para múltiples tipos de contenido
- Sistema avanzado de bloques para texto, código, tareas, imágenes y embebidos
- Enlaces bidireccionales con previsualización instantánea y sugerencias inteligentes
- Gráfico de conocimiento visual navegable para explorar conexiones
- Búsqueda semántica avanzada por contenido, etiquetas y metadatos
- Resúmenes automáticos y extracciones de conceptos clave mediante IA
- Historial de versiones con diferencias visuales y restauración selectiva
- Sincronización offline con resolución inteligente de conflictos

### Gestión de Proyectos y Clientes

- Creación flexible de proyectos con plantillas personalizables
- Vistas configurables: Kanban, Gantt, calendario, lista y timeline
- Jerarquías ilimitadas de tareas con dependencias y prioridades
- Automatizaciones personalizables para flujos de trabajo recurrentes
- Sistema de campos personalizados para cada tipo de entidad
- Colaboración en tiempo real con asignaciones y menciones
- Dashboards analíticos con métricas clave de rendimiento
- Integración bidireccional con herramientas externas populares

### Asistencia Contextual con IA

- Asistente proactivo que aprende de patrones individuales y grupales
- Generación de documentación automática basada en actividades
- Sugerencias inteligentes de próximas acciones y recursos relevantes
- Detección de cuellos de botella y recomendaciones de optimización
- Análisis predictivo de progreso y fechas de finalización realistas
- Categorización automática de tareas según contexto e historial
- Optimización de distribución de trabajo basada en capacidades y cargas
- Personalización gradual que aprende de las interacciones y preferencias

### Arquitectura Multitenancy

- Espacios completamente aislados por cliente mediante subdominios personalizados
- Bases de datos individuales para máxima seguridad y cumplimiento regulatorio
- Panel administrativo centralizado con visión completa del ecosistema
- Migraciones y actualizaciones gestionables por tenant sin interrupciones
- Sistema flexible de roles y permisos a nivel de organización y equipo
- Personalización de marca y experiencia específica por tenant
- Integraciones configurables por espacio según necesidades específicas

### Facturación y Suscripciones

- Planes escalables con características y límites configurables
- Gestión automática de ciclos de facturación recurrente
- Integración transparente con Stripe para procesamiento seguro
- Portal de cliente para gestión de suscripciones y pagos
- Historial detallado de transacciones y facturas descargables
- Gestión de impuestos según ubicación geográfica
- Webhooks para sincronización con sistemas de contabilidad externos

## Implementación Técnica Detallada

### Arquitectura Multitenancy

EnkiFlow implementa un sistema multitenancy avanzado con las siguientes características:

- **Aislamiento Completo**: Cada tenant (cliente) tiene su propia base de datos dedicada, asegurando máxima seguridad y evitando filtraciones de datos entre organizaciones.

- **Identificación por Subdominio**: Los tenants se identifican mediante subdominios únicos (cliente.enkiflow.com) gestionados automáticamente por el sistema.

- **Middleware Inteligente**: Detecta automáticamente el tenant actual y configura el entorno de ejecución apropiado.

- **Migraciones Separadas**: Sistema dual de migraciones que distingue entre estructuras centrales (usuarios, tenants) y específicas de tenant (proyectos, tareas).

- **Cache por Tenant**: Implementación de prefijos automáticos en cache para evitar colisiones entre datos de diferentes clientes.

- **Trabajos en Cola Aislados**: Procesamiento de trabajos asíncronos en contexto del tenant apropiado.

### Integración con Stripe y Facturación

- **Modelo Customer por Tenant**: Cada tenant está asociado directamente con un cliente en Stripe.

- **Suscripciones Flexibles**: Soporte para diferentes planes, períodos de facturación y cantidades variables.

- **Gestión de Eventos**: Procesamiento robusto de webhooks para manejar cambios de suscripción, pagos, facturas y disputas.

- **Períodos de Gracia**: Manejo elegante de fallos de pago con notificaciones y períodos de gracia configurables.

- **Facturación por Uso**: Capacidad de facturar por consumo adicional más allá del plan base.

### Sistema de Asistencia IA Contextual

- **Procesamiento en Segundo Plano**: Análisis continuo de patrones de actividad sin impacto en rendimiento.

- **Modelos Adaptables**: Uso de diferentes modelos según complejidad de la tarea y contexto.

- **Entrenamiento Específico**: Capacidad de adaptación a terminología y procesos específicos de cada organización.

- **Privacidad por Diseño**: Datos sensibles nunca salen del entorno seguro del tenant.

- **Retroalimentación Continua**: Sistema que aprende de las interacciones del usuario para mejorar precisión.

## Hoja de Ruta del Desarrollo

### Fase 1: Fundamentos e Infraestructura (Q2 2025)
- Implementación robusta de arquitectura multitenancy
- Sistema completo de autenticación y gestión de usuarios
- Estructura básica de seguimiento del tiempo
- Framework para diario personal basado en bloques
- Gestión esencial de proyectos y clientes
- Infraestructura inicial para asistencia IA

### Fase 2: Expansión y Refinamiento (Q3 2025)
- Análisis avanzado de tiempos con visualizaciones detalladas
- Sistema completo de enlaces bidireccionales y grafos de conocimiento
- Vistas personalizables para gestión de proyectos
- Optimización de experiencia móvil y offline
- Mejoras en el sistema de facturación y planes
- Expansión de capacidades IA con personalización

### Fase 3: Inteligencia Avanzada y Colaboración (Q4 2025)
- Implementación de sistema predictivo para estimaciones
- Editor colaborativo en tiempo real con CRDT
- Sistema avanzado de automatizaciones configurables
- Panel analítico integral con métricas personalizables
- Implementación de búsqueda semántica avanzada
- Optimizaciones de rendimiento y escalabilidad

### Fase 4: Integración y Expansión Ecosistema (Q1 2026)
- API pública con documentación exhaustiva y SDK oficial
- Marketplace para extensiones y conectores
- Integraciones profundas con herramientas externas populares
- Características avanzadas de cumplimiento y auditoría
- Expansión internacional con localización completa
- Soluciones específicas por industria (legal, desarrollo, creativos)

## Características Distintivas

- **Integración Perfecta**: Flujo natural entre documentación, seguimiento de tiempo y gestión de proyectos que elimina la fricción entre herramientas.

- **Asistencia IA Contextual**: Inteligencia artificial que verdaderamente comprende el contexto del trabajo y ofrece asistencia proactiva pero no intrusiva.

- **Sistema Flexible de Bloques**: Creación modular de contenido que combina la estructura con la flexibilidad según necesidades específicas.

- **Multitenancy Robusta**: Aislamiento completo de datos por cliente con personalización avanzada manteniendo eficiencia operativa.

- **Métricas Accionables**: Analíticas que no solo muestran datos sino que ofrecen insights accionables para mejorar procesos.

- **Experiencia Multiplataforma**: Funcionamiento consistente en dispositivos de escritorio, móviles y tablets con sincronización perfecta.

- **Arquitectura Evolutiva**: Diseño técnico que permite incorporar nuevas capacidades y tecnologías sin reescrituras masivas.

## Plan de Migración de Node.js a Laravel

### Motivación para la Migración

La versión beta inicial de EnkiFlow fue desarrollada utilizando Node.js para lograr un prototipo funcional rápidamente. Sin embargo, para la versión de producción, se ha tomado la decisión estratégica de migrar el backend a Laravel por las siguientes razones:

- **Ecosistema Maduro**: Laravel ofrece un conjunto de herramientas y paquetes robustos para el desarrollo de aplicaciones SaaS multitenancy.
- **Seguridad Mejorada**: Marco de trabajo con protecciones incorporadas contra vulnerabilidades comunes.
- **ORM Eloquent**: Sistema de mapeo objeto-relacional potente y expresivo que simplifica las interacciones con la base de datos.
- **Escalabilidad**: Mejor soporte para arquitecturas escalables con características como colas, caché y jobs asíncronos.
- **Mantenibilidad**: Estructura estandarizada que facilita la incorporación de nuevos desarrolladores al proyecto.

### Estado Actual de la UI

La interfaz de usuario beta desarrollada en Node.js presenta las siguientes características:

- **Navegación principal**: Estructura de menú lateral con categorías MAIN, PROJECTS y SETTINGS
- **Seguimiento de tiempo**: Vista de timesheet semanal con registro de horas por proyecto y día
- **Selección de proyectos**: Selector desplegable para cambiar rápidamente entre proyectos
- **Acción principal**: Botón prominente "Start Timer" para comenzar el seguimiento
- **Paleta de colores**: Predominante azul corporativo con fondos blancos y grises claros

### Estrategia de Migración

#### Fase 1: Configuración de Infraestructura Laravel (Completado)
- Implementación base de Laravel 12.0 con PHP 8.2
- Configuración de multitenancy con Stancl/Tenancy
- Integración de sistema de autenticación con Laravel Breeze

#### Fase 2: Refactorización del Frontend (En Progreso)
- Migración de componentes React a Inertia.js manteniendo la misma estética
- Conservación de la estructura de layout principal con navegación lateral
- Implementación de componentes reutilizables para mantener consistencia visual
- Adaptación del sistema de rutas para trabajar con el controlador de Laravel

#### Detalles de la Refactorización de la UI Actual

La captura de pantalla de la versión beta en Node.js muestra una interfaz clara y funcional que servirá como base para la implementación en Laravel + Inertia.js. Los elementos clave a preservar incluyen:

1. **Estructura de Navegación**:
   - Sidebar izquierdo con las categorías MAIN, PROJECTS y SETTINGS
   - Menú principal con iconos y texto para cada opción de navegación
   - Avatar y nombre de usuario con indicador de estado "Online"
   - Botón de cierre de sesión en la parte inferior

2. **Barra Superior**:
   - Fecha actual ("Friday, May 2, 2025")
   - Selector de proyecto con indicador de color
   - Botón prominente "Start Timer" con icono de play

3. **Vista de Timeline**:
   - Título principal con icono de calendario
   - Panel de Weekly Timesheet con tabla semanal
   - Filas para cada proyecto y columnas para cada día
   - Totales por proyecto y totales diarios
   - Formato consistente para representación de tiempos

4. **Elementos de Desktop Activity**:
   - Panel con título y descripción
   - Manejo elegante de errores (actualmente muestra "Failed to load desktop activity data")

5. **Resumen de Tiempo**:
   - Panel inferior con el tiempo total del día
   - Formato consistente (actualmente muestra "0h 0m")

#### Fase 3: Migración de Datos y Lógica de Negocio (Próximo)
- Creación de modelos Eloquent equivalentes a las estructuras de datos existentes
- Implementación de migraciones para estructurar la base de datos
- Desarrollo de servicios y repositorios para encapsular la lógica de negocio
- Testing automatizado para garantizar paridad funcional

#### Fase 4: Optimización y Características Extendidas (Planificado)
- Implementación de caché para mejorar rendimiento
- Configuración de colas y jobs para procesos asíncronos
- Integración de websockets para actualizaciones en tiempo real
- Desarrollo de API RESTful completa con documentación OpenAPI

### Implementación de la UI en Laravel

#### Estructura de Componentes

La implementación en Laravel + Inertia.js seguirá un enfoque modular con los siguientes componentes principales:

1. **Layout Principal**
   - `MainLayout.jsx`: Contenedor principal que define la estructura base
   - `Sidebar.jsx`: Barra lateral de navegación con logo, menú principal y proyectos
   - `TopBar.jsx`: Barra superior con fecha, selector de proyectos y botón de temporizador

2. **Componentes de Seguimiento de Tiempo**
   - `TimeSheet.jsx`: Tabla semanal de registro de horas
   - `TimelineView.jsx`: Vista principal con timesheet y actividad
   - `Timer.jsx`: Componente de cronómetro y control de tiempo
   - `ProjectSelector.jsx`: Selector desplegable para proyectos activos

3. **Páginas Principales**
   - `Dashboard.jsx`: Vista de resumen con actividad reciente y métricas
   - `Journal.jsx`: Editor de notas y documentación
   - `Calendar.jsx`: Vista de calendario y programación
   - `Clients.jsx`: Gestión de clientes y organizaciones
   - `Reports.jsx`: Informes y analíticas de tiempo

#### Detalles de Implementación Técnica

La implementación técnica incluirá:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── TimeController.php
│   │   ├── ProjectController.php
│   │   ├── JournalController.php
│   │   └── ...
│   └── Middleware/
│       ├── HandleInertiaRequests.php
│       └── ...
├── Models/
│   ├── User.php
│   ├── Project.php
│   ├── TimeEntry.php
│   └── ...
└── ...

resources/
├── js/
│   ├── Components/
│   │   ├── Layout/
│   │   ├── TimeTracking/
│   │   └── ...
│   ├── Pages/
│   │   ├── Dashboard.jsx
│   │   ├── Timeline.jsx
│   │   └── ...
│   └── app.jsx
└── ...
```

#### Consideraciones de Diseño para la UI en Laravel

- Mantener consistencia con la interfaz actual, preservando:
  - El esquema de colores corporativo (azules, blancos, grises)
  - La estructura de navegación lateral
  - El diseño de timesheet semanal
  - El selector de proyectos y botón de inicio de temporizador
- Mejorar aspectos como:
  - Optimización para dispositivos móviles
  - Feedback visual para acciones del usuario
  - Accesibilidad según estándares WCAG
  - Transiciones y animaciones para mejorar la experiencia

#### Rutas e Interacción

La configuración de rutas en Laravel + Inertia.js:

```php
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Time tracking
    Route::get('/timeline', [TimeController::class, 'index'])->name('timeline');
    Route::post('/time/start', [TimeController::class, 'startTimer'])->name('time.start');
    Route::post('/time/stop', [TimeController::class, 'stopTimer'])->name('time.stop');
    
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    
    // Journal
    Route::get('/journal', [JournalController::class, 'index'])->name('journal');
    Route::get('/journal/demo', [JournalController::class, 'demo'])->name('journal.demo');
    
    // Calendar, clients, organizations...
});
```

#### Ejemplo de Controladores Laravel

```php
// app/Http/Controllers/TimeController.php
public function index()
{
    $startDate = now()->startOfWeek();
    $endDate = now()->endOfWeek();
    
    // Obtener datos de tiempo registrado para la semana actual
    $weekData = TimeEntry::whereBetween('date', [$startDate, $endDate])
        ->where('user_id', auth()->id())
        ->with('project')
        ->get()
        ->groupBy(['project_id', function($item) {
            return strtolower(\Carbon\Carbon::parse($item->date)->format('D'));
        }]);
    
    // Formatear datos para el timesheet
    $formattedData = $this->formatWeekDataForTimesheet($weekData);
    
    // Obtener proyectos para el selector
    $projects = Project::where('user_id', auth()->id())->get();
    
    return Inertia::render('Timeline', [
        'weekData' => $formattedData,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'projects' => $projects,
        'totalTimeToday' => $this->getTotalTimeForToday(),
    ]);
}
```

### Migración del Sistema de Seguimiento de Tiempo

La funcionalidad de seguimiento de tiempo es un componente central de EnkiFlow y requiere especial atención durante la migración a Laravel. La implementación actual en Node.js muestra las siguientes características clave:

#### Análisis de la Interfaz Actual (Versión Node.js)

La vista actual de Timeline incluye:

1. **Weekly Timesheet**:
   - Tabla que muestra proyectos por filas y días de la semana por columnas
   - Formato de tiempo en unidades "3m" (3 minutos) o "7m" (7 minutos)
   - Totales por proyecto y totales diarios
   - Rango de fecha visible (April 28 - May 4, 2025)

2. **Desktop Activity**:
   - Sección para monitoreo automático de actividad (actualmente muestra un error)

3. **Selector de Proyecto**:
   - Dropdown con los proyectos activos ("asdasd" seleccionado)
   - Indicadores visuales (círculos azules) para cada proyecto

4. **Control de Tiempo**:
   - Botón "Start Timer" para iniciar el seguimiento

#### Implementación en Laravel

La migración de esta funcionalidad a Laravel incluirá:

1. **Modelo de Datos**:

```php
// app/Models/TimeEntry.php
class TimeEntry extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'description',
        'start_time',
        'end_time',
        'duration',
        'date',
    ];
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// app/Models/Project.php
class Project extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'color',
        'description',
        'is_active',
    ];
    
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
```

2. **Componente React de TimeSheet**:

```jsx
// resources/js/Components/TimeTracking/TimeSheet.jsx
import React from 'react';

const TimeSheet = ({ weekData, startDate, endDate }) => {
  const daysOfWeek = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
  
  // Componente que reproduce la vista de timesheet semanal
  return (
    <div className="bg-white rounded-lg border border-gray-200 shadow-sm">
      <div className="flex justify-between items-center p-4 border-b border-gray-200">
        <h2 className="text-lg font-semibold">Weekly Timesheet</h2>
        <div className="text-sm text-gray-600">
          {formatDateRange(startDate, endDate)}
        </div>
      </div>
      
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="bg-gray-50 text-left">
              <th className="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                PROJECT
              </th>
              {daysOfWeek.map(day => (
                <th key={day} className="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {day}
                </th>
              ))}
              <th className="py-3 px-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                TOTAL
              </th>
            </tr>
          </thead>
          
          <tbody className="divide-y divide-gray-200">
            {weekData.map((project, index) => (
              <tr key={index} className="hover:bg-gray-50">
                <td className="py-3 px-4">
                  <div className="flex items-center">
                    <div className="w-2 h-2 rounded-full bg-blue-500 mr-2"></div>
                    <span>{project.name}</span>
                  </div>
                </td>
                {daysOfWeek.map(day => (
                  <td key={day} className="py-3 px-4">
                    {project[day.toLowerCase()] || '-'}
                  </td>
                ))}
                <td className="py-3 px-4 font-medium">{calculateTotal(project)}</td>
              </tr>
            ))}
            
            {/* Daily Total Row */}
            <tr className="bg-gray-50 font-medium">
              <td className="py-3 px-4">Daily Total</td>
              {daysOfWeek.map(day => (
                <td key={day} className="py-3 px-4">
                  {dailyTotals[day.toLowerCase()]}
                </td>
              ))}
              <td className="py-3 px-4">{dailyTotals.total}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
};
```

3. **Sistema de Temporizador**:

Este componente será crítico para el seguimiento de tiempo en tiempo real:

```php
// app/Services/TimeTrackingService.php
class TimeTrackingService
{
    public function startTimer(User $user, Project $project, $description = null)
    {
        // Detener cualquier temporizador activo primero
        $this->stopActiveTimers($user);
        
        // Crear nueva entrada de tiempo con hora de inicio
        return TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'description' => $description,
            'start_time' => now(),
            'date' => now()->toDateString(),
        ]);
    }
    
    public function stopTimer(TimeEntry $timeEntry)
    {
        $endTime = now();
        $duration = $endTime->diffInSeconds($timeEntry->start_time);
        
        $timeEntry->update([
            'end_time' => $endTime,
            'duration' => $duration,
        ]);
        
        return $timeEntry;
    }
    
    private function stopActiveTimers(User $user)
    {
        $activeEntries = TimeEntry::where('user_id', $user->id)
            ->whereNull('end_time')
            ->get();
            
        foreach ($activeEntries as $entry) {
            $this->stopTimer($entry);
        }
    }
}
```

4. **Integración Frontend-Backend**:

Mediante eventos en tiempo real para actualizar el estado del temporizador:

```php
// app/Events/TimerStarted.php
class TimerStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $timeEntry;
    
    public function __construct(TimeEntry $timeEntry)
    {
        $this->timeEntry = $timeEntry;
    }
    
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->timeEntry->user_id);
    }
}
```

### Cronograma de Implementación

- **Mayo 2025**: Finalización de la migración base de la UI
- **Junio 2025**: Completar migración de funcionalidades core (autenticación, proyectos, tiempo)
- **Julio 2025**: Implementación de mejoras y optimizaciones
- **Agosto 2025**: Lanzamiento de la versión beta de Laravel
- **Septiembre 2025**: Release de la versión 1.0 con Laravel
- **Octubre 2025**: Desactivación completa de la infraestructura Node.js

### Estrategia de Pruebas y Transición para Usuarios

Para garantizar una migración suave desde Node.js a Laravel, se implementará una estrategia de pruebas y transición gradual:

#### Plan de Pruebas

1. **Pruebas Unitarias**:
   - Cobertura mínima del 80% para todos los modelos, servicios y controladores
   - Utilización de PHPUnit para backend y Jest para componentes React
   - Pruebas específicas para la lógica de negocio del seguimiento de tiempo

2. **Pruebas de Integración**:
   - Validación de flujos completos (creación de cuenta → seguimiento de tiempo → reportes)
   - Pruebas de interacción entre componentes y servicios
   - Validación de comunicación frontend-backend

3. **Pruebas de UI**:
   - Testing automatizado con Cypress para flujos críticos
   - Validación de responsividad en diferentes dispositivos
   - Pruebas de accesibilidad según estándares WCAG

4. **Pruebas de Rendimiento**:
   - Benchmarks comparativos entre la versión Node.js y Laravel
   - Evaluación de tiempos de carga y respuesta
   - Optimización basada en resultados

#### Estrategia de Transición para Usuarios

1. **Fase de Coexistencia**:
   - Mantenimiento de ambos sistemas (Node.js y Laravel) durante un período limitado
   - Sistema de redirección inteligente basado en preferencias del usuario
   - Sincronización de datos entre ambas plataformas

2. **Programa Beta para Usuarios Selectos**:
   - Invitación a un grupo de usuarios actuales para probar la versión Laravel
   - Recopilación estructurada de feedback y priorización de mejoras
   - Incentivos para participantes del programa beta

3. **Migración de Datos**:
   - Desarrollo de scripts de migración para transferir datos de usuario
   - Proceso automatizado con validación de integridad
   - Opción para usuarios de exportar/importar datos manualmente

4. **Comunicación con Usuarios**:
   - Plan detallado de comunicación con anuncios progresivos
   - Documentación clara sobre los cambios y mejoras
   - Tutoriales y webinars para familiarizar a los usuarios con la nueva interfaz

5. **Soporte Post-Migración**:
   - Período extendido de soporte para resolver problemas de transición
   - Canal dedicado para reportar inconvenientes relacionados con la migración
   - Equipo especializado para asistencia en la adaptación

## Conclusión

EnkiFlow representa una nueva generación de plataformas de productividad que unifica capacidades anteriormente dispersas en múltiples herramientas, potenciadas por inteligencia artificial contextual. La combinación de una arquitectura técnica sólida con enfoque en la experiencia de usuario y seguridad multitenancy crea una solución integral que evoluciona con las necesidades de individuos y equipos.

La migración de Node.js a Laravel fortalecerá los fundamentos técnicos de la plataforma, permitiendo un desarrollo más rápido de características avanzadas y garantizando una base sólida para el crecimiento futuro. Esta transición estratégica mantiene la experiencia de usuario ya validada mientras optimiza la infraestructura subyacente.

Con un desarrollo iterativo guiado por feedback de usuarios y las últimas innovaciones tecnológicas, EnkiFlow aspira a convertirse en el centro nervioso digital para profesionales y equipos que buscan maximizar su eficiencia y creatividad.
