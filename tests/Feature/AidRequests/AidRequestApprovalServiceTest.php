<?php

namespace Tests\Feature\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Alert;
use App\Models\Family;
use App\Models\User;
use App\Services\AidRequests\AidRequestApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AidRequestApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_all_items_moves_request_to_approved_and_notifies_submitter(): void
    {
        $reviewer = User::factory()->create();
        $fieldworker = User::factory()->create();

        $family = Family::factory()->approved()->create(['created_by' => $reviewer->id]);

        $aidRequest = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $itemA = $this->createItem($aidRequest, 'مساعدة طبية');
        $itemB = $this->createItem($aidRequest, 'مساعدة غذائية');

        $this->actingAs($reviewer);

        $service = app(AidRequestApprovalService::class);
        $service->approveItems($aidRequest, [$itemA->id, $itemB->id]);

        $aidRequest->refresh();

        $this->assertSame(AidRequestStatus::Approved->value, $aidRequest->status);

        $this->assertTrue($itemA->fresh()->approved);
        $this->assertTrue($itemB->fresh()->approved);
        $this->assertNotNull($itemA->fresh()->reviewed_at);
        $this->assertSame($reviewer->id, $itemA->fresh()->reviewer_id);

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_APPROVED,
            'alertable_type' => AidRequest::class,
            'alertable_id' => $aidRequest->id,
            'notified_user_id' => $fieldworker->id,
            'created_by' => $reviewer->id,
            'status' => Alert::STATUS_ACTIVE,
        ]);
    }

    public function test_approve_some_items_moves_request_to_partially_approved_and_notifies(): void
    {
        $reviewer = User::factory()->create();
        $fieldworker = User::factory()->create();

        $family = Family::factory()->approved()->create(['created_by' => $reviewer->id]);
        $aidRequest = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $itemA = $this->createItem($aidRequest, 'مساعدة طبية');
        $itemB = $this->createItem($aidRequest, 'مساعدة غذائية');
        $this->createItem($aidRequest, 'مساعدة تعليمية'); // رابث، لن يُعتمد

        $this->actingAs($reviewer);

        app(AidRequestApprovalService::class)->approveItems($aidRequest, [$itemA->id, $itemB->id]);

        $aidRequest->refresh();

        $this->assertSame(AidRequestStatus::PartiallyApproved->value, $aidRequest->status);

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_PARTIALLY_APPROVED,
            'notified_user_id' => $fieldworker->id,
            'alertable_id' => $aidRequest->id,
        ]);
    }

    public function test_reject_unapproves_all_items_and_notifies_with_warning_severity(): void
    {
        $reviewer = User::factory()->create();
        $fieldworker = User::factory()->create();

        $family = Family::factory()->approved()->create(['created_by' => $reviewer->id]);
        $aidRequest = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => $fieldworker->id,
        ]);

        $itemA = $this->createItem($aidRequest, 'مساعدة طبية');

        $this->actingAs($reviewer);

        app(AidRequestApprovalService::class)->reject($aidRequest, 'غير مطابق للضوابط');

        $aidRequest->refresh();

        $this->assertSame(AidRequestStatus::Rejected->value, $aidRequest->status);
        $this->assertFalse($itemA->fresh()->approved);

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_REJECTED,
            'severity' => Alert::SEVERITY_WARNING,
            'notified_user_id' => $fieldworker->id,
            'alertable_id' => $aidRequest->id,
        ]);
    }

    public function test_no_notification_when_request_has_no_submitter(): void
    {
        $reviewer = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $reviewer->id]);

        // created_by اضبطه لمندوب آخر لكن submitted_by بقى null
        $fieldworker = User::factory()->create();
        $aidRequest = AidRequest::factory()->for($family)->submitted()->create([
            'created_by' => $fieldworker->id,
            'submitted_by' => null,
        ]);

        $itemA = $this->createItem($aidRequest, 'مساعدة سكن');

        $this->actingAs($reviewer);

        app(AidRequestApprovalService::class)->approveItems($aidRequest, [$itemA->id]);

        $this->assertDatabaseMissing('alerts', [
            'alertable_type' => AidRequest::class,
            'alertable_id' => $aidRequest->id,
        ]);
    }

    private function createItem(AidRequest $aidRequest, string $title): AidRequestItem
    {
        return AidRequestItem::create([
            'aid_request_id' => $aidRequest->id,
            'category_id' => 1,
            'title' => $title,
            'execution_type' => 'وقتية',
            'quantity' => 1,
            'unit_cost' => 100,
            'estimated_total' => 100,
            'recurrence_type' => 'وقتية',
            'priority' => 'عادية',
            'sort_order' => 0,
        ]);
    }
}
