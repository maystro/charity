<?php

namespace Database\Factories;

use App\Enums\ReleaseChangeType;
use App\Models\Release;
use App\Models\ReleaseChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReleaseChange>
 */
class ReleaseChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'release_id' => Release::factory(),
            'type' => fake()->randomElement(ReleaseChangeType::cases()),
            'file_path' => fake()->filePath(),
            'description' => fake()->sentence(),
        ];
    }
}
