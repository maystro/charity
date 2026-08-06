<?php

namespace Database\Factories;

use App\Enums\FamilyStatus;
use App\Models\FamilyAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyAssessment>
 */
class FamilyAssessmentFactory extends Factory
{
    protected $model = FamilyAssessment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $caseTypes = ['يتيم', 'أرملة', 'مطلقة', 'غارم', 'ذوي احتياجات', 'مسن'];
        $familyTypes = ['بسيطة', 'مركبة'];
        $communities = ['حي النور', 'حي الفيحاء', 'حي الوادي', 'حي الربيع', 'حي الأندلس', 'حي النخيل', 'حي المروج'];

        return [
            'round' => 1,
            'status' => FamilyStatus::Approved->value,
            'case_type' => fake()->randomElement($caseTypes),
            'case_name' => fake()->name(),
            'community' => fake()->randomElement($communities),
            'detailed_address' => fake()->optional(0.7)->streetAddress(),
            'phone' => '0'.fake()->numberBetween(1000000000, 9999999999),
            'family_type' => fake()->randomElement($familyTypes),
            'members_count' => fake()->numberBetween(1, 10),
            'total_income' => fake()->randomFloat(2, 0, 10000),
            'average_income_per_person' => fake()->randomFloat(2, 0, 5000),
            'approved_by' => 1,
            'approved_at' => now(),
            'created_by' => 1,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => FamilyStatus::Approved->value,
            'approved_by' => 1,
            'approved_at' => now(),
        ]);
    }

    public function approvedMonthsAgo(int $months): static
    {
        return $this->state(fn () => [
            'status' => FamilyStatus::Approved->value,
            'approved_by' => 1,
            'approved_at' => now()->subMonths($months),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => FamilyStatus::Draft->value]);
    }
}
