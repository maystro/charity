<?php

namespace Tests\Feature\Deployments;

use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_deployments_index(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('deployments.index'))
            ->assertOk();
    }

    public function test_super_admin_can_access_deployments_create(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('deployments.create'))
            ->assertOk();
    }

    public function test_super_admin_can_access_deployments_show(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $release = Release::factory()->create(['created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)
            ->get(route('deployments.show', $release))
            ->assertOk();
    }

    public function test_charity_admin_cannot_access_deployments_index(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('deployments.index'))
            ->assertForbidden();
    }

    public function test_charity_admin_cannot_access_deployments_create(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('deployments.create'))
            ->assertForbidden();
    }

    public function test_user_cannot_access_deployments_index(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user)
            ->get(route('deployments.index'))
            ->assertForbidden();
    }

    public function test_fieldworker_cannot_access_deployments_index(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();

        $this->actingAs($fieldworker)
            ->get(route('deployments.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('deployments.index'))
            ->assertRedirect(route('login'));
    }
}
