<?php

namespace Database\Factories;

use App\Models\ProjectPhase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectPhase>
 */
class ProjectPhaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => null, // يستلزم تعيينه يدوياً
            'name' => 'المرحلة '.fake()->word(),
            'description' => fake()->boolean(50) ? fake()->sentence() : null,
            'cost' => fake()->randomFloat(2, 1000, 100000),
            'sort_order' => fake()->randomDigit(),
        ];
    }
}
