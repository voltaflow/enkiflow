# Documento de Diseño Técnico: Módulo de Temporizador de Tiempo Interactivo con Funcionalidades Harvest-Inspired

> **Guía para Desarrolladores Junior**: Este documento ha sido adaptado para facilitar tu comprensión y guiarte en la implementación. Encontrarás explicaciones detalladas, ejemplos prácticos y consejos a lo largo del texto. Las secciones marcadas con 🔰 contienen información especialmente relevante para ti.

## 🔰 Cómo usar este documento

Si eres un desarrollador junior, te recomendamos seguir estos pasos:

1. Lee primero el **Resumen Ejecutivo** para entender el propósito general del módulo
2. Revisa la **Arquitectura General** para comprender cómo se organizan los archivos y componentes
3. Consulta el **Roadmap de Implementación** (sección 10) para ver el plan de trabajo por fases
4. Cuando implementes cada componente, revisa su descripción detallada en la sección correspondiente
5. Utiliza los **ejemplos de código** como referencia para tu implementación
6. Verifica los **Criterios de Aceptación** (sección 8) para asegurarte de que tu implementación cumple con los requisitos

Al final del documento encontrarás un **Glosario de Términos** y **Recursos de Aprendizaje** que te ayudarán a comprender mejor los conceptos técnicos utilizados.

## 1. Resumen Ejecutivo

Este documento detalla el diseño técnico para implementar un módulo de temporizador de tiempo interactivo en la aplicación EnkiFlow. Piensa en este módulo como un "Cronómetro inteligente" que permite a los usuarios registrar el tiempo que dedican a diferentes tareas y proyectos.

🔰 **Tecnologías principales que usaremos**:
- **Laravel 12**: Framework de PHP para el backend (servidor)
- **React 19**: Biblioteca de JavaScript para construir la interfaz de usuario
- **Inertia.js**: Permite usar React con Laravel sin necesidad de crear una API separada
- **Pinia**: Biblioteca para manejar el estado de la aplicación en el frontend

El módulo permitirá a los usuarios:
- Iniciar, pausar y guardar sesiones de tiempo de forma intuitiva y persistente
- Detectar períodos de inactividad para mejorar la exactitud del registro
- Recibir recordatorios automáticos para completar sus hojas de tiempo
- Editar tiempo en vistas Día/Semana para carga masiva eficiente
- Duplicar entradas del día anterior para ahorrar tiempo
- Gestionar un flujo de aprobación y bloqueo de hojas de tiempo

Estas funcionalidades, inspiradas en las mejores prácticas de Harvest (una aplicación popular de seguimiento de tiempo), buscan:
- Reducir tiempos olvidados (mediante alertas de inactividad)
- Aumentar el porcentaje de horas registradas (con recordatorios)
- Facilitar la carga de tiempo histórico (con vista semanal)
- Ofrecer un flujo de aprobación/bloqueo posterior a la presentación

## 2. Arquitectura General

🔰 **Para desarrolladores junior**: Esta sección explica cómo se organizan los archivos y cómo se comunican entre sí. Es importante entender esta estructura antes de comenzar a programar.

### 2.1 Estructura de Carpetas

La siguiente estructura muestra dónde deberás crear cada archivo del proyecto:

```
resources/
├── js/
│   ├── stores/
│   │   └── timeEntryStore.js           # Store de Pinia para gestión de estado
│   ├── components/
│   │   └── TimeTracker/
│   │       ├── Timer.tsx                # Componente principal del cronómetro
│   │       ├── TaskSelector.tsx         # Selector de tareas/proyectos
│   │       ├── DescriptionInput.tsx     # Campo para descripción
│   │       ├── StatusIndicator.tsx      # Indicador visual del estado
│   │       ├── TimesheetDay.tsx         # Vista Día (Harvest-style)
│   │       ├── TimesheetWeek.tsx        # Vista Semana (edición masiva)
│   │       ├── DuplicateDayAction.tsx   # "Copiar día anterior"
│   │       ├── IdlePromptModal.tsx      # Diálogo para tiempo inactivo
│   │       └── ApprovalBanner.tsx       # Estado de aprobación/bloqueo
│   └── composables/
│       ├── useTimer.ts                  # Lógica de manejo de tiempo
│       ├── useLocalStorageBackup.ts     # Persistencia local
│       ├── useIdleDetection.ts          # Detección de inactividad
│       ├── useTimeReminders.ts          # Recordatorios automáticos
│       └── useTimesheetApproval.ts      # Flujo de aprobación
```

🔰 **Explicación para principiantes**:
- **stores/**: Aquí guardaremos el estado global de la aplicación usando Pinia (similar a Redux o Vuex)
- **components/**: Contiene todos los componentes visuales de React que el usuario verá en pantalla
- **composables/**: Contiene funciones reutilizables que encapsulan lógica compleja (similar a los "hooks" en React)

### 2.2 Diagrama de Flujo de Datos

Este diagrama muestra cómo fluye la información entre las diferentes partes de la aplicación:

```
┌─────────────────┐      ┌───────────────────┐      ┌───────────────┐
│ Componentes UI  │ <──> │ timeEntryStore.js │ <──> │ API Backend   │
└─────────────────┘      └───────────────────┘      └───────────────┘
        ↑                         ↑
        │                         │
        ↓                         ↓
┌─────────────────┐      ┌───────────────────┐
│   Composables   │      │   LocalStorage    │
│  - useTimer     │      │   - currentEntry  │
│  - useIdle...   │      │   - failedEntries │
│  - useTime...   │      │   - preferences   │
└─────────────────┘      └───────────────────┘
```

🔰 **¿Cómo interpretar este diagrama?**
1. Los **Componentes UI** (lo que el usuario ve) se comunican con el **timeEntryStore.js** para obtener y actualizar datos
2. El **timeEntryStore.js** se comunica con el **API Backend** (servidor Laravel) para guardar y recuperar datos permanentes
3. Los **Composables** proporcionan funcionalidades reutilizables a los componentes (como el temporizador, detección de inactividad, etc.)
4. El **LocalStorage** guarda datos temporalmente en el navegador del usuario (útil cuando se pierde la conexión a internet)

### 2.3 Navegación Consolidada bajo /time

🔰 **Para desarrolladores junior**: Esta sección explica cómo organizaremos la navegación del módulo de tiempo. Es importante entender que todo el módulo estará bajo una única URL.

Para mejorar la experiencia de usuario y reducir la fragmentación en la navegación, se implementará una estructura de navegación unificada bajo una única ruta:

```
https://prueba.enkiflow.test/time
```

Esta decisión arquitectónica elimina la fragmentación actual donde existen rutas separadas como `/time` y `/time/timesheet`, consolidando toda la funcionalidad de seguimiento de tiempo en un único espacio de trabajo coherente.

#### Estructura de Navegación

La ruta `/time` incluirá un sistema de subpestañas (tabs) que permitirá al usuario navegar entre las diferentes vistas del módulo:

```
/time
  ├── ⏱ Temporizador activo (vista predeterminada)
  ├── 📋 Entradas recientes (Vista Día)
  └── 📅 Vista semanal
```

🔰 **Ejemplo visual**: Así se verá aproximadamente la navegación por pestañas:

```
+-------------------------------------------------------+
|                                                       |
|  [⏱ Temporizador] [📋 Día] [📅 Semana]                |
|  +------------------------------------------------+   |
|  |                                                |   |
|  |  Contenido de la pestaña seleccionada          |   |
|  |                                                |   |
|  +------------------------------------------------+   |
|                                                       |
+-------------------------------------------------------+
```

Esta estructura, similar al enfoque utilizado en Harvest, proporciona:
- **Navegación intuitiva**: El usuario siempre sabe dónde está dentro del módulo de tiempo
- **Contexto persistente**: Al cambiar entre vistas, se mantiene el contexto del período seleccionado
- **Experiencia unificada**: Todas las acciones relacionadas con tiempo ocurren en un único espacio

#### Refactorización de Componentes

🔰 **Para desarrolladores junior**: Esta parte explica los cambios técnicos necesarios para implementar la navegación por pestañas. No te preocupes si no entiendes todo ahora, lo iremos implementando paso a paso.

Para soportar esta consolidación, será necesario:

1. **Fusionar componentes redundantes**:
   - Consolidar `TimesheetDay` y cualquier vista de resumen diario duplicada
   - Unificar la lógica de visualización de entradas recientes

2. **Alinear el estado en timeEntryStore.js**:
   - Modificar la navegación para mantener el estado entre tabs
   - Implementar un sistema de "vista activa" que recuerde la última pestaña seleccionada
   - Asegurar que las acciones en una vista se reflejen inmediatamente en las demás

3. **Crear un componente de navegación por tabs**:
   - Implementar `TimeTrackerTabs.tsx` para gestionar la navegación entre vistas
   - Mantener la URL sincronizada con la pestaña activa mediante query params

🔰 **Consejo práctico**: Cuando implementes este sistema de pestañas, puedes usar la biblioteca `react-tabs` o los componentes de UI de frameworks como Material-UI o Tailwind para simplificar el proceso.

## 3. Componentes

🔰 **Para desarrolladores junior**: Esta sección describe cada componente que necesitarás crear. Cada componente tiene una función específica y se comunica con otros componentes a través de props y eventos.

### 3.1 Timer.tsx

**Descripción**: Este componente muestra un cronómetro visual con botones para iniciar, pausar y detener el tiempo. Una característica importante es que solo permite tener un temporizador activo a la vez (como en Harvest).

🔰 **¿Qué es un componente en React?** Un componente es como una pieza de LEGO que puedes reutilizar en diferentes partes de tu aplicación. Cada componente tiene su propio archivo y puede recibir datos (props) y emitir eventos.

**Props** (datos que recibe el componente):
- `initialTime`: number (opcional) - Tiempo inicial en segundos (útil para continuar un timer existente)
- `isRunning`: boolean - Indica si el temporizador está corriendo actualmente
- `isPaused`: boolean - Indica si el temporizador está en pausa
- `showControls`: boolean (default: true) - Si es true, muestra los botones de control
- `hasActiveTimer`: boolean - Si existe otro timer activo en la aplicación

**Eventos** (acciones que el componente puede comunicar):
- `start` - Se dispara cuando el usuario inicia el temporizador
- `pause` - Se dispara cuando el usuario pausa el temporizador
- `resume` - Se dispara cuando el usuario reanuda el temporizador después de una pausa
- `stop` - Se dispara cuando el usuario detiene el temporizador, emite la duración total en segundos

**Validaciones**:
- Bloquea el botón Start si `hasActiveTimer` es true (solo un timer activo permitido)

🔰 **Ejemplo básico de implementación**:

```tsx
import React, { useState, useEffect } from 'react';

interface TimerProps {
  initialTime?: number;
  isRunning: boolean;
  isPaused: boolean;
  showControls?: boolean;
  hasActiveTimer: boolean;
  onStart: () => void;
  onPause: () => void;
  onResume: () => void;
  onStop: () => void;
}

export default function Timer({
  initialTime = 0,
  isRunning,
  isPaused,
  showControls = true,
  hasActiveTimer,
  onStart,
  onPause,
  onResume,
  onStop
}: TimerProps) {
  const [seconds, setSeconds] = useState(initialTime);

  // Formatear tiempo como HH:MM:SS
  const formatTime = (totalSeconds: number) => {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  };

  // Efecto para actualizar el temporizador cada segundo
  useEffect(() => {
    let interval: number | null = null;

    if (isRunning && !isPaused) {
      interval = window.setInterval(() => {
        setSeconds(prevSeconds => prevSeconds + 1);
      }, 1000);
    }

    return () => {
      if (interval) clearInterval(interval);
    };
  }, [isRunning, isPaused]);

  return (
    <div className="timer">
      <div className="timer-display">{formatTime(seconds)}</div>

      {showControls && (
        <div className="timer-controls">
          {!isRunning && !isPaused ? (
            <button 
              onClick={onStart}
              disabled={hasActiveTimer}
              className="start-button"
            >
              {hasActiveTimer ? 'Otro timer activo' : 'Iniciar'}
            </button>
          ) : isPaused ? (
            <>
              <button onClick={onResume}>Reanudar</button>
              <button onClick={onStop}>Detener</button>
            </>
          ) : (
            <>
              <button onClick={onPause}>Pausar</button>
              <button onClick={onStop}>Detener</button>
            </>
          )}
        </div>
      )}
    </div>
  );
}
```

🔰 **Consejo**: Este componente debe comunicarse con el `timeEntryStore.js` para obtener y actualizar el estado del temporizador. No almacenes el estado del temporizador solo en este componente.

### 3.2 TaskSelector.tsx

**Descripción**: Este componente es un menú desplegable (dropdown) con capacidad de búsqueda que permite al usuario seleccionar rápidamente un proyecto y una tarea para asociarlos con su registro de tiempo.

🔰 **¿Por qué es importante?** Cada entrada de tiempo debe estar asociada a un proyecto y, opcionalmente, a una tarea específica. Este componente facilita esta selección y mejora la experiencia del usuario.

**Props**:
- `projects`: Project[] - Lista de proyectos disponibles para seleccionar
- `tasks`: Task[] - Lista de tareas disponibles para seleccionar
- `selectedProjectId`: number | null - ID del proyecto actualmente seleccionado
- `selectedTaskId`: number | null - ID de la tarea actualmente seleccionada
- `disabled`: boolean - Si es true, el selector aparecerá deshabilitado (no se puede interactuar con él)
- `favorites`: Array<{projectId: number, taskId?: number}> - Lista de combinaciones proyecto-tarea favoritas del usuario

🔰 **Ejemplo de estructura de datos**:

```typescript
// Interfaces para los tipos de datos
interface Project {
  id: number;
  name: string;
  client_id: number;
  client_name: string;
  color?: string;
}

interface Task {
  id: number;
  name: string;
  project_id: number;
  billable: boolean;
}

// Ejemplo de datos
const projects: Project[] = [
  { id: 1, name: 'Rediseño Web', client_id: 101, client_name: 'Acme Inc.', color: '#ff5722' },
  { id: 2, name: 'App Móvil', client_id: 102, client_name: 'TechCorp', color: '#2196f3' }
];

const tasks: Task[] = [
  { id: 1, name: 'Diseño UI', project_id: 1, billable: true },
  { id: 2, name: 'Desarrollo Frontend', project_id: 1, billable: true },
  { id: 3, name: 'Pruebas', project_id: 1, billable: false },
  { id: 4, name: 'Diseño UX', project_id: 2, billable: true }
];
```

🔰 **Funcionalidades clave a implementar**:
1. Filtrado de tareas según el proyecto seleccionado
2. Búsqueda por texto para encontrar rápidamente proyectos/tareas
3. Sección de "Favoritos" para acceso rápido a combinaciones frecuentes
4. Indicadores visuales (como el color del proyecto) para facilitar la identificación

🔰 **Consejo**: Puedes usar bibliotecas como `react-select` o `downshift` para implementar la funcionalidad de búsqueda y selección de manera más sencilla.

### 3.3 TimesheetDay.tsx

**Descripción**: Este componente muestra todas las entradas de tiempo de un día específico, con la posibilidad de añadir nuevas entradas rápidamente. Es similar a la vista diaria de Harvest.

🔰 **¿Qué es un HUD?** HUD significa "Heads-Up Display" y se refiere a una interfaz que muestra información importante de manera clara y accesible, sin que el usuario tenga que buscarla.

**Props**:
- `date`: Date - Fecha del día que se está mostrando
- `entries`: TimeEntry[] - Lista de entradas de tiempo para ese día
- `projects`: Project[] - Lista de proyectos disponibles para seleccionar
- `isLocked`: boolean - Si es true, la hoja de tiempo está bloqueada y no se puede editar

**Features** (características principales):
- Lista editable de entradas del día (añadir, editar, eliminar)
- Totales por proyecto (suma de horas por cada proyecto)
- Acción para duplicar día anterior (copiar todas las entradas del día anterior)
- Indicador de horas totales vs objetivo (por ejemplo, 6.5/8 horas)

🔰 **Ejemplo visual de cómo se vería este componente**:

```
+-------------------------------------------------------+
| Martes, 15 de Agosto, 2023                 Total: 6.5/8h |
+-------------------------------------------------------+
| + Añadir tiempo                                       |
+-------------------------------------------------------+
| Proyecto        | Tarea          | Descripción | Tiempo |
+----------------+----------------+-------------+--------+
| 🔴 Rediseño Web | Diseño UI      | Homepage    | 2.5h   |
| 🔴 Rediseño Web | Desarrollo     | Navbar      | 1.5h   |
| 🔵 App Móvil    | Diseño UX      | Wireframes  | 2.5h   |
+----------------+----------------+-------------+--------+
| Duplicar día anterior                                 |
+-------------------------------------------------------+
```

🔰 **Funcionalidades clave a implementar**:

1. **Visualización de entradas**:
   - Mostrar cada entrada con proyecto, tarea, descripción y tiempo
   - Agrupar por proyecto para mejor visualización
   - Mostrar colores de proyecto para identificación rápida

2. **Edición de entradas**:
   - Permitir editar cualquier campo haciendo clic en él
   - Validar que el tiempo total no exceda 24 horas por día
   - Deshabilitar edición si `isLocked` es true

3. **Acciones rápidas**:
   - Botón "+ Añadir tiempo" para crear una nueva entrada
   - Botón "Duplicar día anterior" para copiar entradas
   - Iconos para eliminar entradas individuales

4. **Resumen y totales**:
   - Mostrar total de horas del día
   - Comparar con el objetivo diario (normalmente 8 horas)
   - Mostrar subtotales por proyecto

🔰 **Consejo**: Este componente debe comunicarse con el `timeEntryStore.js` para obtener y actualizar las entradas de tiempo. Usa la función `duplicatePreviousDay` del store para implementar la funcionalidad de duplicar día.

### 3.4 TimesheetWeek.tsx

**Descripción**: Este componente muestra una vista semanal en formato de tabla que permite al usuario ingresar y editar horas de manera masiva para toda la semana.

🔰 **¿Por qué es importante?** La vista semanal facilita enormemente la carga de tiempo histórico y permite ver patrones de trabajo a lo largo de la semana.

**Props**:
- `weekStart`: Date - Fecha del primer día de la semana (normalmente lunes)
- `entries`: TimeEntry[] - Lista de todas las entradas de tiempo para esa semana
- `projects`: Project[] - Lista de proyectos disponibles para seleccionar
- `isLocked`: boolean - Si es true, la hoja de tiempo está bloqueada y no se puede editar

**Features** (características principales):
- Grid editable con días como columnas (lunes a domingo)
- Proyectos/tareas como filas (agrupados por proyecto)
- Totales por día y proyecto (sumas automáticas)
- Capacidad de edición masiva (bulk edit)

🔰 **Ejemplo visual de cómo se vería este componente**:

```
+-----------------------------------------------------------------------+
| Semana: 14-20 Agosto, 2023                           Total: 32.5/40h  |
+-----------------------------------------------------------------------+
| Proyecto/Tarea  | Lun | Mar | Mié | Jue | Vie | Sáb | Dom | Total     |
+-----------------+-----+-----+-----+-----+-----+-----+-----+-----------+
| 🔴 Rediseño Web |     |     |     |     |     |     |     | 16.0h     |
|  - Diseño UI    | 2.0 | 2.5 | 3.0 | 1.5 | 2.0 | 0.0 | 0.0 | 11.0h     |
|  - Desarrollo   | 0.0 | 1.5 | 0.0 | 2.0 | 1.5 | 0.0 | 0.0 | 5.0h      |
+-----------------+-----+-----+-----+-----+-----+-----+-----+-----------+
| 🔵 App Móvil    |     |     |     |     |     |     |     | 16.5h     |
|  - Diseño UX    | 6.0 | 2.5 | 5.0 | 3.0 | 0.0 | 0.0 | 0.0 | 16.5h     |
+-----------------+-----+-----+-----+-----+-----+-----+-----+-----------+
| Total por día   | 8.0 | 6.5 | 8.0 | 6.5 | 3.5 | 0.0 | 0.0 | 32.5h     |
+-----------------+-----+-----+-----+-----+-----+-----+-----+-----------+
| [Enviar semana para aprobación]                                       |
+-----------------------------------------------------------------------+
```

🔰 **Funcionalidades clave a implementar**:

1. **Estructura de tabla**:
   - Encabezados de columna con días de la semana
   - Filas agrupadas por proyecto y tarea
   - Celdas editables para ingresar horas
   - Filas y columnas de totales

2. **Edición eficiente**:
   - Navegación con teclado (Tab/Enter) entre celdas
   - Validación de entrada (solo números y decimales)
   - Actualización automática de totales al editar
   - Deshabilitar edición si `isLocked` es true

3. **Edición masiva**:
   - Selección múltiple de celdas
   - Opción para aplicar el mismo valor a varias celdas
   - Copiar/pegar valores entre celdas

4. **Navegación entre semanas**:
   - Botones para avanzar/retroceder semanas
   - Selector de fecha para saltar a una semana específica
   - Mantener el contexto al cambiar de semana

🔰 **Consejo**: Para implementar la tabla editable, puedes usar bibliotecas como `react-data-grid` o `ag-grid-react` que facilitan la creación de grids editables con funcionalidades avanzadas.

### 3.5 IdlePromptModal.tsx

**Descripción**: Este componente es una ventana modal (diálogo emergente) que aparece cuando el sistema detecta que el usuario ha estado inactivo durante un período de tiempo, preguntándole si desea mantener o descartar ese tiempo de inactividad en su registro.

🔰 **¿Qué es un Modal?** Un modal es una ventana que aparece encima del contenido principal y bloquea la interacción con el resto de la aplicación hasta que el usuario tome una decisión.

🔰 **¿Por qué es importante?** Esta funcionalidad ayuda a mejorar la precisión del registro de tiempo, evitando que se contabilicen períodos en los que el usuario no estaba realmente trabajando (por ejemplo, cuando salió a almorzar pero dejó el temporizador corriendo).

**Props**:
- `idleMinutes`: number - Cantidad de minutos de inactividad detectados
- `onKeepTime`: () => void - Función que se ejecuta cuando el usuario decide mantener el tiempo de inactividad
- `onDiscardTime`: (minutes: number) => void - Función que se ejecuta cuando el usuario decide descartar el tiempo, recibe como parámetro los minutos a descartar

🔰 **Ejemplo visual de cómo se vería este componente**:

```
+-----------------------------------------------------------+
|                                                           |
|  ⚠️ Inactividad Detectada                                 |
|                                                           |
|  Hemos detectado que has estado inactivo durante          |
|  15 minutos.                                              |
|                                                           |
|  ¿Qué deseas hacer con este tiempo?                       |
|                                                           |
|  [Mantener tiempo]        [Descartar tiempo inactivo]     |
|                                                           |
+-----------------------------------------------------------+
```

🔰 **Funcionalidades clave a implementar**:

1. **Presentación clara**:
   - Mostrar claramente cuánto tiempo de inactividad se ha detectado
   - Explicar las opciones disponibles de manera sencilla
   - Usar iconos o colores para destacar la importancia

2. **Opciones de usuario**:
   - Botón para mantener todo el tiempo (incluyendo el inactivo)
   - Botón para descartar el tiempo inactivo
   - Opcionalmente, un campo para ajustar manualmente cuánto tiempo descartar

3. **Comportamiento del modal**:
   - Bloquear la interacción con el resto de la aplicación hasta que se tome una decisión
   - No debe poder cerrarse sin elegir una opción (para evitar registros incorrectos)
   - Debe ser responsive y funcionar bien en dispositivos móviles

🔰 **Ejemplo básico de implementación**:

```tsx
import React from 'react';

interface IdlePromptModalProps {
  idleMinutes: number;
  onKeepTime: () => void;
  onDiscardTime: (minutes: number) => void;
}

export default function IdlePromptModal({ 
  idleMinutes, 
  onKeepTime, 
  onDiscardTime 
}: IdlePromptModalProps) {
  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <h2>⚠️ Inactividad Detectada</h2>

        <p>
          Hemos detectado que has estado inactivo durante 
          <strong> {idleMinutes} minutos</strong>.
        </p>

        <p>¿Qué deseas hacer con este tiempo?</p>

        <div className="modal-actions">
          <button 
            className="btn-secondary"
            onClick={onKeepTime}
          >
            Mantener tiempo
          </button>

          <button 
            className="btn-primary"
            onClick={() => onDiscardTime(idleMinutes)}
          >
            Descartar tiempo inactivo
          </button>
        </div>
      </div>
    </div>
  );
}
```

🔰 **Consejo**: Este componente debe aparecer automáticamente cuando el hook `useIdleDetection` detecta inactividad. Asegúrate de que el modal sea visible y llamativo para que el usuario no lo ignore accidentalmente.

### 3.6 ApprovalBanner.tsx

**Descripción**: Este componente muestra un banner (barra informativa) en la parte superior de la hoja de tiempo que indica su estado actual en el flujo de aprobación.

🔰 **¿Qué es un Banner?** Un banner es una sección destacada, generalmente en la parte superior de una página, que muestra información importante o alertas al usuario.

🔰 **¿Por qué es importante?** El usuario necesita saber claramente en qué estado se encuentra su hoja de tiempo: si está en borrador, enviada para revisión, aprobada o bloqueada. Esto ayuda a evitar confusiones y facilita el seguimiento del proceso.

**Props**:
- `isSubmitted`: boolean - Si es true, la hoja de tiempo ha sido enviada para revisión
- `isApproved`: boolean - Si es true, la hoja de tiempo ha sido aprobada
- `isLocked`: boolean - Si es true, la hoja de tiempo está bloqueada y no se puede editar
- `submittedAt`: Date | null - Fecha y hora en que se envió la hoja de tiempo para revisión
- `approvedBy`: User | null - Información del usuario que aprobó la hoja de tiempo

🔰 **Ejemplo visual de cómo se vería este componente en diferentes estados**:

```
// Estado: Borrador (no enviado)
+-----------------------------------------------------------------------+
| 📝 Borrador - Esta hoja de tiempo no ha sido enviada para aprobación  |
| [Enviar para aprobación]                                              |
+-----------------------------------------------------------------------+

// Estado: Enviado para revisión
+-----------------------------------------------------------------------+
| 🕒 Enviado para revisión el 15/08/2023 - Esperando aprobación         |
+-----------------------------------------------------------------------+

// Estado: Aprobado
+-----------------------------------------------------------------------+
| ✅ Aprobado por Juan Pérez el 16/08/2023                              |
+-----------------------------------------------------------------------+

// Estado: Bloqueado
+-----------------------------------------------------------------------+
| 🔒 Esta hoja de tiempo está bloqueada y no puede ser modificada       |
+-----------------------------------------------------------------------+
```

🔰 **Funcionalidades clave a implementar**:

1. **Visualización clara del estado**:
   - Usar colores e iconos distintos para cada estado (borrador, enviado, aprobado, bloqueado)
   - Mostrar información relevante según el estado (fecha de envío, quién aprobó, etc.)
   - Asegurar que el banner sea visible pero no intrusivo

2. **Acciones contextuales**:
   - En estado borrador: mostrar botón "Enviar para aprobación"
   - En estado enviado: opcionalmente mostrar botón "Cancelar envío" (si el usuario tiene permisos)
   - En estado aprobado/bloqueado: no mostrar acciones de edición

3. **Información temporal**:
   - Mostrar fechas en formato amigable ("hace 2 días" en vez de fechas exactas)
   - Incluir información sobre plazos si es relevante ("Debe ser aprobado antes del 20/08/2023")

🔰 **Ejemplo básico de implementación**:

```tsx
import React from 'react';
import { formatDistance } from 'date-fns';
import { es } from 'date-fns/locale';

interface User {
  id: number;
  name: string;
  email: string;
}

interface ApprovalBannerProps {
  isSubmitted: boolean;
  isApproved: boolean;
  isLocked: boolean;
  submittedAt: Date | null;
  approvedBy: User | null;
  onSubmit?: () => void;
}

export default function ApprovalBanner({
  isSubmitted,
  isApproved,
  isLocked,
  submittedAt,
  approvedBy,
  onSubmit
}: ApprovalBannerProps) {
  // Función para formatear fechas de forma amigable
  const formatDate = (date: Date) => {
    return formatDistance(date, new Date(), { 
      addSuffix: true,
      locale: es 
    });
  };

  // Determinar el estado actual para mostrar el banner adecuado
  if (isLocked) {
    return (
      <div className="banner banner-locked">
        <span className="icon">🔒</span>
        <span className="message">
          Esta hoja de tiempo está bloqueada y no puede ser modificada
        </span>
      </div>
    );
  }

  if (isApproved) {
    return (
      <div className="banner banner-approved">
        <span className="icon">✅</span>
        <span className="message">
          Aprobado por {approvedBy?.name || 'un administrador'} 
          {submittedAt && ` ${formatDate(submittedAt)}`}
        </span>
      </div>
    );
  }

  if (isSubmitted) {
    return (
      <div className="banner banner-submitted">
        <span className="icon">🕒</span>
        <span className="message">
          Enviado para revisión 
          {submittedAt && ` ${formatDate(submittedAt)}`} - 
          Esperando aprobación
        </span>
      </div>
    );
  }

  // Estado por defecto: borrador
  return (
    <div className="banner banner-draft">
      <span className="icon">📝</span>
      <span className="message">
        Borrador - Esta hoja de tiempo no ha sido enviada para aprobación
      </span>
      {onSubmit && (
        <button 
          className="btn-primary"
          onClick={onSubmit}
        >
          Enviar para aprobación
        </button>
      )}
    </div>
  );
}
```

🔰 **Consejo**: Este componente debe ser visible en todas las vistas relacionadas con hojas de tiempo (día, semana) para que el usuario siempre sepa el estado actual. Usa colores consistentes para cada estado (por ejemplo, amarillo para borrador, azul para enviado, verde para aprobado, rojo para bloqueado).

## 4. Store de Pinia (timeEntryStore.js)

🔰 **Para desarrolladores junior**: Esta sección describe cómo se gestiona el estado global de la aplicación usando Pinia. El "store" es como una base de datos en el frontend que almacena todos los datos que necesitan compartirse entre componentes.

🔰 **¿Qué es Pinia?** Pinia es una biblioteca de gestión de estado para Vue.js (similar a Redux para React). Permite centralizar el estado de la aplicación y proporciona métodos para modificarlo de manera controlada.

### 4.1 Estado Ampliado

El estado del store contiene toda la información que necesitamos para el módulo de tiempo. Piensa en esto como la "base de datos" del frontend.

```javascript
const state = () => ({
  // Estado original - Información sobre la entrada de tiempo actual
  currentEntry: {
    id: null,                  // ID único de la entrada (null si es nueva)
    description: '',           // Descripción del trabajo realizado
    projectId: null,           // ID del proyecto seleccionado
    taskId: null,              // ID de la tarea seleccionada
    startTime: null,           // Cuándo se inició el temporizador
    endTime: null,             // Cuándo se detuvo el temporizador
    duration: 0,               // Duración en segundos
    isRunning: false,          // Si el temporizador está activo
    isPaused: false,           // Si el temporizador está en pausa
    pausedAt: null,            // Cuándo se pausó el temporizador
    totalPausedTime: 0,        // Tiempo total en pausa (para cálculos)
    lastSyncedAt: null         // Última sincronización con el servidor
  },
  recentEntries: [],           // Lista de entradas recientes
  isLoading: false,            // Indicador de carga (para mostrar spinners)
  error: null,                 // Mensaje de error si algo falla

  // Nuevas propiedades
  activeTimers: [],            // Todos los timers activos (para validación)
  reminders: {
    dailySent: false,          // Si ya se envió el recordatorio diario
    lastSentAt: null           // Cuándo se envió el último recordatorio
  },
  idleDetection: {
    threshold: 600,            // 10 minutos en segundos
    lastActivity: null,        // Última vez que se detectó actividad
    idleStartedAt: null        // Cuándo comenzó el período de inactividad
  },
  approval: {
    isSubmitted: false,        // Si la hoja de tiempo fue enviada
    submittedAt: null,         // Cuándo se envió
    isApproved: false,         // Si fue aprobada
    approvedAt: null,          // Cuándo se aprobó
    approvedBy: null,          // Quién la aprobó
    isLocked: false,           // Si está bloqueada para edición
    lockedAt: null             // Cuándo se bloqueó
  },
  preferences: {
    dailyHoursGoal: 8,         // Objetivo diario de horas (por defecto 8)
    reminderTime: '17:00',     // Hora para enviar recordatorios
    enableIdleDetection: true, // Si la detección de inactividad está activada
    enableReminders: true      // Si los recordatorios están activados
  }
})
```

🔰 **Consejo**: Cuando trabajes con este store, recuerda que todos los componentes que lo utilicen tendrán acceso a estos datos. Es importante mantener la consistencia y no modificar el estado directamente, sino a través de las acciones y mutaciones definidas.

### 4.2 Getters Ampliados

🔰 **Para desarrolladores junior**: Los "getters" son como propiedades calculadas que derivan información del estado. Te permiten acceder a datos procesados o filtrados sin modificar el estado original.

```javascript
const getters = {
  // Getters originales...

  // Nuevos getters

  // Verifica si hay algún temporizador activo actualmente
  hasActiveTimer: (state) => 
    state.activeTimers.some(timer => timer.isRunning),

  // Determina si se puede iniciar un nuevo temporizador
  // (solo si no hay otro activo o si se permiten múltiples)
  canStartNewTimer: (state, getters) => 
    !getters.hasActiveTimer || state.preferences.allowMultipleTimers,

  // Calcula el total de horas registradas hoy
  todaysTotalHours: (state) => {
    const today = new Date().toDateString();
    return state.recentEntries
      .filter(entry => new Date(entry.started_at).toDateString() === today)
      .reduce((sum, entry) => sum + entry.duration, 0) / 3600; // Convertir segundos a horas
  },

  // Determina si se debe enviar un recordatorio al usuario
  needsReminder: (state, getters) => {
    // No enviar si los recordatorios están desactivados
    if (!state.preferences.enableReminders) return false;
    // No enviar si ya se envió hoy
    if (state.reminders.dailySent) return false;

    // Verificar si ya pasó la hora configurada para el recordatorio
    const now = new Date();
    const reminderTime = new Date();
    const [hours, minutes] = state.preferences.reminderTime.split(':');
    reminderTime.setHours(hours, minutes, 0);

    // Enviar recordatorio si ya pasó la hora Y no se ha alcanzado el objetivo diario
    return now >= reminderTime && 
           getters.todaysTotalHours < state.preferences.dailyHoursGoal;
  },

  // Verifica si la hoja de tiempo se puede editar
  canEditTimesheet: (state) => !state.approval.isLocked,

  // Devuelve el estado actual de la hoja de tiempo
  timesheetStatus: (state) => {
    if (state.approval.isLocked) return 'locked';      // Bloqueada
    if (state.approval.isApproved) return 'approved';  // Aprobada
    if (state.approval.isSubmitted) return 'submitted'; // Enviada
    return 'draft';                                    // Borrador
  }
}
```

🔰 **¿Cómo usar los getters?** En tus componentes, puedes acceder a estos getters como si fueran propiedades del store:

```javascript
// En un componente Vue/React con Pinia
import { useTimeEntryStore } from '@/stores/timeEntryStore';

export default {
  setup() {
    const store = useTimeEntryStore();

    // Acceder a los getters
    console.log('Horas registradas hoy:', store.todaysTotalHours);
    console.log('¿Puedo iniciar un nuevo timer?', store.canStartNewTimer);

    // Usar getters para condicionar la UI
    const showReminderBanner = store.needsReminder;

    return {
      // Exponer getters al template
      totalHours: store.todaysTotalHours,
      timesheetStatus: store.timesheetStatus,
      canEdit: store.canEditTimesheet
    };
  }
}
```

### 4.3 Acciones Ampliadas

🔰 **Para desarrolladores junior**: Las "acciones" son funciones que modifican el estado del store. A diferencia de las mutaciones (que son síncronas), las acciones pueden ser asíncronas y realizar operaciones como llamadas a API antes de modificar el estado.

```javascript
const actions = {
  // Acciones originales...

  // ===== GESTIÓN DEL TEMPORIZADOR =====

  /**
   * Inicia un nuevo temporizador, asegurando que solo haya uno activo a la vez
   */
  async startTimer({ commit, state, getters }) {
    // Verificar si podemos iniciar un nuevo timer
    if (!getters.canStartNewTimer) {
      // Si no podemos, detener el timer activo automáticamente
      const activeTimer = state.activeTimers.find(t => t.isRunning);
      if (activeTimer) {
        await this.stopTimer(activeTimer.id);
      }
    }

    // Continuar con lógica original de startTimer...
    // (crear nueva entrada, establecer tiempo de inicio, etc.)
  },

  // ===== MANEJO DE INACTIVIDAD =====

  /**
   * Maneja la decisión del usuario cuando se detecta inactividad
   * @param {Object} options - Opciones
   * @param {boolean} options.keepTime - Si es true, mantiene el tiempo inactivo
   * @param {number} options.discardMinutes - Minutos a descartar si keepTime es false
   */
  handleIdleExceeded({ commit, state }, { keepTime, discardMinutes }) {
    // Solo procesar si hay un timer activo
    if (!state.currentEntry.isRunning) return;

    if (keepTime) {
      // Opción 1: Mantener el tiempo, solo registrar que hubo actividad
      commit('updateLastActivity');
    } else {
      // Opción 2: Descartar el tiempo inactivo
      const discardSeconds = discardMinutes * 60;
      // Reducir la duración actual
      commit('adjustCurrentDuration', -discardSeconds);
      // Registrar como tiempo pausado para cálculos correctos
      commit('addToPausedTime', discardSeconds);
    }

    // Guardar en localStorage por si se cierra el navegador
    useLocalStorageBackup.saveCurrentEntry(state.currentEntry);
  },

  // ===== RECORDATORIOS =====

  /**
   * Envía un recordatorio diario si es necesario
   */
  async sendDailyReminder({ commit, state, getters }) {
    // Verificar si se necesita enviar recordatorio (usando el getter)
    if (!getters.needsReminder) return;

    try {
      // 1. Enviar notificación push en el navegador (si está permitido)
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('EnkiFlow - Registro de Tiempo', {
          body: `Has registrado ${getters.todaysTotalHours.toFixed(1)} de ${state.preferences.dailyHoursGoal} horas hoy.`,
          icon: '/logo.png'
        });
      }

      // 2. También enviar email vía API del backend
      await axios.post('/api/reminders/daily', {
        hours_tracked: getters.todaysTotalHours,
        hours_goal: state.preferences.dailyHoursGoal
      });

      // 3. Marcar que ya se envió el recordatorio hoy
      commit('markReminderSent');
    } catch (error) {
      console.error('Error enviando recordatorio:', error);
    }
  },

  // ===== FUNCIONALIDADES DE PRODUCTIVIDAD =====

  /**
   * Duplica las entradas de un día a otro (útil para trabajo repetitivo)
   * @param {string} fromDate - Fecha de origen en formato YYYY-MM-DD
   * @param {string} toDate - Fecha de destino en formato YYYY-MM-DD
   * @returns {Array} - Las entradas duplicadas
   */
  async duplicatePreviousDay({ commit, state }, { fromDate, toDate }) {
    try {
      // Indicar que estamos cargando (para mostrar spinner)
      commit('setLoading', true);

      // Llamar a la API para duplicar el día
      const response = await axios.post('/api/time-entries/duplicate-day', {
        from_date: fromDate,
        to_date: toDate
      });

      // Agregar las entradas duplicadas al estado
      response.data.entries.forEach(entry => {
        commit('addRecentEntry', entry);
      });

      return response.data.entries;
    } catch (error) {
      // Manejar errores
      commit('setError', error.response?.data?.message || 'Error al duplicar día');
      throw error;
    } finally {
      // Siempre desactivar el indicador de carga
      commit('setLoading', false);
    }
  },

  // ===== FLUJO DE APROBACIÓN =====

  /**
   * Envía una hoja de tiempo para aprobación
   * @param {Object} options - Opciones
   * @param {Date} options.weekStart - Fecha de inicio de la semana
   * @param {Date} options.weekEnd - Fecha de fin de la semana
   */
  async submitTimesheet({ commit, state }, { weekStart, weekEnd }) {
    // Evitar envíos duplicados
    if (state.approval.isSubmitted) return;

    try {
      commit('setLoading', true);

      // Enviar solicitud al servidor
      const response = await axios.post('/api/timesheets/submit', {
        week_start: weekStart,
        week_end: weekEnd
      });

      // Actualizar el estado de aprobación
      commit('setApprovalStatus', {
        isSubmitted: true,
        submittedAt: new Date()
      });

      return response.data;
    } catch (error) {
      commit('setError', error.response?.data?.message || 'Error al enviar hoja de tiempo');
      throw error;
    } finally {
      commit('setLoading', false);
    }
  },

  /**
   * Aprueba una hoja de tiempo (acción para managers/supervisores)
   */
  async approveTimesheet({ commit }, { userId, weekStart }) {
    try {
      const response = await axios.post('/api/timesheets/approve', {
        user_id: userId,
        week_start: weekStart
      });

      // Actualizar estado a aprobado
      commit('setApprovalStatus', {
        isApproved: true,
        approvedAt: new Date(),
        approvedBy: response.data.approved_by
      });

      return response.data;
    } catch (error) {
      commit('setError', error.response?.data?.message);
      throw error;
    }
  },

  /**
   * Bloquea una hoja de tiempo para evitar ediciones
   */
  async lockTimesheet({ commit }) {
    commit('setApprovalStatus', {
      isLocked: true,
      lockedAt: new Date()
    });
  },

  // ===== PREFERENCIAS DE USUARIO =====

  /**
   * Carga las preferencias del usuario desde el servidor
   */
  async loadUserPreferences({ commit }) {
    try {
      const response = await axios.get('/api/user/preferences');
      commit('setPreferences', response.data.preferences);
    } catch (error) {
      console.error('Error cargando preferencias:', error);
    }
  }
}
```

🔰 **¿Cómo usar las acciones?** A diferencia de los getters, las acciones se invocan como métodos:

```javascript
// En un componente Vue/React con Pinia
import { useTimeEntryStore } from '@/stores/timeEntryStore';

export default {
  setup() {
    const store = useTimeEntryStore();

    // Función para manejar el botón "Duplicar día anterior"
    const handleDuplicateDay = async () => {
      try {
        // Llamar a la acción del store
        await store.duplicatePreviousDay({
          fromDate: '2023-08-14', // Lunes
          toDate: '2023-08-15'    // Martes
        });

        // Mostrar mensaje de éxito
        alert('¡Día duplicado con éxito!');
      } catch (error) {
        // Manejar errores
        alert('Error al duplicar día: ' + error.message);
      }
    };

    // Función para enviar hoja de tiempo
    const submitWeek = async () => {
      const weekStart = new Date('2023-08-14');
      const weekEnd = new Date('2023-08-20');

      await store.submitTimesheet({ weekStart, weekEnd });
    };

    return {
      handleDuplicateDay,
      submitWeek
    };
  }
}
```

🔰 **Consejo**: Las acciones son el lugar ideal para implementar la lógica de negocio compleja y las comunicaciones con el servidor. Mantén tus componentes simples delegando esta lógica a las acciones del store.

## 5. Composables

🔰 **Para desarrolladores junior**: Los "composables" son funciones reutilizables que encapsulan lógica compleja. Son similares a los "hooks" en React, pero para Vue. Te permiten extraer y reutilizar lógica entre componentes.

### 5.1 useTimer.ts (actualizado)

Este composable maneja toda la lógica relacionada con el temporizador: iniciar, pausar, reanudar, detener y formatear el tiempo.

🔰 **¿Por qué usar composables?** Permiten separar la lógica de la interfaz de usuario. Por ejemplo, este composable maneja toda la lógica del temporizador, mientras que el componente `Timer.tsx` solo se preocupa por mostrar la interfaz.

```typescript
import { ref, computed, onUnmounted } from 'vue';

/**
 * Hook para manejar un temporizador con funcionalidades de inicio, pausa, reanudación y detención
 * @param initialSeconds - Segundos iniciales para el temporizador (por defecto: 0)
 */
export function useTimer(initialSeconds = 0) {
  // Estado del temporizador
  const seconds = ref(initialSeconds);        // Segundos transcurridos
  const isRunning = ref(false);               // Si el temporizador está corriendo
  const isPaused = ref(false);                // Si el temporizador está pausado
  const startTime = ref<Date | null>(null);   // Cuándo se inició el temporizador
  const pausedAt = ref<Date | null>(null);    // Cuándo se pausó el temporizador
  const totalPausedTime = ref(0);             // Tiempo total en pausa (en segundos)
  let timerInterval: number | null = null;    // ID del intervalo para poder limpiarlo después

  /**
   * Formatea los segundos en formato HH:MM:SS
   * Ejemplo: 3665 segundos -> "01:01:05"
   */
  const formattedTime = computed(() => {
    const hrs = Math.floor(seconds.value / 3600);
    const mins = Math.floor((seconds.value % 3600) / 60);
    const secs = seconds.value % 60;
    return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  });

  /**
   * Inicia el temporizador
   */
  const start = () => {
    // No hacer nada si ya está corriendo
    if (isRunning.value) return;

    // Inicializar valores
    startTime.value = new Date();
    isRunning.value = true;
    isPaused.value = false;
    seconds.value = initialSeconds;
    totalPausedTime.value = 0;

    // Limpiar intervalo anterior si existe
    if (timerInterval) clearInterval(timerInterval);

    // Crear nuevo intervalo que actualiza cada segundo
    timerInterval = window.setInterval(updateTimer, 1000);
  };

  /**
   * Actualiza el contador de segundos basado en el tiempo transcurrido
   * Se llama automáticamente cada segundo mientras el temporizador está activo
   */
  const updateTimer = () => {
    if (!isRunning.value || !startTime.value) return;

    // Calcular segundos transcurridos desde el inicio
    const now = new Date();
    const elapsedSeconds = Math.floor((now.getTime() - startTime.value.getTime()) / 1000);

    // Restar el tiempo en pausa para obtener el tiempo real
    seconds.value = elapsedSeconds - totalPausedTime.value;
  };

  /**
   * Pausa el temporizador
   */
  const pause = () => {
    // No hacer nada si no está corriendo o ya está pausado
    if (!isRunning.value || isPaused.value) return;

    // Actualizar estado
    isPaused.value = true;
    isRunning.value = false;
    pausedAt.value = new Date();

    // Detener la actualización del temporizador
    if (timerInterval) clearInterval(timerInterval);
  };

  /**
   * Reanuda el temporizador después de una pausa
   */
  const resume = () => {
    // No hacer nada si no está pausado
    if (!isPaused.value || !pausedAt.value) return;

    // Calcular cuánto tiempo estuvo pausado
    const now = new Date();
    const pauseDuration = Math.floor((now.getTime() - pausedAt.value.getTime()) / 1000);
    totalPausedTime.value += pauseDuration;

    // Actualizar estado
    isPaused.value = false;
    isRunning.value = true;
    pausedAt.value = null;

    // Reiniciar el intervalo
    timerInterval = window.setInterval(updateTimer, 1000);
  };

  /**
   * Detiene el temporizador y lo reinicia
   * @returns El tiempo final en segundos
   */
  const stop = () => {
    // Guardar el valor final antes de reiniciar
    const finalSeconds = seconds.value;

    // Reiniciar todos los valores
    isRunning.value = false;
    isPaused.value = false;
    seconds.value = 0;
    startTime.value = null;
    pausedAt.value = null;
    totalPausedTime.value = 0;

    // Detener la actualización
    if (timerInterval) clearInterval(timerInterval);

    return finalSeconds;
  };

  /**
   * Ajusta manualmente la duración (útil para corregir tiempo inactivo)
   * @param adjustmentSeconds - Segundos a añadir (positivo) o restar (negativo)
   */
  const adjustDuration = (adjustmentSeconds: number) => {
    // Asegurar que nunca sea negativo
    seconds.value = Math.max(0, seconds.value + adjustmentSeconds);
  };

  // Limpiar el intervalo cuando el componente se desmonta
  onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
  });

  // Exponer las propiedades y métodos que se pueden usar desde fuera
  return {
    // Estado
    seconds,
    formattedTime,
    isRunning,
    isPaused,
    startTime,
    pausedAt,
    totalPausedTime,

    // Métodos
    start,
    pause,
    resume,
    stop,
    adjustDuration
  };
}
```

🔰 **Ejemplo de uso en un componente**:

```tsx
import { useTimer } from '@/composables/useTimer';

export default function SimpleTimer() {
  // Usar el composable
  const timer = useTimer();

  return (
    <div>
      <div className="display">
        {timer.formattedTime.value}
      </div>

      <div className="controls">
        {!timer.isRunning.value ? (
          <button onClick={() => timer.start()}>Iniciar</button>
        ) : timer.isPaused.value ? (
          <>
            <button onClick={() => timer.resume()}>Reanudar</button>
            <button onClick={() => timer.stop()}>Detener</button>
          </>
        ) : (
          <>
            <button onClick={() => timer.pause()}>Pausar</button>
            <button onClick={() => timer.stop()}>Detener</button>
          </>
        )}
      </div>
    </div>
  );
}
```

🔰 **Consejo**: Los composables son una excelente manera de reutilizar lógica entre componentes. Por ejemplo, este mismo `useTimer` podría usarse tanto en el componente principal de cronómetro como en un widget de tiempo en la barra lateral.

### 5.2 useIdleDetection.ts

Este composable detecta cuando el usuario está inactivo (no mueve el mouse, no presiona teclas, etc.) y permite ejecutar acciones cuando esto ocurre.

🔰 **¿Por qué es importante?** Ayuda a mejorar la precisión del registro de tiempo detectando cuando el usuario probablemente no está trabajando, aunque el temporizador siga corriendo.

```typescript
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Opciones para configurar la detección de inactividad
 */
interface IdleDetectionOptions {
  threshold?: number;   // Segundos de inactividad antes de considerarse "idle"
  onIdle?: () => void;  // Función a ejecutar cuando se detecta inactividad
  onActive?: () => void; // Función a ejecutar cuando el usuario vuelve a estar activo
  events?: string[];    // Lista de eventos del DOM a monitorear
}

/**
 * Composable para detectar inactividad del usuario
 * @param options - Opciones de configuración
 */
export function useIdleDetection(options: IdleDetectionOptions = {}) {
  // Valores por defecto para las opciones
  const {
    threshold = 600,    // 10 minutos por defecto
    onIdle = () => {},  // Función vacía por defecto
    onActive = () => {}, // Función vacía por defecto
    // Lista de eventos que indican actividad del usuario
    events = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll']
  } = options;

  // Estado interno
  const isIdle = ref(false);                  // Si el usuario está inactivo
  const lastActivity = ref(Date.now());       // Timestamp de la última actividad
  const idleStarted = ref<Date | null>(null); // Cuándo comenzó la inactividad
  let checkInterval: number | null = null;    // ID del intervalo de verificación

  /**
   * Reinicia el contador de actividad cuando el usuario hace algo
   */
  const resetActivity = () => {
    const wasIdle = isIdle.value;
    lastActivity.value = Date.now();
    isIdle.value = false;
    idleStarted.value = null;

    // Si estaba inactivo y ahora está activo, ejecutar callback
    if (wasIdle) {
      onActive();
    }
  };

  /**
   * Verifica si el usuario ha estado inactivo por más tiempo que el umbral
   */
  const checkIdleStatus = () => {
    const now = Date.now();
    const timeSinceActivity = (now - lastActivity.value) / 1000;

    // Si no estaba inactivo pero ha pasado el tiempo umbral
    if (!isIdle.value && timeSinceActivity >= threshold) {
      isIdle.value = true;
      idleStarted.value = new Date();
      onIdle(); // Ejecutar callback de inactividad
    }
  };

  /**
   * Maneja el evento de cambio de visibilidad del documento
   * (cuando el usuario cambia de pestaña o minimiza el navegador)
   */
  const handleVisibilityChange = () => {
    if (document.hidden) {
      // Si la pestaña está oculta, considerar como inactivo
      if (!isIdle.value) {
        isIdle.value = true;
        idleStarted.value = new Date();
        onIdle();
      }
    } else {
      // Si vuelve a la pestaña, considerar como activo
      resetActivity();
    }
  };

  /**
   * Calcula cuántos minutos ha estado inactivo el usuario
   */
  const getIdleMinutes = () => {
    if (!idleStarted.value) return 0;
    return Math.floor((Date.now() - idleStarted.value.getTime()) / 60000);
  };

  // Configurar listeners y temporizadores cuando se monta el componente
  onMounted(() => {
    // Agregar listeners para todos los eventos de actividad
    events.forEach(event => {
      window.addEventListener(event, resetActivity);
    });

    // Listener especial para cuando el usuario cambia de pestaña
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Iniciar intervalo que verifica periódicamente si el usuario está inactivo
    checkInterval = window.setInterval(checkIdleStatus, 10000); // Cada 10 segundos
  });

  // Limpiar listeners y temporizadores cuando se desmonta el componente
  onUnmounted(() => {
    // Eliminar todos los listeners de eventos
    events.forEach(event => {
      window.removeEventListener(event, resetActivity);
    });

    document.removeEventListener('visibilitychange', handleVisibilityChange);

    // Detener el intervalo de verificación
    if (checkInterval) {
      clearInterval(checkInterval);
    }
  });

  // Exponer propiedades y métodos
  return {
    isIdle,             // Si el usuario está actualmente inactivo
    lastActivity,       // Timestamp de la última actividad
    idleStarted,        // Cuándo comenzó el período de inactividad
    getIdleMinutes,     // Función para obtener minutos de inactividad
    resetActivity       // Función para reiniciar manualmente el estado
  };
}
```

🔰 **Ejemplo de uso con el componente IdlePromptModal**:

```tsx
import { useIdleDetection } from '@/composables/useIdleDetection';
import IdlePromptModal from '@/components/TimeTracker/IdlePromptModal';

export default function TimeTracker() {
  const [showIdlePrompt, setShowIdlePrompt] = useState(false);

  // Usar el composable de detección de inactividad
  const idle = useIdleDetection({
    threshold: 600, // 10 minutos
    onIdle: () => {
      // Mostrar el modal cuando se detecta inactividad
      setShowIdlePrompt(true);
    }
  });

  // Manejar la decisión del usuario sobre el tiempo inactivo
  const handleKeepTime = () => {
    setShowIdlePrompt(false);
    idle.resetActivity();
  };

  const handleDiscardTime = (minutes) => {
    setShowIdlePrompt(false);
    // Ajustar el temporizador para descontar el tiempo inactivo
    timer.adjustDuration(-minutes * 60);
    idle.resetActivity();
  };

  return (
    <div>
      {/* Componentes del temporizador */}

      {/* Modal que aparece cuando se detecta inactividad */}
      {showIdlePrompt && (
        <IdlePromptModal
          idleMinutes={idle.getIdleMinutes()}
          onKeepTime={handleKeepTime}
          onDiscardTime={handleDiscardTime}
        />
      )}
    </div>
  );
}
```

🔰 **Consejo**: Este composable funciona mejor cuando se combina con el `useTimer` para ajustar automáticamente el tiempo registrado cuando se detecta inactividad. Considera configurar el umbral (`threshold`) según las necesidades de tu equipo - 10 minutos es un buen punto de partida, pero algunos equipos prefieren valores más cortos o más largos.

### 5.3 useTimeReminders.ts

Este composable gestiona los recordatorios automáticos para que los usuarios completen su registro de tiempo diario.

🔰 **¿Por qué es importante?** Muchos usuarios olvidan registrar sus horas, especialmente al final del día. Este sistema de recordatorios ayuda a aumentar la precisión del registro y reduce el trabajo administrativo posterior.

```typescript
import { ref, computed, onMounted } from 'vue';
import { useTimeEntryStore } from '@/stores/timeEntryStore';

/**
 * Opciones para configurar los recordatorios de tiempo
 */
interface ReminderOptions {
  dailyGoal?: number;              // Objetivo diario de horas (por defecto: 8)
  reminderTime?: string;           // Hora del recordatorio en formato "HH:MM"
  enableNotifications?: boolean;   // Si se deben mostrar notificaciones del navegador
}

/**
 * Composable para gestionar recordatorios de registro de tiempo
 */
export function useTimeReminders(options: ReminderOptions = {}) {
  // Valores por defecto para las opciones
  const {
    dailyGoal = 8,                 // 8 horas por defecto
    reminderTime = '17:00',        // 5:00 PM por defecto
    enableNotifications = true     // Notificaciones activadas por defecto
  } = options;

  // Acceder al store global de entradas de tiempo
  const store = useTimeEntryStore();

  // Estado interno
  const notificationPermission = ref<NotificationPermission>('default');
  const nextReminderTime = ref<Date | null>(null);

  /**
   * Solicita permiso para mostrar notificaciones en el navegador
   */
  const requestNotificationPermission = async () => {
    // Verificar si el navegador soporta notificaciones y aún no se ha pedido permiso
    if ('Notification' in window && Notification.permission === 'default') {
      // Solicitar permiso al usuario
      const permission = await Notification.requestPermission();
      notificationPermission.value = permission;
    }
  };

  /**
   * Calcula cuándo debe enviarse el próximo recordatorio
   */
  const calculateNextReminderTime = () => {
    const now = new Date();
    // Convertir "17:00" a horas y minutos numéricos
    const [hours, minutes] = reminderTime.split(':').map(Number);

    // Crear fecha para hoy a la hora especificada
    const reminder = new Date();
    reminder.setHours(hours, minutes, 0, 0);

    // Si ya pasó la hora de hoy, programar para mañana
    if (reminder <= now) {
      reminder.setDate(reminder.getDate() + 1);
    }

    // No programar recordatorios para fines de semana
    while (reminder.getDay() === 0 || reminder.getDay() === 6) { // 0=domingo, 6=sábado
      reminder.setDate(reminder.getDate() + 1);
    }

    nextReminderTime.value = reminder;
    return reminder;
  };

  /**
   * Determina si se debe enviar un recordatorio basado en las horas registradas
   */
  const shouldSendReminder = computed(() => {
    const todaysHours = store.todaysTotalHours;
    // Enviar recordatorio si no se ha alcanzado el objetivo y no se ha enviado hoy
    return todaysHours < dailyGoal && !store.reminders.dailySent;
  });

  /**
   * Envía el recordatorio al usuario
   */
  const sendReminder = async () => {
    // No hacer nada si no se necesita enviar recordatorio
    if (!shouldSendReminder.value) return;

    const hoursTracked = store.todaysTotalHours;
    const hoursRemaining = dailyGoal - hoursTracked;

    // Enviar notificación del navegador si está permitido
    if (enableNotifications && notificationPermission.value === 'granted') {
      new Notification('Recordatorio de Registro de Tiempo', {
        body: `Has registrado ${hoursTracked.toFixed(1)} horas hoy. Te faltan ${hoursRemaining.toFixed(1)} horas para alcanzar tu meta diaria.`,
        icon: '/icon-192.png',
        tag: 'time-reminder',
        requireInteraction: true  // Requiere que el usuario interactúe con la notificación
      });
    }

    // También enviar por otros canales (email, etc.) usando el store
    await store.sendDailyReminder();
  };

  /**
   * Programa el próximo recordatorio basado en la hora configurada
   */
  const scheduleNextReminder = () => {
    const next = calculateNextReminderTime();
    const now = new Date();
    const timeUntilReminder = next.getTime() - now.getTime();

    // Solo programar si el tiempo es positivo (futuro)
    if (timeUntilReminder > 0) {
      setTimeout(() => {
        sendReminder();
        scheduleNextReminder(); // Programar el siguiente recordatorio
      }, timeUntilReminder);
    }
  };

  // Cuando el componente se monta, solicitar permisos y programar recordatorios
  onMounted(() => {
    requestNotificationPermission();
    scheduleNextReminder();
  });

  // Exponer propiedades y métodos
  return {
    notificationPermission,        // Estado actual del permiso de notificaciones
    nextReminderTime,              // Cuándo se enviará el próximo recordatorio
    shouldSendReminder,            // Si se debe enviar un recordatorio ahora
    sendReminder,                  // Función para enviar recordatorio manualmente
    requestNotificationPermission  // Función para solicitar permisos manualmente
  };
}
```

🔰 **Ejemplo de uso en un componente**:

```tsx
import { useTimeReminders } from '@/composables/useTimeReminders';

export default function TimeTrackerApp() {
  // Configurar recordatorios con opciones personalizadas
  const reminders = useTimeReminders({
    dailyGoal: 7.5,           // Objetivo de 7.5 horas diarias
    reminderTime: '16:30',    // Recordar a las 4:30 PM
    enableNotifications: true // Usar notificaciones del navegador
  });

  // Mostrar cuándo será el próximo recordatorio
  const formatNextReminder = () => {
    if (!reminders.nextReminderTime.value) return 'No programado';

    return reminders.nextReminderTime.value.toLocaleString('es', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  return (
    <div>
      <div className="time-tracker-main">
        {/* Componentes principales del temporizador */}
      </div>

      <div className="reminder-settings">
        <h3>Configuración de Recordatorios</h3>

        {reminders.notificationPermission.value !== 'granted' && (
          <button onClick={() => reminders.requestNotificationPermission()}>
            Permitir Notificaciones
          </button>
        )}

        <p>Próximo recordatorio: {formatNextReminder()}</p>

        {/* Botón para probar recordatorios */}
        <button 
          onClick={() => reminders.sendReminder()}
          disabled={!reminders.shouldSendReminder.value}
        >
          Enviar Recordatorio Ahora
        </button>
      </div>
    </div>
  );
}
```

🔰 **Consejo**: Para una mejor experiencia de usuario, solicita el permiso de notificaciones después de que el usuario haya interactuado con la aplicación (por ejemplo, después de iniciar un temporizador por primera vez), en lugar de hacerlo inmediatamente al cargar la página.

### 5.4 useTimesheetApproval.ts

Este composable maneja el flujo de aprobación de hojas de tiempo, permitiendo a los usuarios enviar sus registros para revisión y a los supervisores aprobarlos o rechazarlos.

🔰 **¿Por qué es importante?** El proceso de aprobación garantiza que los registros de tiempo sean precisos y estén completos antes de usarlos para facturación o análisis. Este composable simplifica la implementación de este flujo de trabajo.

```typescript
import { ref, computed } from 'vue';
import { useTimeEntryStore } from '@/stores/timeEntryStore';
import axios from 'axios';

/**
 * Composable para gestionar el flujo de aprobación de hojas de tiempo
 */
export function useTimesheetApproval() {
  // Acceder al store global
  const store = useTimeEntryStore();

  // Estado de carga para mostrar indicadores visuales
  const isSubmitting = ref(false); // Si está enviando la hoja de tiempo
  const isApproving = ref(false);  // Si está aprobando la hoja de tiempo

  /**
   * Determina si la hoja de tiempo puede ser enviada para aprobación
   */
  const canSubmit = computed(() => 
    // No debe estar ya enviada
    !store.approval.isSubmitted && 
    // No debe estar bloqueada
    !store.approval.isLocked &&
    // Debe tener al menos algunas horas registradas
    store.todaysTotalHours > 0
  );

  /**
   * Determina si la hoja de tiempo puede ser aprobada
   * (generalmente usado por supervisores/managers)
   */
  const canApprove = computed(() => 
    // Debe estar enviada
    store.approval.isSubmitted && 
    // No debe estar ya aprobada
    !store.approval.isApproved &&
    // No debe estar bloqueada
    !store.approval.isLocked
  );

  /**
   * Determina si la hoja de tiempo puede ser editada
   */
  const canEdit = computed(() => 
    // Solo se puede editar si no está bloqueada
    !store.approval.isLocked
  );

  /**
   * Envía una hoja de tiempo para aprobación
   * @param weekStart - Fecha de inicio de la semana
   * @param weekEnd - Fecha de fin de la semana
   * @returns Objeto con el resultado de la operación
   */
  const submitTimesheet = async (weekStart: Date, weekEnd: Date) => {
    // Verificar si se puede enviar
    if (!canSubmit.value) return;

    // Activar indicador de carga
    isSubmitting.value = true;

    try {
      // Llamar a la acción del store para enviar la hoja de tiempo
      await store.submitTimesheet({ weekStart, weekEnd });
      return { success: true };
    } catch (error) {
      return { success: false, error };
    } finally {
      // Desactivar indicador de carga
      isSubmitting.value = false;
    }
  };

  /**
   * Aprueba una hoja de tiempo (para supervisores/managers)
   * @param userId - ID del usuario cuya hoja se está aprobando
   * @param weekStart - Fecha de inicio de la semana
   * @returns Objeto con el resultado de la operación
   */
  const approveTimesheet = async (userId: number, weekStart: Date) => {
    // Verificar si se puede aprobar
    if (!canApprove.value) return;

    // Activar indicador de carga
    isApproving.value = true;

    try {
      // Aprobar la hoja de tiempo
      await store.approveTimesheet({ userId, weekStart });
      // Bloquear para evitar ediciones posteriores
      await store.lockTimesheet();
      return { success: true };
    } catch (error) {
      return { success: false, error };
    } finally {
      // Desactivar indicador de carga
      isApproving.value = false;
    }
  };

  /**
   * Rechaza una hoja de tiempo (para supervisores/managers)
   * @param userId - ID del usuario cuya hoja se está rechazando
   * @param weekStart - Fecha de inicio de la semana
   * @param reason - Motivo del rechazo
   * @returns Objeto con el resultado de la operación
   */
  const rejectTimesheet = async (userId: number, weekStart: Date, reason: string) => {
    try {
      // Enviar solicitud de rechazo al servidor
      const response = await axios.post('/api/timesheets/reject', {
        user_id: userId,
        week_start: weekStart,
        reason
      });

      // Resetear el estado de aprobación para permitir ediciones
      store.resetApprovalStatus();

      return { success: true, data: response.data };
    } catch (error) {
      return { success: false, error };
    }
  };

  // Exponer propiedades y métodos
  return {
    // Estado de carga
    isSubmitting,
    isApproving,

    // Permisos
    canSubmit,
    canApprove,
    canEdit,

    // Acciones
    submitTimesheet,
    approveTimesheet,
    rejectTimesheet,

    // Estado actual
    status: computed(() => store.timesheetStatus)
  };
}
```

🔰 **Ejemplo de uso en un componente para empleados**:

```tsx
import { useTimesheetApproval } from '@/composables/useTimesheetApproval';

export default function WeeklyTimesheet() {
  // Obtener la semana actual (lunes a domingo)
  const getWeekDates = () => {
    const now = new Date();
    const dayOfWeek = now.getDay(); // 0 = domingo, 1 = lunes, ...
    const diff = now.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1); // Ajustar para que la semana comience el lunes

    const weekStart = new Date(now.setDate(diff));
    weekStart.setHours(0, 0, 0, 0);

    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    weekEnd.setHours(23, 59, 59, 999);

    return { weekStart, weekEnd };
  };

  const { weekStart, weekEnd } = getWeekDates();
  const { canSubmit, isSubmitting, submitTimesheet, status } = useTimesheetApproval();

  const handleSubmit = async () => {
    if (!confirm('¿Estás seguro de enviar esta hoja de tiempo para aprobación?')) {
      return;
    }

    const result = await submitTimesheet(weekStart, weekEnd);

    if (result?.success) {
      alert('Hoja de tiempo enviada con éxito');
    } else {
      alert('Error al enviar la hoja de tiempo');
    }
  };

  return (
    <div className="weekly-timesheet">
      <h2>Hoja de Tiempo: {weekStart.toLocaleDateString()} - {weekEnd.toLocaleDateString()}</h2>

      {/* Contenido de la hoja de tiempo */}

      <div className="timesheet-actions">
        <button 
          onClick={handleSubmit}
          disabled={!canSubmit.value || isSubmitting.value}
        >
          {isSubmitting.value ? 'Enviando...' : 'Enviar para aprobación'}
        </button>

        {status.value !== 'draft' && (
          <div className="status-badge">
            Estado: {
              status.value === 'submitted' ? 'Enviado' :
              status.value === 'approved' ? 'Aprobado' :
              status.value === 'locked' ? 'Bloqueado' : 'Borrador'
            }
          </div>
        )}
      </div>
    </div>
  );
}
```

🔰 **Ejemplo de uso en un componente para supervisores**:

```tsx
import { useTimesheetApproval } from '@/composables/useTimesheetApproval';

export default function TimesheetApprovalPanel({ userId, weekStart }) {
  const [rejectReason, setRejectReason] = useState('');
  const [showRejectModal, setShowRejectModal] = useState(false);

  const { 
    canApprove, 
    isApproving, 
    approveTimesheet, 
    rejectTimesheet 
  } = useTimesheetApproval();

  const handleApprove = async () => {
    if (!confirm('¿Estás seguro de aprobar esta hoja de tiempo?')) {
      return;
    }

    const result = await approveTimesheet(userId, weekStart);

    if (result?.success) {
      alert('Hoja de tiempo aprobada con éxito');
    } else {
      alert('Error al aprobar la hoja de tiempo');
    }
  };

  const handleReject = async () => {
    if (!rejectReason.trim()) {
      alert('Por favor, proporciona un motivo para el rechazo');
      return;
    }

    const result = await rejectTimesheet(userId, weekStart, rejectReason);

    if (result?.success) {
      alert('Hoja de tiempo rechazada');
      setShowRejectModal(false);
    } else {
      alert('Error al rechazar la hoja de tiempo');
    }
  };

  return (
    <div className="approval-panel">
      <h3>Panel de Aprobación</h3>

      <div className="approval-actions">
        <button 
          onClick={handleApprove}
          disabled={!canApprove.value || isApproving.value}
          className="approve-button"
        >
          {isApproving.value ? 'Aprobando...' : 'Aprobar Hoja de Tiempo'}
        </button>

        <button 
          onClick={() => setShowRejectModal(true)}
          disabled={!canApprove.value}
          className="reject-button"
        >
          Rechazar
        </button>
      </div>

      {showRejectModal && (
        <div className="reject-modal">
          <h4>Motivo del Rechazo</h4>
          <textarea 
            value={rejectReason}
            onChange={(e) => setRejectReason(e.target.value)}
            placeholder="Explica por qué estás rechazando esta hoja de tiempo..."
          />
          <div className="modal-actions">
            <button onClick={() => setShowRejectModal(false)}>Cancelar</button>
            <button onClick={handleReject}>Confirmar Rechazo</button>
          </div>
        </div>
      )}
    </div>
  );
}
```

🔰 **Consejo**: Asegúrate de implementar diferentes vistas y permisos según el rol del usuario. Los empleados regulares solo deberían poder enviar sus propias hojas de tiempo, mientras que los supervisores o gerentes deberían poder ver, aprobar o rechazar las hojas de tiempo de sus equipos.
```

### 5.5 useLocalStorageBackup.ts (actualizado)

```typescript
const CURRENT_ENTRY_KEY = 'enkiflow_current_time_entry';
const FAILED_ENTRIES_KEY = 'enkiflow_failed_time_entries';
const PREFERENCES_KEY = 'enkiflow_time_preferences';
const IDLE_STATE_KEY = 'enkiflow_idle_state';

interface StoredEntry {
  [key: string]: any;
  startTime?: string;
  endTime?: string;
  pausedAt?: string;
  lastSyncedAt?: string;
}

interface StoredPreferences {
  dailyHoursGoal: number;
  reminderTime: string;
  enableIdleDetection: boolean;
  enableReminders: boolean;
}

export function useLocalStorageBackup() {
  const saveCurrentEntry = (entry: any) => {
    try {
      const toStore: StoredEntry = {
        ...entry,
        startTime: entry.startTime?.toISOString(),
        endTime: entry.endTime?.toISOString(),
        pausedAt: entry.pausedAt?.toISOString(),
        lastSyncedAt: new Date().toISOString()
      };
      localStorage.setItem(CURRENT_ENTRY_KEY, JSON.stringify(toStore));
    } catch (error) {
      console.error('Error al guardar en localStorage:', error);
    }
  };

  const getSavedEntry = (): any | null => {
    try {
      const savedEntry = localStorage.getItem(CURRENT_ENTRY_KEY);
      if (!savedEntry) return null;

      const entry = JSON.parse(savedEntry);

      // Convertir strings a Date
      if (entry.startTime) entry.startTime = new Date(entry.startTime);
      if (entry.endTime) entry.endTime = new Date(entry.endTime);
      if (entry.pausedAt) entry.pausedAt = new Date(entry.pausedAt);

      return entry;
    } catch (error) {
      console.error('Error al leer de localStorage:', error);
      return null;
    }
  };

  const clearCurrentEntry = () => {
    try {
      localStorage.removeItem(CURRENT_ENTRY_KEY);
    } catch (error) {
      console.error('Error al limpiar localStorage:', error);
    }
  };

  const saveFailedEntry = (entry: any) => {
    try {
      const failedEntries = getFailedEntries();

      failedEntries.push({
        ...entry,
        startTime: entry.startTime?.toISOString(),
        endTime: entry.endTime?.toISOString(),
        failedAt: new Date().toISOString()
      });

      localStorage.setItem(FAILED_ENTRIES_KEY, JSON.stringify(failedEntries));
    } catch (error) {
      console.error('Error al guardar entrada fallida:', error);
    }
  };

  const getFailedEntries = (): any[] => {
    try {
      const entries = localStorage.getItem(FAILED_ENTRIES_KEY);
      return entries ? JSON.parse(entries) : [];
    } catch (error) {
      console.error('Error al leer entradas fallidas:', error);
      return [];
    }
  };

  const removeFailedEntry = (entry: any) => {
    try {
      const failedEntries = getFailedEntries();
      const updatedEntries = failedEntries.filter((e: any) => 
        e.startTime !== entry.startTime || 
        e.description !== entry.description
      );

      localStorage.setItem(FAILED_ENTRIES_KEY, JSON.stringify(updatedEntries));
    } catch (error) {
      console.error('Error al eliminar entrada fallida:', error);
    }
  };

  const savePreferences = (prefs: StoredPreferences) => {
    try {
      localStorage.setItem(PREFERENCES_KEY, JSON.stringify(prefs));
    } catch (error) {
      console.error('Error al guardar preferencias:', error);
    }
  };

  const getPreferences = (): StoredPreferences | null => {
    try {
      const prefs = localStorage.getItem(PREFERENCES_KEY);
      return prefs ? JSON.parse(prefs) : null;
    } catch (error) {
      console.error('Error al leer preferencias:', error);
      return null;
    }
  };

  const saveIdleState = (idleData: any) => {
    try {
      localStorage.setItem(IDLE_STATE_KEY, JSON.stringify({
        ...idleData,
        timestamp: new Date().toISOString()
      }));
    } catch (error) {
      console.error('Error al guardar estado idle:', error);
    }
  };

  const getIdleState = () => {
    try {
      const state = localStorage.getItem(IDLE_STATE_KEY);
      return state ? JSON.parse(state) : null;
    } catch (error) {
      console.error('Error al leer estado idle:', error);
      return null;
    }
  };

  return {
    saveCurrentEntry,
    getSavedEntry,
    clearCurrentEntry,
    saveFailedEntry,
    getFailedEntries,
    removeFailedEntry,
    savePreferences,
    getPreferences,
    saveIdleState,
    getIdleState
  };
}
```

## 6. Integración y Flujos de Usuario

### 6.1 Flujo de Timer Único Activo

1. Usuario navega a la ruta `/time` (vista predeterminada del temporizador)
2. Usuario intenta iniciar un nuevo timer
3. Sistema verifica si existe un timer activo
4. Si existe, muestra confirmación: "¿Detener timer actual y comenzar uno nuevo?"
5. Al confirmar, detiene el timer actual (guardando la entrada) y comienza el nuevo
6. StatusIndicator actualiza para mostrar el nuevo timer activo
7. El timer activo es visible desde cualquier subpestaña del módulo de tiempo

### 6.2 Flujo de Detección de Inactividad

1. Usuario tiene un timer activo dentro de cualquier vista del módulo `/time`
2. useIdleDetection monitorea actividad del usuario en segundo plano
3. Tras 10 minutos sin actividad, dispara evento `onIdle`
4. IdlePromptModal aparece como overlay sobre cualquier pestaña activa del módulo de tiempo, preguntando: "Detectamos 10 minutos de inactividad. ¿Mantener o descartar este tiempo?"
5. Usuario selecciona opción:
   - **Mantener**: El tiempo se conserva íntegro
   - **Descartar**: Se resta el tiempo inactivo del total
6. La decisión se registra y el timer continúa o se ajusta según corresponda
7. El estado actualizado del timer se refleja en todas las vistas del módulo de tiempo

### 6.3 Flujo de Recordatorio Diario

1. useTimeReminders programa verificación a las 17:00 (configurable)
2. Si horas registradas < objetivo diario:
   - Muestra notificación del navegador
   - Envía email de recordatorio con enlace directo a la ruta `/time`
3. Usuario puede:
   - Hacer clic en la notificación para ir directamente al módulo de tiempo unificado
   - Ignorar y recibir recordatorio al día siguiente
4. Al acceder a través de la notificación, el sistema carga la vista más relevante dentro del módulo de tiempo (generalmente la pestaña de entradas del día)
5. Sistema marca recordatorio como enviado para evitar duplicados
6. El banner de recordatorio es visible en todas las pestañas del módulo de tiempo hasta que se complete el objetivo diario

### 6.4 Flujo de Vista Semana

1. Usuario navega a la ruta `/time` y selecciona la pestaña "📅 Vista semanal"
2. Sistema carga el componente TimesheetWeek dentro del layout principal
3. Usuario ve grid con proyectos/tareas en filas y días en columnas
4. Puede:
   - Ingresar horas directamente en celdas
   - Ver totales por día y proyecto
   - Usar tab/enter para navegación rápida
   - Cambiar entre semanas manteniendo el contexto de la aplicación
5. Cambios se guardan automáticamente (con debounce)
6. Al final puede "Enviar semana" para aprobación
7. Puede cambiar a otras pestañas sin perder el contexto o estado de la semana actual

### 6.5 Flujo de Duplicar Día

1. Usuario navega a la ruta `/time` y selecciona la pestaña "📋 Entradas recientes (Vista Día)"
2. En la vista de día (TimesheetDay), usuario hace clic en "Duplicar día anterior"
3. Sistema copia todas las entradas del día anterior (sin las horas)
4. Usuario ajusta horas según trabajo realizado
5. Ahorra tiempo al no tener que recrear estructura de proyectos/tareas
6. Los cambios se reflejan inmediatamente en todas las vistas del módulo de tiempo

### 6.6 Flujo de Aprobación

1. **Envío**: Usuario completa semana dentro de la ruta `/time` (generalmente en la pestaña "📅 Vista semanal") y hace clic en "Enviar para aprobación"
2. **Notificación**: El sistema muestra un banner de confirmación dentro del módulo de tiempo
3. **Revisión**: Manager recibe notificación y accede a la vista de equipo dentro del mismo módulo de tiempo
4. **Decisión**:
   - **Aprobar**: Timesheet se marca como aprobada y se bloquea
   - **Rechazar**: Se devuelve con comentarios para corrección
5. **Bloqueo**: Una vez aprobada, la interfaz en todas las pestañas del módulo refleja el estado bloqueado
6. **Visibilidad**: El estado de aprobación es visible consistentemente en todas las vistas del módulo de tiempo

## 7. Pruebas

### 7.1 Pruebas para useIdleDetection

```typescript
import { useIdleDetection } from '@/composables/useIdleDetection';
import { flushPromises } from '@vue/test-utils';

describe('useIdleDetection', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  test('should detect idle after threshold', async () => {
    const onIdleMock = vi.fn();
    const { isIdle, lastActivity } = useIdleDetection({
      threshold: 60, // 1 minuto para test
      onIdle: onIdleMock
    });

    expect(isIdle.value).toBe(false);

    // Avanzar tiempo sin actividad
    vi.advanceTimersByTime(70000); // 70 segundos
    await flushPromises();

    expect(onIdleMock).toHaveBeenCalled();
    expect(isIdle.value).toBe(true);
  });

  test('should reset on activity', async () => {
    const onActiveMock = vi.fn();
    const { isIdle, resetActivity } = useIdleDetection({
      threshold: 60,
      onActive: onActiveMock
    });

    // Simular inactividad
    vi.advanceTimersByTime(70000);
    await flushPromises();
    expect(isIdle.value).toBe(true);

    // Simular actividad
    resetActivity();
    expect(isIdle.value).toBe(false);
    expect(onActiveMock).toHaveBeenCalled();
  });

  test('should handle visibility change', async () => {
    const onIdleMock = vi.fn();
    useIdleDetection({
      onIdle: onIdleMock
    });

    // Simular pestaña oculta
    Object.defineProperty(document, 'hidden', {
      value: true,
      writable: true
    });

    document.dispatchEvent(new Event('visibilitychange'));
    await flushPromises();

    expect(onIdleMock).toHaveBeenCalled();
  });
});
```

### 7.2 Pruebas para Timer con restricción de único activo

```typescript
import { mount } from '@vue/test-utils';
import Timer from '@/components/TimeTracker/Timer.vue';

describe('Timer.vue - Single Active Timer', () => {
  test('should disable start when another timer is active', () => {
    const wrapper = mount(Timer, {
      props: {
        isRunning: false,
        isPaused: false,
        hasActiveTimer: true
      }
    });

    const startButton = wrapper.find('.start-button');
    expect(startButton.attributes('disabled')).toBeDefined();
  });

  test('should show warning when trying to start with active timer', async () => {
    const wrapper = mount(Timer, {
      props: {
        isRunning: false,
        isPaused: false,
        hasActiveTimer: true
      }
    });

    await wrapper.find('.start-button').trigger('click');
    expect(wrapper.emitted('request-stop-active')).toBeTruthy();
  });
});
```

### 7.3 Pruebas para flujo de aprobación

```typescript
import { useTimesheetApproval } from '@/composables/useTimesheetApproval';
import { setActivePinia, createPinia } from 'pinia';

describe('useTimesheetApproval', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  test('should submit timesheet successfully', async () => {
    const { submitTimesheet, canSubmit } = useTimesheetApproval();

    expect(canSubmit.value).toBe(true);

    const result = await submitTimesheet(
      new Date('2024-01-01'),
      new Date('2024-01-07')
    );

    expect(result.success).toBe(true);
    expect(canSubmit.value).toBe(false);
  });

  test('should not allow edit when locked', async () => {
    const { canEdit } = useTimesheetApproval();
    const store = useTimeEntryStore();

    store.approval.isLocked = true;
    expect(canEdit.value).toBe(false);
  });
});
```

## 8. Criterios de Aceptación

### 8.1 Funcionalidad Base
- ✅ El usuario puede iniciar, pausar, reanudar y detener el temporizador
- ✅ El temporizador muestra correctamente el tiempo transcurrido en formato HH:MM:SS
- ✅ El usuario puede asociar una entrada de tiempo a un proyecto y/o tarea
- ✅ El usuario puede ingresar una descripción para la entrada de tiempo
- ✅ Las entradas de tiempo se guardan correctamente en el servidor

### 8.2 Persistencia
- ✅ El temporizador persiste al recargar la página mediante localStorage
- ✅ Si el usuario cierra el navegador con un temporizador activo, se recupera al volver
- ✅ Las entradas que no se pudieron guardar por problemas de conexión se sincronizan automáticamente

### 8.3 Funcionalidades Harvest-Inspired
- ✅ **Single Active Timer**: Solo un temporizador puede estar activo a la vez
- ✅ **Idle Detection**: El sistema pregunta tras 10 min sin actividad y ajusta tiempo según selección
- ✅ **Recordatorio Diario**: Si a las 17:00 el usuario registró < 8h, se envía notificación
- ✅ **Duplicate Day**: Botón disponible en vista Día que copia estructura de entradas
- ✅ **Week View**: Permite ingresar horas en bloque con grid editable
- ✅ **Approval Flow**: Timesheets pueden ser enviadas, aprobadas y bloqueadas

### 8.4 UX/UI
- ✅ Interfaz limpia y fácil de usar inspirada en Harvest
- ✅ Indicadores visuales claros del estado del temporizador
- ✅ Feedback visual al iniciar, pausar y detener
- ✅ Vistas Día/Semana intuitivas para entrada rápida
- ✅ Diseño responsive que funciona en móviles y desktop
- ✅ Toda experiencia de tracking ocurre dentro de la ruta unificada `/time`

### 8.5 Rendimiento
- ✅ El temporizador actualiza la UI sin problemas de rendimiento
- ✅ Las operaciones de localStorage son eficientes
- ✅ La sincronización con el servidor es asíncrona
- ✅ La detección de inactividad no impacta el rendimiento

## 9. Consideraciones de Implementación

### 9.1 Migración de Base de Datos

```sql
-- Agregar campos de aprobación a time_entries
ALTER TABLE time_entries ADD COLUMN submitted_at TIMESTAMP NULL;
ALTER TABLE time_entries ADD COLUMN approved_at TIMESTAMP NULL;
ALTER TABLE time_entries ADD COLUMN approved_by_id BIGINT UNSIGNED NULL;
ALTER TABLE time_entries ADD COLUMN locked_at TIMESTAMP NULL;
ALTER TABLE time_entries ADD COLUMN idle_minutes INT DEFAULT 0;

-- Crear tabla para preferencias de usuario
CREATE TABLE user_time_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    daily_hours_goal DECIMAL(4,2) DEFAULT 8.00,
    reminder_time TIME DEFAULT '17:00:00',
    enable_idle_detection BOOLEAN DEFAULT TRUE,
    enable_reminders BOOLEAN DEFAULT TRUE,
    idle_threshold_minutes INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Índices para consultas de aprobación
CREATE INDEX idx_time_entries_approval ON time_entries(submitted_at, approved_at, user_id);
```

### 9.2 Configuración de Laravel para Multi-tenancy

```php
// En el modelo TimeEntry
protected $casts = [
    'submitted_at' => 'datetime',
    'approved_at' => 'datetime',
    'locked_at' => 'datetime',
];

// Scopes para consultas
public function scopeSubmitted($query) {
    return $query->whereNotNull('submitted_at');
}

public function scopeApproved($query) {
    return $query->whereNotNull('approved_at');
}

public function scopeLocked($query) {
    return $query->whereNotNull('locked_at');
}

public function scopeWeek($query, $weekStart) {
    return $query->whereBetween('started_at', [
        $weekStart,
        Carbon::parse($weekStart)->endOfWeek()
    ]);
}
```

### 9.3 Notificaciones y Cron Jobs

```php
// App\Console\Commands\SendTimeReminders
class SendTimeReminders extends Command
{
    protected $signature = 'time:send-reminders';

    public function handle()
    {
        $users = User::whereHas('preferences', function($q) {
            $q->where('enable_reminders', true);
        })->get();

        foreach ($users as $user) {
            $hoursToday = $user->timeEntries()
                ->whereDate('started_at', today())
                ->sum('duration') / 3600;

            if ($hoursToday < $user->preferences->daily_hours_goal) {
                $user->notify(new DailyTimeReminder($hoursToday));
            }
        }
    }
}

// En Kernel.php
$schedule->command('time:send-reminders')->dailyAt('17:00');
```

### 9.4 API Endpoints Necesarios

```php
// Rutas API
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Time entries - Consolidados bajo un único prefijo
    Route::prefix('time')->group(function () {
        Route::post('/entries/duplicate-day', [TimeEntryController::class, 'duplicateDay']);
        Route::get('/entries/week/{date}', [TimeEntryController::class, 'weekView']);
        Route::get('/entries/day/{date}', [TimeEntryController::class, 'dayView']);

        // Timesheets
        Route::post('/submit', [TimesheetController::class, 'submit']);
        Route::post('/approve', [TimesheetController::class, 'approve']);
        Route::post('/reject', [TimesheetController::class, 'reject']);

        // Preferencias específicas de tiempo
        Route::get('/preferences', [TimePreferenceController::class, 'show']);
        Route::put('/preferences', [TimePreferenceController::class, 'update']);

        // Recordatorios
        Route::post('/reminders/daily', [ReminderController::class, 'sendDaily']);

        // Estado de navegación
        Route::get('/active-view', [TimeViewController::class, 'getActiveView']);
        Route::post('/active-view', [TimeViewController::class, 'setActiveView']);
    });

    // Otras preferencias de usuario generales
    Route::get('/user/preferences', [UserPreferenceController::class, 'show']);
    Route::put('/user/preferences', [UserPreferenceController::class, 'update']);
});
```

## 10. Roadmap de Implementación

### Fase 1: Core Timer y Navegación Unificada (1-2 semanas)
- Implementar componentes base (Timer, TaskSelector, etc.)
- Store de Pinia con funcionalidad básica
- Persistencia en localStorage
- Configurar estructura de navegación unificada bajo `/time`
- Implementar sistema de tabs para las diferentes vistas
- Definir layout principal para el módulo de tiempo
- Configurar rutas en Inertia.js para la navegación consolidada
- Tests unitarios

### Fase 2: Detección de Inactividad (1 semana)
- Implementar useIdleDetection
- Crear IdlePromptModal
- Integrar con store
- Tests de integración

### Fase 3: Vistas Día/Semana y Refactorización (2 semanas)
- Desarrollar TimesheetDay y TimesheetWeek como componentes dentro del sistema de tabs
- Implementar edición en grid
- Acción duplicar día
- Refactorizar componentes redundantes (consolidar vistas de resumen diario)
- Alinear navegación y estado en timeEntryStore.js para soportar la unificación
- Optimización de rendimiento
- Asegurar transiciones fluidas entre las diferentes vistas

### Fase 4: Recordatorios y Notificaciones (1 semana)
- Configurar notificaciones del navegador
- Implementar cron jobs
- Preferencias de usuario
- Tests end-to-end

### Fase 5: Flujo de Aprobación (1-2 semanas)
- Estados de timesheet
- Interfaces de manager
- Bloqueo post-aprobación
- Reportes y auditoría

## 11. Consideraciones de Seguridad

### 11.1 Validación de Datos
- Validar duración máxima de entradas (24h por día)
- Prevenir manipulación de timestamps
- Verificar permisos antes de aprobar/rechazar

### 11.2 Rate Limiting
- Limitar frecuencia de start/stop (max 60 por hora)
- Throttle en endpoints de aprobación
- Protección contra spam de recordatorios

### 11.3 Auditoría
- Log de todas las acciones de aprobación
- Registro de cambios en entradas bloqueadas
- Tracking de ajustes por inactividad

## 12. Métricas de Éxito

### 12.1 KPIs de Adopción
- % de usuarios que registran 8h diarias
- Reducción en entradas manuales retrospectivas
- Tiempo promedio entre trabajo y registro
- Tasa de uso de detección de inactividad

### 12.2 KPIs de Eficiencia
- Tiempo para completar timesheet semanal
- Número de clics para registrar tiempo
- Tasa de errores en registro
- Satisfacción del usuario (NPS)

## 13. Conclusión

Este diseño técnico proporciona una implementación robusta y completa para el módulo de temporizador de tiempo interactivo, incorporando las mejores prácticas probadas por Harvest. Las funcionalidades adicionales como detección de inactividad, recordatorios automáticos, vistas día/semana y flujo de aprobación elevan significativamente la experiencia del usuario y la precisión del registro de tiempo.

La arquitectura modular propuesta garantiza mantenibilidad y escalabilidad, mientras que la integración con el stack existente de Laravel 12 + React 19 + Inertia.js asegura consistencia con el resto de la aplicación EnkiFlow.

Las mejoras inspiradas en Harvest no solo incrementan la funcionalidad, sino que establecen un estándar de la industria para el registro de tiempo, posicionando a EnkiFlow como una solución competitiva en el mercado SaaS de productividad.
