<?php

namespace Tests\Feature\Visits;

use App\Models\Family;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_visit(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test('visits.create')
            ->set('family_id', $family->id)
            ->set('visit_type', 'verification')
            ->set('priority', 'medium')
            ->set('purpose', 'زيارة تحقق')
            ->set('notes', 'ملاحظات اختبارية')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('visits', [
            'family_id' => $family->id,
            'visit_type' => 'verification',
            'purpose' => 'زيارة تحقق',
        ]);
    }

    public function test_visit_number_is_auto_generated(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test('visits.create')
            ->set('family_id', $family->id)
            ->set('visit_type', 'other')
            ->call('save')
            ->assertHasNoErrors();

        $visit = Visit::first();
        $this->assertNotNull($visit->visit_number);
        $this->assertStringStartsWith('VIS-'.now()->year.'-', $visit->visit_number);
    }

    public function test_validation_requires_family(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test('visits.create')
            ->set('visit_type', 'other')
            ->call('save')
            ->assertHasErrors(['family_id' => 'required']);
    }

    public function test_fieldworker_cannot_create_visit_for_other_users_family(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();
        $family = Family::factory()->approved()->create([
            'created_by' => $other->id,
            'submitted_by' => $other->id,
        ]);

        // الفحص الأمني يمنع الوصول — التحقق يتم في familyQuery()
        // نختبر أن العائلات المتاحة لا تشمل هذه الأسرة
        $component = Livewire::actingAs($fieldworker)
            ->test('visits.create');

        $availableFamilies = $component->viewData('families');
        $familyIds = collect($availableFamilies)->pluck('id')->all();

        $this->assertNotContains($family->id, $familyIds);
    }

    public function test_status_history_is_recorded_on_create(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test('visits.create')
            ->set('family_id', $family->id)
            ->set('visit_type', 'other')
            ->call('save')
            ->assertHasNoErrors();

        $visit = Visit::first();
        $this->assertDatabaseHas('visit_status_histories', [
            'visit_id' => $visit->id,
            'to_status' => 'scheduled',
        ]);
    }
}
