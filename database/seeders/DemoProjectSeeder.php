<?php

namespace Database\Seeders;

use App\Helpers\RelativeDate;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskState;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class DemoProjectSeeder extends Seeder
{
    /**
     * Escenario a utilizar.
     *
     * @var string|null
     */
    protected $scenario = null;

    /**
     * Mapeo de prioridades de texto a enteros.
     *
     * @var array
     */
    protected $priorityMap = [
        'low' => 0,
        'medium' => 1,
        'high' => 2,
        'urgent' => 3,
    ];

    /**
     * Constructor.
     */
    public function __construct(?string $scenario = null)
    {
        $this->scenario = $scenario;
    }

    /**
     * Run the database seeds.
     */
    public function run(?string $tenantId = null): void
    {
        // Establecer fecha de anclaje para fechas relativas
        RelativeDate::setAnchor();
        
        // Obtener tenant_id si no se proporciona
        if (!$tenantId) {
            $tenantId = tenant('id') ?: (tenant() ? tenant()->id : null);
        }
        
        // Asegurar que existan estados de tareas
        $seeder = new TaskStateSeeder();
        $seeder->setCommand($this->command);
        $seeder->run(true, $tenantId);
        
        // Obtener estados de tareas
        $pendingState = TaskState::where('name', 'Pendiente')->first();
        $inProgressState = TaskState::where('name', 'En progreso')->first();
        $reviewState = TaskState::where('name', 'En revisión')->first();
        $completedState = TaskState::where('name', 'Completado')->first();
        
        // Crear etiquetas de demostración
        $tags = [
            ['name' => 'Bug', 'color' => '#EF4444'],
            ['name' => 'Feature', 'color' => '#3B82F6'],
            ['name' => 'Mejora', 'color' => '#10B981'],
            ['name' => 'Documentación', 'color' => '#8B5CF6'],
            ['name' => 'UI/UX', 'color' => '#EC4899'],
        ];
        
        $allTags = [];
        
        foreach ($tags as $tag) {
            $tagModel = Tag::where('name', $tag['name'])->first();
            if (!$tagModel) {
                $tagModel = Tag::create(array_merge($tag, ['is_demo' => true]));
            }
            $allTags[] = $tagModel;
        }
        
        // Obtener usuario actual o crear uno de demostración
        $user = User::first() ?? User::factory()->create([
            'name' => 'Usuario Demo',
            'email' => 'demo@example.com',
        ]);
        
        // Si se especificó un escenario, cargar desde YAML
        if ($this->scenario && $this->loadScenario($this->scenario, $user, $allTags)) {
            return;
        }
        
        // Crear proyectos de demostración predeterminados
        $demoProjects = [
            [
                'name' => 'Rediseño de Sitio Web',
                'description' => 'Proyecto para rediseñar completamente el sitio web corporativo con enfoque en experiencia de usuario y rendimiento.',
                'tasks' => [
                    [
                        'title' => 'Análisis de sitio actual',
                        'state' => $completedState,
                        'priority' => 'high',
                        'estimated_hours' => 8,
                        'tags' => ['Documentación'],
                    ],
                    [
                        'title' => 'Diseño de wireframes',
                        'state' => $completedState,
                        'priority' => 'medium',
                        'estimated_hours' => 16,
                        'tags' => ['UI/UX'],
                    ],
                    [
                        'title' => 'Diseño visual de páginas principales',
                        'state' => $inProgressState,
                        'priority' => 'high',
                        'estimated_hours' => 24,
                        'tags' => ['UI/UX'],
                    ],
                    [
                        'title' => 'Implementación frontend',
                        'state' => $pendingState,
                        'priority' => 'medium',
                        'estimated_hours' => 40,
                        'tags' => ['Feature'],
                    ],
                    [
                        'title' => 'Optimización de rendimiento',
                        'state' => $pendingState,
                        'priority' => 'low',
                        'estimated_hours' => 16,
                        'tags' => ['Mejora'],
                    ],
                ]
            ],
            [
                'name' => 'Aplicación Móvil v2.0',
                'description' => 'Desarrollo de la versión 2.0 de nuestra aplicación móvil con nuevas funcionalidades y mejoras de rendimiento.',
                'tasks' => [
                    [
                        'title' => 'Planificación de funcionalidades',
                        'state' => $completedState,
                        'priority' => 'high',
                        'estimated_hours' => 12,
                        'tags' => ['Documentación'],
                    ],
                    [
                        'title' => 'Diseño de nuevas pantallas',
                        'state' => $inProgressState,
                        'priority' => 'high',
                        'estimated_hours' => 20,
                        'tags' => ['UI/UX'],
                    ],
                    [
                        'title' => 'Implementación de autenticación biométrica',
                        'state' => $pendingState,
                        'priority' => 'medium',
                        'estimated_hours' => 16,
                        'tags' => ['Feature', 'Mejora'],
                    ],
                    [
                        'title' => 'Corrección de bugs reportados',
                        'state' => $reviewState,
                        'priority' => 'urgent',
                        'estimated_hours' => 8,
                        'tags' => ['Bug'],
                    ],
                ]
            ],
        ];
        
        $this->createProjects($demoProjects, $user, $allTags);
    }

    /**
     * Cargar escenario desde archivo YAML.
     */
    protected function loadScenario(string $scenario, User $user, array $allTags): bool
    {
        $scenarioPath = database_path("demos/{$scenario}.yaml");
        
        if (!File::exists($scenarioPath)) {
            $this->command->error("Escenario no encontrado: {$scenario}");
            return false;
        }
        
        try {
            $data = Yaml::parseFile($scenarioPath);
            
            if (isset($data['projects']) && is_array($data['projects'])) {
                $this->createProjects($data['projects'], $user, $allTags);
                return true;
            }
            
            $this->command->error("Formato de escenario inválido: {$scenario}");
            return false;
        } catch (\Exception $e) {
            $this->command->error("Error al cargar escenario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear proyectos y tareas.
     */
    protected function createProjects(array $projects, User $user, array $allTags): void
    {
        $allTagsCollection = collect($allTags);
        
        foreach ($projects as $projectData) {
            // Crear proyecto
            $project = Project::create([
                'name' => $projectData['name'],
                'description' => $projectData['description'] ?? fake()->paragraph(),
                'user_id' => $user->id,
                'status' => $projectData['status'] ?? 'active',
                'start_date' => RelativeDate::get('-30 days', '-10 days'),
                'due_date' => RelativeDate::get('+30 days', '+90 days'),
                'is_demo' => true,
            ]);
            
            $this->command->info("Proyecto demo creado: {$project->name}");
            
            // Crear tareas para el proyecto
            if (isset($projectData['tasks']) && is_array($projectData['tasks'])) {
                foreach ($projectData['tasks'] as $taskData) {
                    $taskStateId = $taskData['state']->id ?? TaskState::where('name', 'Pendiente')->first()->id;
                    $taskState = TaskState::find($taskStateId);
                    
                    $task = Task::create([
                        'title' => $taskData['title'],
                        'description' => $taskData['description'] ?? fake()->paragraph(),
                        'project_id' => $project->id,
                        'user_id' => $user->id,
                        'task_state_id' => $taskStateId,
                        'priority' => $this->priorityMap[$taskData['priority'] ?? 'medium'] ?? 1,
                        'position' => $taskData['position'] ?? rand(0, 100),
                        'estimated_hours' => $taskData['estimated_hours'] ?? rand(1, 40),
                        'start_date' => RelativeDate::get('-10 days', '-1 day'),
                        'due_date' => RelativeDate::get('+1 day', '+20 days'),
                        'completed_at' => $taskState && $taskState->is_completed ? RelativeDate::get('-5 days', '-1 day') : null,
                        'is_demo' => true,
                    ]);
                    
                    // Asignar etiquetas
                    if (!empty($taskData['tags'])) {
                        $tagIds = $allTagsCollection->whereIn('name', $taskData['tags'])->pluck('id')->toArray();
                        $task->tags()->attach($tagIds);
                    }
                    
                    // Crear comentarios para la tarea
                    $commentCount = $taskData['comments_count'] ?? rand(0, 3);
                    for ($i = 0; $i < $commentCount; $i++) {
                        Comment::create([
                            'user_id' => $user->id,
                            'task_id' => $task->id,
                            'content' => $taskData['comments'][$i] ?? fake()->paragraph(),
                            'is_demo' => true,
                        ]);
                    }
                    
                    // Crear entradas de tiempo para tareas completadas o en progreso
                    if ($taskState && ($taskState->is_completed || $taskState->name === 'En progreso')) {
                        $timeEntryCount = $taskData['time_entries_count'] ?? rand(1, 3);
                        for ($i = 0; $i < $timeEntryCount; $i++) {
                            $duration = rand(30, 180) * 60; // 30 min a 3 horas en segundos
                            $startedAt = RelativeDate::get('-10 days', '-1 day')->subHours(rand(1, 8));
                            
                            TimeEntry::create([
                                'user_id' => $user->id,
                                'project_id' => $project->id,
                                'task_id' => $task->id,
                                'description' => $taskData['time_entries'][$i] ?? "Trabajando en {$task->title}",
                                'started_at' => $startedAt,
                                'ended_at' => (clone $startedAt)->addSeconds($duration),
                                'duration' => $duration,
                                'is_billable' => true,
                                'is_demo' => true,
                            ]);
                        }
                    }
                    
                    // Crear subtareas si se especifican
                    if (isset($taskData['subtasks']) && is_array($taskData['subtasks'])) {
                        foreach ($taskData['subtasks'] as $subtaskData) {
                            $subtaskStateId = $subtaskData['state'] ?? TaskState::where('name', 'Pendiente')->first()->id;
                            $subtaskState = TaskState::find($subtaskStateId);
                            
                            Task::create([
                                'title' => $subtaskData['title'],
                                'description' => $subtaskData['description'] ?? fake()->paragraph(),
                                'project_id' => $project->id,
                                'parent_id' => $task->id,
                                'user_id' => $user->id,
                                'task_state_id' => $subtaskStateId,
                                'priority' => $this->priorityMap[$subtaskData['priority'] ?? 'medium'] ?? 1,
                                'position' => $subtaskData['position'] ?? rand(0, 100),
                                'estimated_hours' => $subtaskData['estimated_hours'] ?? rand(1, 8),
                                'start_date' => RelativeDate::get('-5 days', 'now'),
                                'due_date' => RelativeDate::get('now', '+10 days'),
                                'completed_at' => $subtaskState && $subtaskState->is_completed ? RelativeDate::get('-3 days', 'now') : null,
                                'is_demo' => true,
                            ]);
                        }
                    }
                }
            }
        }
    }
}