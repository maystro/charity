<?php

namespace Database\Factories;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AidRequest>
 */
class AidRequestFactory extends Factory
{
    protected $model = AidRequest::class;

    public function definition(): array
    {
        return [
            'request_number' => 'AR-'.fake()->year().'-'.fake()->unique()->numerify('######'),
            'family_id' => Family::factory(),
            'created_by' => 1,
            'source_type' => 'الأسرة مباشرة',
            'request_type' => fake()->randomElement(['وقتية', 'دورية', 'طارئة']),
            'priority' => fake()->randomElement(['عادية', 'متوسطة', 'مرتفعة', 'عاجلة جداً']),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requested_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'needed_by' => fake()->dateTimeBetween('now', '+6 months'),
            'status' => AidRequestStatus::Draft->value,
            'total_estimated_amount' => fake()->randomFloat(2, 0, 10000),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => AidRequestStatus::Draft->value]);
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => AidRequestStatus::Submitted->value]);
    }

    public function needsCompletion(): static
    {
        return $this->state(fn () => ['status' => AidRequestStatus::NeedsCompletion->value]);
    }

    public function underReview(): static
    {
        return $this->state(fn () => ['status' => AidRequestStatus::UnderReview->value]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => AidRequestStatus::Approved->value]);
    }

    public function partiallyApproved(): static
    {
        return $this->state(fn () => ['status' => AidRequestStatus::PartiallyApproved->value]);
    }
}
