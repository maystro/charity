<?php

namespace Tests\Feature\Visits;

use App\Models\Family;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitExecuteTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_page_loads_for_scheduled_visit(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'visit_number' => 'VIS-2026-000001',
            'visit_type' => 'other',
            'status' => 'scheduled',
            'scheduled_at' => now()->subDay(),
        ]);

        Livewire::actingAs($admin)
            ->test('visits.execute', ['visit' => $visit])
            ->assertSee('تنفيذ الزيارة')
            ->assertSee($family->case_name);
    }

    public function test_completing_a_visit_records_status_history(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'visit_number' => 'VIS-2026-000001',
            'visit_type' => 'other',
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($admin)
            ->test('visits.execute', ['visit' => $visit])
            ->set('outcome_summary', 'تمت الزيارة بنجاح وتم التحقق من البيانات.')
            ->call('complete')
            ->assertHasNoErrors()
            ->assertRedirect(route('visits.show', $visit));

        $visit->refresh();
        $this->assertEquals('completed', $visit->status);
        $this->assertNotNull($visit->completed_at);

        $this->assertDatabaseHas('visit_status_histories', [
            'visit_id' => $visit->id,
            'to_status' => 'completed',
        ]);
    }

    public function test_save_draft_keeps_in_progress_status(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'visit_number' => 'VIS-2026-000001',
            'visit_type' => 'other',
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($admin)
            ->test('visits.execute', ['visit' => $visit])
            ->set('contacted_person', 'محمد أحمد')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $visit->refresh();
        $this->assertEquals('in_progress', $visit->status);
        $this->assertEquals('محمد أحمد', $visit->contacted_person);
    }
}
