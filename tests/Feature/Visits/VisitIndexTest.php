<?php

namespace Tests\Feature\Visits;

use App\Models\Family;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_visits_list(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        Visit::create(['family_id' => $family->id, 'status' => 'scheduled', 'visit_type' => 'other']);

        Livewire::actingAs($admin)
            ->test('visits.index')
            ->assertSee($family->case_name);
    }

    public function test_fieldworker_only_sees_visits_for_owned_families(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();
        $owned = Family::factory()->approved()->create(['created_by' => $fieldworker->id]);
        $notOwned = Family::factory()->approved()->create(['created_by' => $other->id]);
        Visit::create(['family_id' => $owned->id, 'status' => 'scheduled', 'visit_type' => 'other']);
        Visit::create(['family_id' => $notOwned->id, 'status' => 'scheduled', 'visit_type' => 'other']);

        Livewire::actingAs($fieldworker)
            ->test('visits.index')
            ->assertSee($owned->case_name)
            ->assertDontSee($notOwned->case_name);
    }

    public function test_visits_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        Visit::create(['family_id' => $family->id, 'status' => 'scheduled', 'visit_type' => 'other']);
        Visit::create(['family_id' => $family->id, 'status' => 'completed', 'visit_type' => 'other']);

        // عند الفلترة بـ completed، تظهر المكتملة فقط
        $component = Livewire::actingAs($admin)
            ->test('visits.index')
            ->set('status', 'completed');

        $visits = $component->viewData('visits');
        $this->assertEquals(1, $visits->total());
        $statuses = $visits->pluck('status')->all();
        $this->assertContains('completed', $statuses);
        $this->assertNotContains('scheduled', $statuses);
    }

    public function test_stats_show_correct_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);

        // Create overdue visit
        Visit::create([
            'family_id' => $family->id,
            'status' => 'scheduled',
            'visit_type' => 'other',
            'scheduled_at' => now()->subDays(3),
            'is_overdue' => true,
        ]);

        // Create completed visit
        Visit::create([
            'family_id' => $family->id,
            'status' => 'completed',
            'visit_type' => 'other',
            'scheduled_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($admin)
            ->test('visits.index')
            ->assertViewHas('overdueCount', 1)
            ->assertViewHas('completedCount', 1);
    }
}
