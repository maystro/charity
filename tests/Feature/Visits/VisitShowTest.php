<?php

namespace Tests\Feature\Visits;

use App\Models\Family;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_displays_visit_details(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'visit_number' => 'VIS-2026-000001',
            'visit_type' => 'verification',
            'status' => 'scheduled',
            'purpose' => 'غرض الاختبار',
            'scheduled_at' => now()->addDays(3),
        ]);

        Livewire::actingAs($admin)
            ->test('visits.show', ['visit' => $visit])
            ->assertSee('VIS-2026-000001')
            ->assertSee($family->case_name)
            ->assertSee('غرض الاختبار');
    }

    public function test_show_displays_status_history(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'visit_number' => 'VIS-2026-000001',
            'visit_type' => 'other',
            'status' => 'scheduled',
        ]);

        VisitStatusHistory::create([
            'visit_id' => $visit->id,
            'from_status' => null,
            'to_status' => 'scheduled',
            'changed_by' => $admin->id,
            'created_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test('visits.show', ['visit' => $visit])
            ->assertSee('مجدولة');
    }

    public function test_show_page_loads_for_fieldworker_own_data(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $family = Family::factory()->approved()->create([
            'created_by' => $fieldworker->id,
        ]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'status' => 'scheduled',
            'visit_type' => 'other',
            'created_by' => $fieldworker->id,
        ]);

        Livewire::actingAs($fieldworker)
            ->test('visits.show', ['visit' => $visit])
            ->assertSee($family->case_name);
    }
}
