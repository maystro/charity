<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'مشروع '.fake()->words(3, true),
            'description' => fake()->boolean(70) ? fake()->paragraph() : null,
            'governorate' => fake()->randomElement(config('governorates.egypt')),
            'status' => fake()->randomElement(['planning', 'active', 'completed', 'suspended', 'cancelled']),
            'total_budget' => fake()->randomFloat(2, 10000, 500000),
            'start_date' => fake()->dateTimeBetween('-1 year', '+1 month')?->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
            'created_by' => null,
        ];
    }

    public function planning(): static
    {
        return $this->state(fn () => ['status' => 'planning']);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'end_date' => now()->format('Y-m-d')]);
    }
}
