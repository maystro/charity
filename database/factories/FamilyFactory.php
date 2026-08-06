<?php

namespace Database\Factories;

use App\Enums\FamilyStatus;
use App\Models\Family;
use App\Services\Families\FamilyNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        $caseTypes = ['يتيم', 'أرملة', 'مطلقة', 'غارم', 'ذوي احتياجات', 'مسن'];
        $familyTypes = ['بسيطة', 'مركبة'];
        $communities = ['حي النور', 'حي الفيحاء', 'حي الوادي', 'حي الربيع', 'حي الأندلس', 'حي النخيل', 'حي المروج'];

        return [
            'case_number' => app(FamilyNumberGenerator::class)->generate(),
            'case_type' => fake()->randomElement($caseTypes),
            'case_name' => fake()->name(),
            'community' => fake()->randomElement($communities),
            'detailed_address' => fake()->optional(0.7)->streetAddress(),
            'phone' => '0'.fake()->numberBetween(1000000000, 9999999999),
            'family_type' => fake()->randomElement($familyTypes),
            'members_count' => fake()->numberBetween(1, 10),
            'total_income' => fake()->randomFloat(2, 0, 10000),
            'average_income_per_person' => fake()->randomFloat(2, 0, 5000),
            'status' => FamilyStatus::Approved->value,
            'created_by' => 1,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => FamilyStatus::Draft->value]);
    }

    public function underReview(): static
    {
        return $this->state(fn () => [
            'status' => FamilyStatus::UnderReview->value,
            'submitted_by' => 1,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => FamilyStatus::Approved->value,
            'approved_by' => 1,
            'approved_at' => now(),
        ]);
    }

    public function needsCompletion(): static
    {
        return $this->state(fn () => ['status' => FamilyStatus::NeedsCompletion->value]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => FamilyStatus::Rejected->value,
            'rejected_by' => 1,
            'rejected_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
