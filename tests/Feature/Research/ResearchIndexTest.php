<?php

namespace Tests\Feature\Research;

use App\Models\Family;
use App\Models\SocialResearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResearchIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_social_researches(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id, 'case_name' => 'أسرة البحث']);
        SocialResearch::create([
            'family_id' => $family->id,
            'research_number' => 'RES-2026-000001',
            'research_type' => 'initial',
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test('research.index')
            ->set('search', 'RES-2026-000001')
            ->assertSee('أسرة البحث');
    }

    public function test_fieldworker_does_not_see_another_users_research(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $other = User::factory()->fieldworker()->create();
        $family = Family::factory()->approved()->create(['created_by' => $other->id, 'submitted_by' => $other->id]);
        SocialResearch::create([
            'family_id' => $family->id,
            'research_number' => 'RES-OTHER-000001',
            'created_by' => $other->id,
        ]);

        Livewire::actingAs($fieldworker)
            ->test('research.index')
            ->assertDontSee('RES-OTHER-000001');
    }

    public function test_admin_can_save_a_social_research_from_the_create_form(): void
    {
        $admin = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $admin->id]);

        Livewire::actingAs($admin)
            ->test('research.create')
            ->set('familyId', $family->id)
            ->set('researchType', 'initial')
            ->set('conductedAt', '2026-07-30')
            ->set('eligibilityDegree', 'مرتفع')
            ->set('averageIncome', '1000')
            ->set('netIncome', '800')
            ->set('recommendation', 'تستحق الأسرة الدعم.')
            ->call('submit')
            ->assertRedirect(route('research.index'));

        $this->assertDatabaseHas('social_researches', [
            'family_id' => $family->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);
    }
}
