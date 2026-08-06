<?php

namespace Database\Factories;

use App\Enums\DeploymentStepStatus;
use App\Models\Deployment;
use App\Models\DeploymentStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeploymentStep>
 */
class DeploymentStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deployment_id' => Deployment::factory(),
            'key' => 'migrate',
            'label' => 'تشغيل الهجرات',
            'status' => DeploymentStepStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
            'output' => null,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the step completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeploymentStepStatus::Completed,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'output' => 'Migration ran successfully.',
        ]);
    }

    /**
     * Indicate that the step failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeploymentStepStatus::Failed,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'output' => 'Migration failed: duplicate column.',
        ]);
    }
}
