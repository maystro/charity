<?php

namespace Tests\Feature;

use App\Livewire\Shared\ReAssessmentAlertsStat;
use App\Models\Family;
use App\Models\FamilyAssessment;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReAssessmentAlertsStatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        SystemSetting::set('reassessment_interval_months', 3, 'general', 'فترة إعادة التقييم بالأشهر', 'integer');
    }

    public function test_due_count_is_zero_when_no_families_are_due(): void
    {
        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSet('dueCount', 0);
    }

    public function test_due_count_reflects_families_past_the_interval(): void
    {
        // Approved 4 months ago → past the 3-month interval → due
        $family = Family::factory()->approved()->create(['case_name' => 'أسرة مستحقة']);
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(4)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        // Recently approved family → not due
        $recent = Family::factory()->approved()->create(['case_name' => 'أسرة حديثة']);
        $recentAssessment = FamilyAssessment::factory()->approved()->create([
            'family_id' => $recent->id,
        ]);
        $recent->update(['current_assessment_id' => $recentAssessment->id]);

        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSet('dueCount', 1);
    }

    public function test_due_count_excludes_non_approved_families(): void
    {
        // Family in draft state — should not count
        $family = Family::factory()->draft()->create();
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(5)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSet('dueCount', 0);
    }

    public function test_top_due_families_returns_families_ordered_by_approval_date(): void
    {
        $this->makeFamilyApprovedMonthsAgo(8, 'الأقدم');
        $this->makeFamilyApprovedMonthsAgo(6, 'الأوسط');
        $this->makeFamilyApprovedMonthsAgo(4, 'الأحدث');

        $component = Livewire::test(ReAssessmentAlertsStat::class);
        $instance = $component->instance();
        $topDueFamilies = $instance->topDueFamilies;

        $this->assertCount(3, $topDueFamilies);
        $this->assertSame('الأقدم', $topDueFamilies->first()->case_name);
        $this->assertSame('الأحدث', $topDueFamilies->last()->case_name);
    }

    public function test_top_due_families_caps_at_five(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->makeFamilyApprovedMonthsAgo(5 + $i, "عائلة {$i}");
        }

        $component = Livewire::test(ReAssessmentAlertsStat::class);
        $instance = $component->instance();
        $topDueFamilies = $instance->topDueFamilies;

        $this->assertCount(5, $topDueFamilies);
    }

    public function test_interval_setting_affects_due_count(): void
    {
        SystemSetting::set('reassessment_interval_months', 12, 'general', 'فترة إعادة التقييم بالأشهر', 'integer');

        // Approved 6 months ago — would be due with 3-month interval, NOT due with 12-month
        $family = Family::factory()->approved()->create();
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(6)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSet('dueCount', 0);
    }

    public function test_view_all_redirects_to_alerts_index(): void
    {
        Livewire::test(ReAssessmentAlertsStat::class)
            ->call('viewAll')
            ->assertRedirect(route('alerts.index'));
    }

    public function test_trigger_renders_as_button_with_pointer_cursor(): void
    {
        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSee('cursor-pointer', false)
            ->assertSee('type="button"', false);
    }

    public function test_dropdown_footer_links_to_re_assessment_index(): void
    {
        $expected = route('families.re-assessment-index');

        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSee('href="'.$expected.'"', false);
    }

    public function test_renders_with_no_alerts_state(): void
    {
        // When no families are due, the "no alerts" label is shown
        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSee(__('ui.no_alerts'));
    }

    public function test_renders_with_overdue_label_when_families_are_past_interval(): void
    {
        // 5 months past the 3-month interval → overdue
        $this->makeFamilyApprovedMonthsAgo(5, 'أسرة');

        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSet('dueCount', 1)
            ->assertSee(__('ui.reassessment_overdue'));
    }

    public function test_renders_with_due_label_when_family_is_due_but_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-30 10:00:00'));

        $this->makeFamilyApprovedMonthsAgo(3, 'أسرة مستحقة');

        Livewire::test(ReAssessmentAlertsStat::class)
            ->assertSet('dueCount', 1)
            ->assertSet('overdueCount', 0)
            ->assertSee(__('ui.reassessment_due'))
            ->assertDontSee(__('ui.reassessment_overdue'));

        Carbon::setTestNow();
    }

    public function test_active_alerts_count_starts_at_zero(): void
    {
        $component = Livewire::test(ReAssessmentAlertsStat::class);

        $this->assertSame(0, $component->instance()->activeAlertsCount);
    }

    /**
     * Helper: create an approved family whose current assessment was approved N months ago.
     */
    protected function makeFamilyApprovedMonthsAgo(int $months, string $name): Family
    {
        $family = Family::factory()->approved()->create(['case_name' => $name]);
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo($months)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        return $family;
    }
}
