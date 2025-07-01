<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = $this->faker->company();
        
        return [
            'name' => $company,
            'slug' => Str::slug($company),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'website' => $this->faker->optional(0.7)->url(),
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->optional(0.5)->phoneNumber(),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'timezone' => $this->faker->randomElement([
                'America/New_York',
                'America/Chicago',
                'America/Denver',
                'America/Los_Angeles',
                'America/Mexico_City',
                'Europe/London',
                'Europe/Madrid',
                'Europe/Paris',
                'America/Sao_Paulo',
                'America/Buenos_Aires',
            ]),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'MXN', 'BRL', 'ARS']),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'is_demo' => false,
        ];
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the client is a demo client.
     */
    public function demo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_demo' => true,
        ]);
    }

    /**
     * Indicate that the client has minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'postal_code' => null,
            'website' => null,
            'contact_name' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'notes' => null,
        ]);
    }
}