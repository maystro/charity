<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Family;
use App\Models\FamilyAssessment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Alerts\ReAssessmentAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        SystemSetting::set('reassessment_interval_months', 3, 'general', 'فترة إعادة التقييم بالأشهر', 'integer');
    }

    public function test_no_alerts_for_recently_approved_family(): void
    {
        $family = Family::factory()->approved()->create();
        $assessment = FamilyAssessment::factory()->approved()->create([
            'family_id' => $family->id,
            'approved_at' => now()->subMonth(),
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        $result = app(ReAssessmentAlertService::class)->generate();

        $this->assertSame(0, $result['created']);
        $this->assertSame(0, Alert::count());
    }

    public function test_due_alert_created_for_family_past_interval(): void
    {
        $family = Family::factory()->approved()->create(['case_name' => 'أسرة متأخرة']);
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(4)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        $result = app(ReAssessmentAlertService::class)->generate();

        $this->assertSame(1, $result['created']);
        $alert = Alert::first();
        $this->assertNotNull($alert);
        $this->assertSame(Alert::TYPE_REASSESSMENT_OVERDUE, $alert->type);
        $this->assertSame(Alert::SEVERITY_CRITICAL, $alert->severity);
        $this->assertSame(Alert::STATUS_ACTIVE, $alert->status);
        $this->assertSame(Family::class, $alert->alertable_type);
        $this->assertSame($family->id, $alert->alertable_id);
    }

    public function test_no_duplicate_alerts_on_repeated_generation(): void
    {
        $family = Family::factory()->approved()->create();
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(5)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        app(ReAssessmentAlertService::class)->generate();
        $result = app(ReAssessmentAlertService::class)->generate();

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, Alert::active()->count());
    }

    public function test_alert_resolved_when_family_no_longer_due(): void
    {
        $family = Family::factory()->approved()->create();
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(5)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        // First run creates alert
        app(ReAssessmentAlertService::class)->generate();
        $this->assertSame(1, Alert::active()->count());

        // Simulate a new recent assessment approval
        $newAssessment = FamilyAssessment::factory()->approved()->create([
            'family_id' => $family->id,
            'round' => 2,
            'approved_at' => now(),
        ]);
        $family->update(['current_assessment_id' => $newAssessment->id]);

        $result = app(ReAssessmentAlertService::class)->generate();

        $this->assertSame(0, Alert::active()->count());
        $this->assertSame(1, Alert::resolved()->count());
    }

    public function test_command_outputs_summary(): void
    {
        $family = Family::factory()->approved()->create();
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(6)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        $this->artisan('app:generate-alerts')
            ->assertSuccessful()
            ->expectsOutputToContain('تم إنشاء');
    }

    public function test_interval_setting_affects_alert_generation(): void
    {
        SystemSetting::set('reassessment_interval_months', 1, 'general', 'فترة إعادة التقييم بالأشهر', 'integer');

        $family = Family::factory()->approved()->create();
        $assessment = FamilyAssessment::factory()->approvedMonthsAgo(2)->create([
            'family_id' => $family->id,
        ]);
        $family->update(['current_assessment_id' => $assessment->id]);

        $result = app(ReAssessmentAlertService::class)->generate();

        $this->assertSame(1, $result['created']);
    }
}
