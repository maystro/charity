<?php

namespace Database\Factories;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donor>
 */
class DonorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isOrg = fake()->boolean(30);

        return [
            'name' => $isOrg ? fake()->company() : fake()->name(),
            'phone' => fake()->numerify('01#########'),
            'email' => fake()->boolean(50) ? fake()->safeEmail() : null,
            'type' => $isOrg ? 'organization' : 'individual',
            'city' => fake()->randomElement(config('governorates.egypt')),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn () => ['type' => 'individual', 'name' => fake()->name()]);
    }

    public function organization(): static
    {
        return $this->state(fn () => ['type' => 'organization', 'name' => fake()->company()]);
    }
}
