<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AppShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('لوحة التحكم');
    }

    public function test_sidebar_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        Volt::test('sidebar')
            ->assertSee($user->name);

        Volt::test('sidebar')
            ->assertSee('data-sidebar-scroll')
            ->assertDontSee('الفروع والمناطق')
            ->assertSee('الحالات والمساعدات')
            ->assertSee('التنفيذ والمتابعة')
            ->assertDontSee('حالات تحت المراجعة')
            ->assertDontSee('الزيارات والمتابعة');
    }

    public function test_user_preferences_component_renders_and_persists_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('user-preferences')
            ->set('accentColor', 'emerald')
            ->set('fontSize', 'large')
            ->set('uiDensity', 'spacious')
            ->set('reducedMotion', true)
            ->set('sidebarCollapsed', true)
            ->assertSet('accentColor', 'emerald');

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'accent_color' => 'emerald',
            'font_size' => 'large',
            'ui_density' => 'spacious',
            'reduced_motion' => 1,
            'sidebar_state' => 'collapsed',
        ]);
    }
}
