<?php

namespace Database\Factories;

use App\Models\TimeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeCategory>
 */
class TimeCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimeCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['#6366F1', '#EC4899', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6'];
        
        return [
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->randomElement($colors),
            'description' => $this->faker->sentence(),
            'is_billable_default' => $this->faker->boolean(80),
        ];
    }
    
    /**
     * Indicate that the category is billable by default.
     */
    public function billable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_billable_default' => true,
            ];
        });
    }
    
    /**
     * Indicate that the category is not billable by default.
     */
    public function nonBillable(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_billable_default' => false,
            ];
        });
    }
}
