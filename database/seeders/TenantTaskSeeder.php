<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create tags
        $tags = [
            ['name' => 'bug', 'color' => 'red'],
            ['name' => 'feature', 'color' => 'blue'],
            ['name' => 'documentation', 'color' => 'green'],
            ['name' => 'enhancement', 'color' => 'purple'],
            ['name' => 'help-wanted', 'color' => 'orange'],
        ];

        foreach ($tags as $tag) {
            \App\Models\Tag::create($tag);
        }

        // Create sample projects if none exist
        if (\App\Models\Project::count() === 0) {
            \App\Models\Project::create([
                'name' => 'Proyecto Demo',
                'description' => 'Este es un proyecto de ejemplo para demostrar las funcionalidades de la aplicación.',
                'status' => 'active',
                'user_id' => \App\Models\User::first()->id,
            ]);
        }

        // Get all projects and users
        $projects = \App\Models\Project::all();
        $users = \App\Models\User::all();

        if ($projects->isEmpty() || $users->isEmpty()) {
            $this->command->info('No hay proyectos o usuarios para crear tareas.');
            return;
        }

        // Create tasks
        $statuses = ['pending', 'in_progress', 'completed'];
        $priorities = [0, 2, 4]; // Bajo, medio, alto

        $tasksTitles = [
            'Implementar autenticación de usuarios',
            'Diseñar interfaz de dashboard',
            'Crear API endpoints para tareas',
            'Optimizar consultas de base de datos',
            'Escribir documentación técnica',
            'Corregir bug en el módulo de reportes',
            'Añadir soporte para múltiples idiomas',
            'Implementar sistema de notificaciones',
            'Crear tests unitarios',
            'Revisar seguridad de la aplicación',
        ];

        foreach ($tasksTitles as $title) {
            $project = $projects->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            $priority = $priorities[array_rand($priorities)];
            
            $task = \App\Models\Task::create([
                'title' => $title,
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed euismod mauris vel nunc fringilla, vitae tincidunt nulla feugiat.',
                'project_id' => $project->id,
                'user_id' => $user->id,
                'status' => $status,
                'priority' => $priority,
                'due_date' => now()->addDays(rand(1, 30)),
                'completed_at' => $status === 'completed' ? now()->subDays(rand(1, 5)) : null,
            ]);
            
            // Attach random tags
            $tagIds = \App\Models\Tag::inRandomOrder()->limit(rand(1, 3))->pluck('id')->toArray();
            $task->tags()->attach($tagIds);
            
            // Add comments to some tasks
            if (rand(0, 1)) {
                $numComments = rand(1, 3);
                for ($i = 0; $i < $numComments; $i++) {
                    $task->comments()->create([
                        'content' => 'Este es un comentario de ejemplo para la tarea.',
                        'user_id' => $users->random()->id,
                    ]);
                }
            }
        }
    }
}
