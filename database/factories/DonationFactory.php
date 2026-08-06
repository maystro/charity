<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $donor = Donor::factory()->create();
        $isInKind = fake()->boolean(25);

        return [
            'donor_id' => $donor->id,
            'project_id' => null,
            'donor_name' => $donor->name,
            'donor_type' => $donor->type->value,
            'amount' => fake()->randomFloat(2, 100, 50000),
            'method' => fake()->randomElement(['cash', 'e_wallet', 'instapay', 'bank_account']),
            'type' => $isInKind ? 'in_kind' : 'cash',
            'donated_at' => fake()->dateTimeBetween('-6 months', 'now')?->format('Y-m-d'),
            'notes' => fake()->boolean(20) ? fake()->sentence() : null,
            'created_by' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => ['type' => 'cash']);
    }

    public function inKind(): static
    {
        return $this->state(fn () => ['type' => 'in_kind']);
    }
}
