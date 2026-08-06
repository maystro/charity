<?php

namespace Database\Factories;

use App\Models\Fieldworker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Fieldworker>
 */
class FieldworkerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $governorates = config('governorates.egypt');

        return [
            'user_id' => null,
            'code' => 'FW-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->name(),
            'phone' => fake()->numerify('01#########'),
            'governorate' => fake()->randomElement($governorates),
            'area' => fake()->optional(0.5)->streetName(),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive']),
            'latitude' => fake()->latitude(22.0, 31.7),
            'longitude' => fake()->longitude(25.0, 35.0),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * ConvenIence: create a linked User account for this fieldworker.
     *
     * @return Factory<Fieldworker>
     */
    public function withUser(?string $username = null, ?string $password = null): Factory
    {
        $username = $username ?: Str::slug(fake()->userName()).fake()->numberBetween(10, 99);

        return $this->state(function (array $attributes) use ($username, $password) {
            return [
                'user_id' => User::create([
                    'name' => $attributes['name'] ?? fake()->name(),
                    'email' => $username.'@charity.test',
                    'username' => $username,
                    'password' => $password ?? 'password',
                    'role' => User::ROLE_FIELDWORKER,
                ])->id,
            ];
        });
    }
}
