<?php

namespace Tests\Feature\Families;

use App\Livewire\Families\Create;
use App\Livewire\Families\Index;
use App\Models\Family;
use App\Models\SocialResearch;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class FamilyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_approved_families_index_page_renders(): void
    {
        Family::factory()->approved()->create(['case_name' => 'أسرة للعرض']);

        Livewire::test(Index::class)
            ->assertOk()
            ->assertSee('أسرة للعرض');
    }

    public function test_index_only_shows_approved_families(): void
    {
        Family::factory()->approved()->create(['case_name' => 'أسرة معتمدة']);
        Family::factory()->draft()->create(['case_name' => 'أسرة مسودة']);
        Family::factory()->underReview()->create(['case_name' => 'أسرة قيد المراجعة']);

        Livewire::test(Index::class)
            ->assertSee('أسرة معتمدة')
            ->assertDontSee('أسرة مسودة')
            ->assertDontSee('أسرة قيد المراجعة');
    }

    public function test_index_shows_under_review_families_when_status_filter_is_set(): void
    {
        Family::factory()->approved()->create(['case_name' => 'أسرة معتمدة']);
        Family::factory()->underReview()->create(['case_name' => 'أسرة قيد المراجعة']);

        Livewire::test(Index::class)
            ->set('statusFilter', 'under_review')
            ->assertSee('أسرة قيد المراجعة')
            ->assertDontSee('أسرة معتمدة');
    }

    public function test_search_filters_by_case_name(): void
    {
        Family::factory()->approved()->create(['case_name' => 'أسرة الأحمد']);
        Family::factory()->approved()->create(['case_name' => 'أسرة العلي']);

        Livewire::test(Index::class)
            ->set('search', 'الأحمد')
            ->assertSee('أسرة الأحمد')
            ->assertDontSee('أسرة العلي');
    }

    public function test_search_filters_by_community(): void
    {
        Family::factory()->approved()->create([
            'case_name' => 'أسرة الشمال',
            'community' => 'حي النور',
        ]);
        Family::factory()->approved()->create([
            'case_name' => 'أسرة الجنوب',
            'community' => 'حي الوادي',
        ]);

        Livewire::test(Index::class)
            ->set('search', 'حي النور')
            ->assertSee('أسرة الشمال')
            ->assertDontSee('أسرة الجنوب');
    }

    public function test_draft_can_be_created_and_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Create::class)
            ->set('form.case_type', 'يتيم')
            ->set('form.case_name', 'أسرة تجريبية')
            ->set('form.community', 'حي النور')
            ->set('form.phone', '0501234567')
            ->set('form.family_type', 'بسيطة')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('families', [
            'status' => 'draft',
            'case_name' => 'أسرة تجريبية',
            'created_by' => $user->id,
        ]);
    }

    public function test_fieldworker_can_only_view_and_edit_owned_drafts(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $ownedFamily = Family::factory()->draft()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
            'fieldworker_id' => $fieldworker->fieldworker->id,
        ]);
        $otherFamily = Family::factory()->draft()->create();

        $this->assertTrue(Gate::forUser($fieldworker)->allows('view', $ownedFamily));
        $this->assertTrue(Gate::forUser($fieldworker)->allows('update', $ownedFamily));
        $this->assertFalse(Gate::forUser($fieldworker)->allows('view', $otherFamily));
        $this->assertFalse(Gate::forUser($fieldworker)->allows('update', $otherFamily));
    }

    public function test_only_admin_can_review_or_approve_a_family(): void
    {
        $fieldworker = User::factory()->fieldworker()->create();
        $family = Family::factory()->underReview()->create();

        $this->assertFalse(Gate::forUser($fieldworker)->allows('review', $family));
        $this->assertFalse(Gate::forUser($fieldworker)->allows('approve', $family));
        $this->assertTrue(Gate::forUser(User::factory()->admin()->create())->allows('approve', $family));
    }

    public function test_family_social_research_and_visit_relationships_are_available(): void
    {
        $user = User::factory()->admin()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $research = SocialResearch::create([
            'family_id' => $family->id,
            'research_number' => 'RES-2026-000001',
            'research_type' => 'initial',
            'status' => 'approved',
            'created_by' => $user->id,
        ]);
        $visit = Visit::create([
            'family_id' => $family->id,
            'research_id' => $research->id,
            'status' => 'scheduled',
        ]);

        $this->assertTrue($family->socialResearches->contains($research));
        $this->assertTrue($family->visits->contains($visit));
        $this->assertTrue($research->visits->contains($visit));
        $this->assertTrue($visit->family->is($family));
    }
}
