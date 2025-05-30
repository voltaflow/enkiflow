# TASKS.md - EnkiFlow Time Tracking: Arquitectura de Features e Implementación

## 🏗️ Arquitectura del Sistema de Time Tracking

### Core Data Model
```
User
 └─> TimeEntry (histórico)
      ├── project_id → Project
      ├── task_id → Task  
      ├── category_id → TimeCategory
      ├── started_at/ended_at
      ├── duration
      ├── description
      ├── is_billable
      └── tags (JSON)
      
 └─> Timer (activo)
      ├── project_id → Project
      ├── task_id → Task
      ├── started_at
      ├── paused_duration
      └── is_running
```

## 🔄 Flujo de Datos y Dependencias entre Features

### 1. Sistema Central de Time Tracking

#### **Timer → TimeEntry Flow**
```
[Timer Widget]
     ↓
[Timer State Manager] ←→ [Backend Timer]
     ↓                         ↓
[Stop Timer] ──────────> [Create TimeEntry]
                              ↓
                         [TimeEntry List]
                              ↓
                    [Weekly Timesheet View]
                              ↓
                         [Reports/Export]
```

**Dependencias críticas**:
- Timer necesita sincronización multi-tab/multi-device
- TimeEntry es la fuente de verdad para todo el sistema
- Timesheet View DEBE poder crear/editar TimeEntries directamente

### 2. Weekly Timesheet Architecture

#### **Estructura de Datos**
```typescript
interface WeeklyTimesheetData {
  weekStart: Date
  projects: Array<{
    id: number
    name: string
    tasks: Array<{
      id: number
      name: string
      entries: Map<Date, TimeEntry>
      dailyTotals: Map<Date, number>
    }>
    projectTotals: Map<Date, number>
  }>
  dayTotals: Map<Date, number>
  weekTotal: number
}
```

#### **Interacciones del Timesheet**
```
[Weekly Timesheet Grid]
         ↓
    ┌────┴────┐
    ↓         ↓
[Cell Edit] [Quick Add]
    ↓         ↓
[Optimistic Update]
    ↓
[API Batch Update]
    ↓
[Sync Other Views]
```

**El Timesheet DEBE**:
- Ser la vista principal de entrada de datos
- Permitir entrada rápida sin modales
- Auto-guardar cambios
- Sincronizar con Timer Widget
- Actualizar totales en tiempo real

### 3. Sistema de Entrada Manual

#### **Flujos de Entrada**
```
1. Quick Command Entry:
   [Command Bar] → [Parser] → [TimeEntry Create]
   
2. Inline Grid Entry:
   [Empty Cell Click] → [Input Mode] → [TimeEntry Create]
   
3. Template Application:
   [Template Select] → [Pre-fill Data] → [TimeEntry Create]
   
4. Timer Conversion:
   [Running Timer] → [Stop & Edit] → [TimeEntry with Timer Data]
```

**Componentes necesarios**:
- Natural Language Parser
- Template Storage System
- Smart Defaults Manager
- Validation Engine

### 4. Export & Reporting Pipeline

#### **Data Flow para Reports**
```
[TimeEntry Data]
       ↓
[Report Generator]
   ├── Aggregations
   ├── Groupings
   └── Calculations
       ↓
[Report Views]
   ├── Dashboard Charts
   ├── Detailed Tables
   └── Summary Cards
       ↓
[Export Engine]
   ├── CSV Generator
   ├── PDF Builder
   └── API Response
```

**Dependencias**:
- Reports dependen de TimeEntry data integrity
- Export necesita Report Generator
- Charts necesitan datos pre-agregados para performance

## 🎯 Plan de Implementación por Capas

### Capa 1: Data Foundation
**Objetivo**: Asegurar que el modelo de datos soporte todas las features

#### Cambios en TimeEntry Model
```php
// Agregar campos faltantes
- weekly_timesheet_id (para agrupar entries de una semana)
- created_from (timer|manual|import|template)
- parent_entry_id (para entries recurrentes)
- locked (para entries aprobadas)
```

#### Nuevo: WeeklyTimesheet Model
```php
// Representa una semana de trabajo
- user_id
- week_start_date
- status (draft|submitted|approved)
- total_hours
- total_billable_hours
- submitted_at
- approved_at
- approved_by
```

### Capa 2: State Management Architecture

#### Global State Structure
```typescript
interface AppState {
  timer: {
    active: Timer | null
    syncStatus: 'synced' | 'syncing' | 'error'
  }
  
  timesheet: {
    currentWeek: WeeklyTimesheetData
    editingCells: Map<string, EditingState>
    pendingChanges: ChangeQueue
  }
  
  entries: {
    list: TimeEntry[]
    filters: FilterState
    selection: Set<number>
  }
  
  templates: {
    userTemplates: Template[]
    recentEntries: TimeEntry[]
  }
}
```

#### Sincronización Multi-tab
```typescript
// BroadcastChannel para sincronización
const channel = new BroadcastChannel('enkiflow_time_sync')

// Eventos a sincronizar:
- timer:start
- timer:stop
- timer:update
- entry:created
- entry:updated
- entry:deleted
- timesheet:cellUpdate
```

### Capa 3: Component Architecture

#### Jerarquía de Componentes
```
<TimeTrackingApp>
  ├── <CommandBar />              // Global quick entry
  ├── <TimerWidget />              // Floating/fixed timer
  ├── <ViewSwitcher>               // Toggle views
  │    ├── <WeeklyTimesheet />    // Main grid view
  │    ├── <DailyList />          // List view
  │    └── <CalendarView />       // Calendar view
  ├── <ReportsDashboard />         // Analytics
  └── <ExportPanel />              // Export options
```

#### Weekly Timesheet Components
```
<WeeklyTimesheet>
  ├── <WeekSelector />
  ├── <TimesheetGrid>
  │    ├── <ProjectRow>
  │    │    ├── <ProjectHeader />
  │    │    ├── <TaskRows>
  │    │    │    └── <EditableCell />
  │    │    └── <ProjectTotals />
  │    └── <DayTotals />
  ├── <QuickActions />
  └── <TimesheetTotals />
```

### Capa 4: API Design

#### Batch Operations API
```typescript
// Para timesheet updates eficientes
POST /api/time-entries/batch
{
  operations: [
    { action: 'create', data: {...} },
    { action: 'update', id: 123, data: {...} },
    { action: 'delete', id: 456 }
  ]
}

// Para obtener datos de timesheet
GET /api/timesheet/week/{weekStart}
Response: WeeklyTimesheetData
```

#### Smart Suggestions API
```typescript
GET /api/time-entries/suggestions
{
  date: '2024-01-15',
  time_range: '09:00-10:00',
  context: 'gap_fill'
}
```

### Capa 5: Feature Integration Map

#### Timer ↔ Timesheet
- Timer activo se muestra en timesheet como "entry en progreso"
- Detener timer desde timesheet crea entry en la celda correcta
- Iniciar timer desde celda pre-llena proyecto/tarea

#### Timesheet ↔ Reports
- Cambios en timesheet actualizan reports en tiempo real
- Click en report drill-down abre timesheet filtrado
- Export desde reports incluye vista de timesheet

#### Templates ↔ Everything
- Templates accesibles desde: CommandBar, Timesheet, Timer
- Auto-sugerencia de templates basado en contexto
- Templates pueden crear múltiples entries

## 🔧 Implementación Progresiva

### Fase 1: Foundation
1. **Extender modelos** con campos necesarios
2. **Crear WeeklyTimesheet model**
3. **Implementar State Management** base
4. **Setup BroadcastChannel** para sync

### Fase 2: Weekly Timesheet Core
1. **WeeklyTimesheet Component** estructura
2. **EditableCell Component** con auto-save
3. **Batch API endpoints**
4. **Optimistic updates** sistema

### Fase 3: Enhanced Entry
1. **CommandBar Component**
2. **Natural Language Parser**
3. **Template System**
4. **Quick entry shortcuts**

### Fase 4: Reports & Export
1. **Report Generator Service**
2. **Chart Components**
3. **Export Service** (CSV/PDF)
4. **Report Templates**

### Fase 5: Polish & Optimization
1. **Offline support**
2. **Conflict resolution**
3. **Performance optimization**
4. **Mobile responsive**

## 🎯 Success Criteria

### Data Integrity
- No time overlaps
- No data loss on sync
- Accurate calculations

### User Experience
- Single click to start tracking
- Zero-friction time entry
- Instant visual feedback
- No page reloads needed

### System Performance
- Timesheet loads < 1s with 1000 entries
- Real-time sync < 100ms
- Export handles 10k+ entries

## 🚨 Critical Implementation Notes

### State Management
- Use optimistic updates everywhere
- Queue changes for batch sync
- Handle offline gracefully
- Resolve conflicts automatically

### Data Validation
- Validate on client for UX
- Re-validate on server
- Show inline errors
- Prevent invalid states

### Performance
- Virtual scroll for large timesheets
- Debounce cell updates
- Cache calculations
- Lazy load reports

### Security
- Validate permissions per entry
- Audit trail for changes
- Secure export endpoints
- Rate limit API calls