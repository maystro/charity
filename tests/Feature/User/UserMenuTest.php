<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_shows_arabic_initial_and_profile_menu_without_placeholder_links(): void
    {
        $user = User::factory()->admin()->create([
            'name' => 'مدير النظام',
        ]);

        $this->actingAs($user);

        $html = Volt::test('sidebar')
            ->assertSee('مدير النظام')
            ->html();

        $this->assertStringContainsString('>م<', preg_replace('/\s+/', '', $html) ?? $html);
        $this->assertStringNotContainsString('href="#"', $html);
        $this->assertStringNotContainsString('>?<', $html);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("open-modal', 'profile')", false)
            ->assertSee("open-modal', 'change-password')", false);
    }

    public function test_change_password_succeeds_with_valid_input(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        Volt::test('change-password')
            ->set('current_password', 'old-password')
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'new-password-1')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('new-password-1', $user->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        Volt::test('change-password')
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'new-password-1')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);
    }

    public function test_change_password_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        Volt::test('change-password')
            ->set('current_password', 'old-password')
            ->set('password', 'new-password-1')
            ->set('password_confirmation', 'other-password')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }

    public function test_profile_updates_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'اسم قديم',
            'email' => 'old@example.com',
            'username' => 'olduser',
        ]);

        $this->actingAs($user);

        Volt::test('profile')
            ->set('name', 'اسم جديد')
            ->set('email', 'new@example.com')
            ->set('username', 'newuser')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('user-profile-updated');

        $user->refresh();

        $this->assertSame('اسم جديد', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('newuser', $user->username);
    }

    public function test_profile_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $user = User::factory()->create([
            'email' => 'mine@example.com',
        ]);

        $this->actingAs($user);

        Volt::test('profile')
            ->set('email', 'taken@example.com')
            ->call('save')
            ->assertHasErrors(['email']);
    }
}
