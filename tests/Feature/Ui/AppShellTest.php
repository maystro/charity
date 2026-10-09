<?php

namespace Tests\Feature\Ui;

use App\Livewire\Shared\UpcomingExecutionStat;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Family;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_upcoming_execution_stat_shows_count_and_links_to_request_show(): void
    {
        Carbon::setTestNow('2026-10-13');

        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $aidRequest = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $admin->id,
            'submitted_by' => $admin->id,
            'title' => 'طلب تنفيذ قريب',
        ]);

        AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => 'بند',
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 50,
            'estimated_total' => 50,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
            'approved' => true,
            'execution_start_date' => '2026-10-15',
        ]);

        $this->actingAs($admin);

        Livewire::test(UpcomingExecutionStat::class)
            ->assertSee('مواعيد تنفيذ قريبة')
            ->assertSee('طلب تنفيذ قريب')
            ->assertSee(route('aid-requests.show', $aidRequest, false));

        Carbon::setTestNow();
    }

    public function test_user_preferences_component_renders_and_persists_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('user-preferences')
            ->assertSee('تفضيلات الواجهة')
            ->assertSee('لون التمييز')
            ->assertDontSee('ui.interface_preferences')
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
