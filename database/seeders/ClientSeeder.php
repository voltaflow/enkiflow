<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo clients
        $demoClients = [
            [
                'name' => 'Acme Corporation',
                'email' => 'info@acme.com',
                'contact_name' => 'John Smith',
                'contact_email' => 'john@acme.com',
                'phone' => '+1 (555) 123-4567',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'United States',
                'postal_code' => '10001',
                'website' => 'https://acme.com',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'is_active' => true,
                'is_demo' => true,
            ],
            [
                'name' => 'Global Tech Solutions',
                'email' => 'contact@globaltech.com',
                'contact_name' => 'Sarah Johnson',
                'contact_email' => 'sarah@globaltech.com',
                'phone' => '+1 (555) 234-5678',
                'address' => '456 Technology Drive',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'United States',
                'postal_code' => '94105',
                'website' => 'https://globaltech.com',
                'timezone' => 'America/Los_Angeles',
                'currency' => 'USD',
                'is_active' => true,
                'is_demo' => true,
            ],
            [
                'name' => 'Creative Agency Inc',
                'email' => 'hello@creativeagency.com',
                'contact_name' => 'Mike Wilson',
                'contact_email' => 'mike@creativeagency.com',
                'phone' => '+1 (555) 345-6789',
                'city' => 'Austin',
                'state' => 'TX',
                'country' => 'United States',
                'timezone' => 'America/Chicago',
                'currency' => 'USD',
                'is_active' => true,
                'is_demo' => true,
            ],
            [
                'name' => 'StartUp Ventures',
                'email' => 'info@startupventures.com',
                'contact_name' => 'Emily Chen',
                'contact_email' => 'emily@startupventures.com',
                'timezone' => 'America/Denver',
                'currency' => 'USD',
                'is_active' => true,
                'is_demo' => true,
            ],
            [
                'name' => 'Legacy Systems Ltd',
                'email' => 'support@legacysystems.com',
                'timezone' => 'Europe/London',
                'currency' => 'GBP',
                'is_active' => false, // Inactive client
                'is_demo' => true,
            ],
        ];

        foreach ($demoClients as $clientData) {
            Client::create($clientData);
        }

        // Optionally create additional random clients for testing
        if (app()->environment('local', 'staging')) {
            Client::factory()
                ->count(10)
                ->create();
        }

        // Associate some existing projects with clients
        $clients = Client::all();
        $projects = Project::whereNull('client_id')->get();

        if ($clients->isNotEmpty() && $projects->isNotEmpty()) {
            $projects->each(function ($project) use ($clients) {
                // 80% chance to assign a client to a project
                if (rand(1, 10) <= 8) {
                    $project->update([
                        'client_id' => $clients->random()->id,
                    ]);
                }
            });
        }
    }
}