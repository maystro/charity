<?php

namespace Database\Factories;

use App\Enums\ReleaseStatus;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Release>
 */
class ReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => 'v'.fake()->semver(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => ReleaseStatus::Draft,
            'source_revision' => fake()->sha1(),
            'released_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the release is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReleaseStatus::Published,
            'released_at' => now(),
        ]);
    }

    /**
     * Indicate that the release is rolled back.
     */
    public function rolledBack(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReleaseStatus::RolledBack,
            'released_at' => now()->subDay(),
        ]);
    }
}
