<?php

namespace Tests\Feature;

use App\Models\Fieldworker;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_a_valid_fieldworker_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('username', 'agent')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_FIELDWORKER, $user->role);

        $fieldworker = Fieldworker::query()->where('user_id', $user->id)->first();

        $this->assertNotNull($fieldworker);
        $this->assertSame('مندوب بني سويف', $fieldworker->name);
        $this->assertSame('FW-AGENT-01', $fieldworker->code);
        $this->assertSame('بني سويف', $fieldworker->governorate);
    }
}
