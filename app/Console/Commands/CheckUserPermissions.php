<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Space;
use App\Models\SpaceUser;
use Illuminate\Console\Command;

class CheckUserPermissions extends Command
{
    protected $signature = 'check:user-permissions {--tenant=} {--email=}';
    
    protected $description = 'Check user permissions in tenant';
    
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $email = $this->option('email');
        
        if (!$tenantId || !$email) {
            $this->error('Please provide both --tenant= and --email=');
            return Command::FAILURE;
        }
        
        // Initialize tenant
        $space = Space::find($tenantId);
        if (!$space) {
            $this->error("Tenant not found: {$tenantId}");
            return Command::FAILURE;
        }
        
        $this->info("Checking permissions in tenant: {$space->name} (ID: {$space->id})");
        tenancy()->initialize($space);
        
        // Find user
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User not found: {$email}");
            return Command::FAILURE;
        }
        
        $this->info("\nUser: {$user->email} (ID: {$user->id})");
        
        // Check if user is space owner
        if ($space->owner_id === $user->id) {
            $this->info("User is the OWNER of this space - has all permissions");
        }
        
        // Check SpaceUser record
        $spaceUser = SpaceUser::where('tenant_id', $space->id)
            ->where('user_id', $user->id)
            ->first();
        
        if ($spaceUser) {
            $this->info("\nSpaceUser record found:");
            $this->line("  Role: {$spaceUser->role->value}");
            $this->line("  Permissions: " . json_encode($spaceUser->getPermissions()));
            
            // Check specific permissions
            $permissions = [
                \App\Enums\SpacePermission::VIEW_ALL_PROJECTS,
                \App\Enums\SpacePermission::CREATE_PROJECTS,
                \App\Enums\SpacePermission::EDIT_PROJECTS,
                \App\Enums\SpacePermission::DELETE_PROJECTS
            ];
            
            $this->info("\nProject permissions:");
            foreach ($permissions as $permission) {
                $hasPermission = $spaceUser->hasPermission($permission);
                $this->line("  {$permission->value}: " . ($hasPermission ? 'YES' : 'NO'));
            }
        } else {
            $this->warn("\nNo SpaceUser record found for this user in this space!");
        }
        
        tenancy()->end();
        
        return Command::SUCCESS;
    }
}