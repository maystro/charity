<?php

namespace Tests\Feature\Backups;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseBackupAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_backups_index(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('backups.index'))
            ->assertOk();
    }

    public function test_charity_admin_cannot_access_backups_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('backups.index'))
            ->assertForbidden();
    }

    public function test_user_cannot_access_backups_index(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user)
            ->get(route('backups.index'))
            ->assertForbidden();
    }

    public function test_fieldworker_cannot_access_backups_index(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();

        $this->actingAs($fieldworker)
            ->get(route('backups.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backups.index'))
            ->assertRedirect(route('login'));
    }
}
