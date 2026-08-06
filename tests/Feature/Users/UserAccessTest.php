<?php

namespace Tests\Feature\Users;

use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('المستخدمون والصلاحيات');
    }

    public function test_user_role_can_access_only_allowed_menu_items(): void
    {
        $user = User::factory()->user()->create([
            'menu_abilities' => ['projects.index'],
        ]);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk();

        Volt::test('sidebar')
            ->assertSee('المشروعات')
            ->assertDontSee('إدارة النظام');
    }

    public function test_admin_can_create_user_with_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openCreateModal')
            ->assertSee('إضافة مستخدم جديد')
            ->set('form.name', 'مستخدم تجريبي')
            ->set('form.email', 'user1@charity.test')
            ->set('form.username', 'user1')
            ->set('form.role', User::ROLE_USER)
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password')
            ->set('form.menu_abilities', ['projects.index', 'donations.index'])
            ->call('save');

        $user = User::query()->where('username', 'user1')->firstOrFail();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertSame(['projects.index', 'donations.index'], $user->menuAbilities());
        $this->assertTrue(password_verify('password', $user->password));
    }

    public function test_admin_can_edit_user_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create([
            'name' => 'مستخدم قديم',
            'email' => 'old@charity.test',
            'username' => 'olduser',
            'menu_abilities' => ['projects.index'],
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('openEditModal', $user->id)
            ->set('form.name', 'مستخدم محدث')
            ->set('form.menu_abilities', ['projects.index', 'donations.index', 'families.index'])
            ->call('save');

        $user->refresh();

        $this->assertSame('مستخدم محدث', $user->name);
        $this->assertSame(['projects.index', 'donations.index', 'families.index'], $user->menuAbilities());
    }

    public function test_admin_can_delete_user_but_not_self(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->user()->create([
            'username' => 'to-delete',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('delete', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('delete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
