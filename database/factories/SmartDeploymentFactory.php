<?php

namespace Database\Factories;

use App\Models\SmartDeployment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmartDeployment>
 */
class SmartDeploymentFactory extends Factory
{
    protected $model = SmartDeployment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mode' => 'local',
            'status' => 'success',
            'files_count' => 0,
            'total_size' => 0,
            'files_list' => [],
            'notes' => null,
            'server_response' => null,
            'started_at' => now(),
            'completed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function deploying(): static
    {
        return $this->state(fn (): array => [
            'status' => 'deploying',
            'completed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
        ]);
    }
}
