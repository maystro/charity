<?php

namespace Database\Factories;

use App\Enums\DatabaseBackupStatus;
use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatabaseBackup>
 */
class DatabaseBackupFactory extends Factory
{
    protected $model = DatabaseBackup::class;

    public function definition(): array
    {
        return [
            'filename' => 'backup-'.now()->format('Ymd-His-u').'.sqlite',
            'size_bytes' => fake()->numberBetween(1024, 1048576),
            'status' => DatabaseBackupStatus::Completed,
            'created_by' => User::factory(),
        ];
    }
}
