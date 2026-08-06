<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Family;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'visit_type' => fake()->randomElement(VisitType::cases())->value,
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'purpose' => fake()->sentence(),
            'family_id' => Family::factory()->approved(),
            'scheduled_at' => fake()->dateTimeBetween('-1 week', '+1 week'),
            'notes' => fake()->optional()->paragraph(),
            'status' => VisitStatus::Scheduled->value,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => VisitStatus::Scheduled->value,
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => VisitStatus::Completed->value,
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
            'started_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
            'completed_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
            'outcome_summary' => fake()->paragraph(),
            'completed_by' => null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => VisitStatus::Scheduled->value,
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '-2 days'),
            'is_overdue' => true,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => VisitStatus::InProgress->value,
            'scheduled_at' => fake()->dateTimeBetween('-1 day', 'now'),
            'started_at' => now(),
        ]);
    }

    public function notCompleted(): static
    {
        return $this->state(fn () => [
            'status' => VisitStatus::NotCompleted->value,
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
            'started_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
            'not_completed_reason' => fake()->sentence(),
        ]);
    }
}
