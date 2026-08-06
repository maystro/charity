<?php

namespace Database\Factories;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deployment>
 */
class DeploymentFactory extends Factory
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
            'environment' => fake()->randomElement(DeploymentEnvironment::cases()),
            'status' => DeploymentStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
            'source_revision' => null,
            'failure_reason' => null,
            'output_log' => null,
            'created_by' => User::factory(),
            'rolled_back_at' => null,
        ];
    }

    /**
     * Indicate that the deployment completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeploymentStatus::Completed,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'source_revision' => fake()->sha1(),
        ]);
    }

    /**
     * Indicate that the deployment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeploymentStatus::Failed,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'failure_reason' => 'Migration failed: duplicate column',
        ]);
    }
}
