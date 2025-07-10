<?php

namespace App\Console\Commands;

use App\Models\Space;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestProjectUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:project-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test project user relationships';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the tenant
        $space = Space::find('3dbb2f8e-a3b6-46af-be2c-38efb99e8fff'); // continental

        if (!$space) {
            $this->error('Space not found');
            return 1;
        }

        // Initialize tenant context
        tenancy()->initialize($space);

        $this->info("Space: {$space->name}");
        $this->info("Space ID: {$space->id}");
        $this->newLine();

        // Get all users in the space
        $spaceUsers = $space->users()->get();
        $this->info("Total users in space: " . $spaceUsers->count());

        foreach ($spaceUsers as $user) {
            $this->line("- User: {$user->name} (ID: {$user->id}, Email: {$user->email})");
        }

        $this->newLine();

        // Get first project
        $project = Project::first();
        if ($project) {
            $this->info("Project: {$project->name} (ID: {$project->id})");
            
            // Get assigned users using raw query
            $assignedUserIds = DB::connection('tenant')
                ->table('project_user')
                ->where('project_id', $project->id)
                ->pluck('user_id')
                ->toArray();
            
            $this->info("Assigned user IDs: " . json_encode($assignedUserIds));
            
            if (!empty($assignedUserIds)) {
                $assignedUsers = User::whereIn('id', $assignedUserIds)->get();
                $this->info("Assigned users: " . $assignedUsers->count());
                
                // Get pivot data
                $pivotData = DB::connection('tenant')
                    ->table('project_user')
                    ->where('project_id', $project->id)
                    ->whereIn('user_id', $assignedUserIds)
                    ->get()
                    ->keyBy('user_id');
                
                foreach ($assignedUsers as $user) {
                    $pivot = $pivotData[$user->id] ?? null;
                    $role = $pivot->role ?? 'unknown';
                    $this->line("- {$user->name} - Role: {$role}");
                }
            } else {
                $this->info("No users assigned to this project");
            }
            
            // Get available users
            $availableUsers = $space->users()
                ->whereNotIn('users.id', $assignedUserIds)
                ->get();
            
            $this->newLine();
            $this->info("Available users for assignment: " . $availableUsers->count());
            foreach ($availableUsers as $user) {
                $this->line("- {$user->name}");
            }

            // Test assigning a user if there are available users
            if ($availableUsers->count() > 0) {
                $this->newLine();
                $userToAssign = $availableUsers->first();
                $this->info("Testing assignment of user: {$userToAssign->name}");
                
                try {
                    DB::connection('tenant')
                        ->table('project_user')
                        ->insert([
                            'project_id' => $project->id,
                            'user_id' => $userToAssign->id,
                            'role' => 'member',
                            'custom_rate' => null,
                            'all_current_projects' => false,
                            'all_future_projects' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    
                    $this->info("✓ User assigned successfully!");
                    
                    // Verify assignment
                    $assignedCount = DB::connection('tenant')
                        ->table('project_user')
                        ->where('project_id', $project->id)
                        ->count();
                    $this->info("Total assigned users now: {$assignedCount}");
                } catch (\Exception $e) {
                    $this->error("Failed to assign user: " . $e->getMessage());
                }
            }
        } else {
            $this->error('No projects found');
        }

        return 0;
    }
}