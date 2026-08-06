<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Family;
use App\Models\User;
use App\Services\Families\FamilyApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_creates_notification_for_submitting_fieldworker(): void
    {
        $fieldworker = User::factory()->create(['role' => User::ROLE_FIELDWORKER]);
        $reviewer = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($reviewer);

        $family = Family::factory()->underReview()->create([
            'submitted_by' => $fieldworker->id,
            'case_name' => 'أسرة اختبار',
        ]);

        $service = app(FamilyApprovalService::class);
        $service->approve($family, 'ملاحظة');

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_FAMILY_APPROVED,
            'alertable_type' => Family::class,
            'alertable_id' => $family->id,
            'notified_user_id' => $fieldworker->id,
            'status' => Alert::STATUS_ACTIVE,
        ]);
    }

    public function test_reject_creates_warning_notification_for_fieldworker(): void
    {
        $fieldworker = User::factory()->create(['role' => User::ROLE_FIELDWORKER]);
        $reviewer = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($reviewer);

        $family = Family::factory()->underReview()->create([
            'submitted_by' => $fieldworker->id,
            'case_name' => 'أسرة مرفوضة',
        ]);

        app(FamilyApprovalService::class)->reject($family, 'سبب الرفض');

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_FAMILY_REJECTED,
            'alertable_id' => $family->id,
            'notified_user_id' => $fieldworker->id,
            'severity' => Alert::SEVERITY_WARNING,
        ]);
    }

    public function test_approve_without_fieldwork_user_does_not_create_notification(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($reviewer);

        // No submitted_by, no fieldworker link, no created_by resolvable to user
        $family = Family::factory()->underReview()->create([
            'submitted_by' => null,
            'created_by' => $reviewer->id, // required by NOT NULL
        ]);

        app(FamilyApprovalService::class)->approve($family);

        $this->assertDatabaseMissing('alerts', [
            'type' => Alert::TYPE_FAMILY_APPROVED,
            'alertable_id' => $family->id,
        ]);
    }
}
