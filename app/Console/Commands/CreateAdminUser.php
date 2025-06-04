<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin';
    protected $description = 'Create admin user for testing';

    public function handle()
    {
        $email = 'admin@enkiflow.test';
        
        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            $this->info("User already exists with email: $email");
            
            // Update password
            $existingUser->password = Hash::make('password123');
            $existingUser->save();
            
            $this->info("Password updated to: password123");
            return 0;
        }
        
        // Create new user
        $user = User::create([
            'name' => 'Admin Enkiflow',
            'email' => $email,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
        
        $this->info("Admin user created successfully!");
        $this->info("Email: $email");
        $this->info("Password: password123");
        
        return 0;
    }
}