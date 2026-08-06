<?php

namespace Tests\Feature\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\Alert;
use App\Models\Family;
use App\Models\User;
use App\Services\AidRequests\DeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createItem(AidRequest $aidRequest, string $title, array $overrides = []): AidRequestItem
    {
        return AidRequestItem::create(array_merge([
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
            'approved' => true,
        ], $overrides));
    }

    // ─── startExecution ───────────────────────────────────────────────────────

    public function test_start_execution_moves_approved_request_to_in_execution(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ]);

        $this->actingAs($user);

        app(DeliveryService::class)->startExecution($aidRequest);

        $aidRequest->refresh();

        $this->assertSame(AidRequestStatus::InExecution->value, $aidRequest->status);

        $this->assertDatabaseHas('aid_request_status_histories', [
            'aid_request_id' => $aidRequest->id,
            'from_status' => AidRequestStatus::Approved->value,
            'to_status' => AidRequestStatus::InExecution->value,
            'changed_by' => $user->id,
        ]);
    }

    public function test_start_execution_moves_partially_approved_request_to_in_execution(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->partiallyApproved()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        app(DeliveryService::class)->startExecution($aidRequest);

        $aidRequest->refresh();

        $this->assertSame(AidRequestStatus::InExecution->value, $aidRequest->status);
    }

    public function test_start_execution_fails_for_non_approved_request(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->draft()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('لا يمكن بدء التنفيذ إلا للطلبات المعتمدة.');

        app(DeliveryService::class)->startExecution($aidRequest);
    }

    // ─── submitForDeliveryReview ──────────────────────────────────────────────

    public function test_submit_for_delivery_review_moves_to_pending_review(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::InExecution->value,
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ]);

        $item = $this->createItem($aidRequest, 'مساعدة غذائية');

        $this->actingAs($user);

        $itemsData = [
            [
                'id' => $item->id,
                'actual_cost' => 150.00,
                'purchase_date' => now()->toDateString(),
                'purchase_notes' => 'فاتورة رقم 123',
            ],
        ];

        app(DeliveryService::class)->submitForDeliveryReview($aidRequest, $itemsData, 'تم الشراء');

        $aidRequest->refresh();
        $item->refresh();

        $this->assertSame(AidRequestStatus::PendingDeliveryReview->value, $aidRequest->status);
        $this->assertSame(150.00, (float) $item->actual_cost);
        $this->assertSame('فاتورة رقم 123', $item->purchase_notes);
        $this->assertSame($user->id, $item->purchased_by);

        $this->assertDatabaseHas('aid_request_status_histories', [
            'aid_request_id' => $aidRequest->id,
            'from_status' => AidRequestStatus::InExecution->value,
            'to_status' => AidRequestStatus::PendingDeliveryReview->value,
            'changed_by' => $user->id,
        ]);
    }

    public function test_submit_for_delivery_review_fails_for_non_in_execution(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->approved()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('لا يمكن رفع مستندات التسليم إلا للطلبات قيد التنفيذ.');

        app(DeliveryService::class)->submitForDeliveryReview($aidRequest, [], null);
    }

    public function test_submit_for_delivery_review_fails_for_unapproved_item(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::InExecution->value,
            'created_by' => $user->id,
        ]);

        $item = $this->createItem($aidRequest, 'مساعدة غذائية', ['approved' => false]);

        $this->actingAs($user);

        $this->expectException(HttpException::class);

        app(DeliveryService::class)->submitForDeliveryReview($aidRequest, [
            ['id' => $item->id, 'actual_cost' => 100],
        ], null);
    }

    // ─── reviewAndConfirmDelivery ─────────────────────────────────────────────

    public function test_review_and_confirm_delivery_moves_to_delivered(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::PendingDeliveryReview->value,
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ]);

        $item = $this->createItem($aidRequest, 'مساعدة غذائية', [
            'actual_cost' => 150.00,
            'purchase_date' => now()->toDateString(),
        ]);

        $this->actingAs($user);

        app(DeliveryService::class)->reviewAndConfirmDelivery($aidRequest, 'تمت المراجعة بنجاح');

        $aidRequest->refresh();
        $item->refresh();

        $this->assertSame(AidRequestStatus::Delivered->value, $aidRequest->status);
        $this->assertTrue($item->delivered);
        $this->assertNotNull($item->delivery_date);
        $this->assertSame($user->id, $item->delivered_by);

        $this->assertDatabaseHas('aid_request_status_histories', [
            'aid_request_id' => $aidRequest->id,
            'from_status' => AidRequestStatus::PendingDeliveryReview->value,
            'to_status' => AidRequestStatus::Delivered->value,
            'changed_by' => $user->id,
        ]);

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_DELIVERED,
            'alertable_id' => $aidRequest->id,
            'notified_user_id' => $user->id,
        ]);
    }

    public function test_review_and_confirm_delivery_fails_for_non_pending_review(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::InExecution->value,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('لا يمكن تأكيد التسليم إلا للطلبات بانتظار مراجعة التسليم.');

        app(DeliveryService::class)->reviewAndConfirmDelivery($aidRequest);
    }

    public function test_review_and_confirm_delivery_does_not_notify_when_no_submitter(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::PendingDeliveryReview->value,
            'created_by' => $user->id,
            'submitted_by' => null,
        ]);

        $this->createItem($aidRequest, 'مساعدة غذائية');

        $this->actingAs($user);

        app(DeliveryService::class)->reviewAndConfirmDelivery($aidRequest);

        $this->assertDatabaseMissing('alerts', [
            'alertable_type' => AidRequest::class,
            'alertable_id' => $aidRequest->id,
        ]);
    }

    // ─── rejectDelivery ───────────────────────────────────────────────────────

    public function test_reject_delivery_returns_to_in_execution(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::PendingDeliveryReview->value,
            'created_by' => $user->id,
            'submitted_by' => $user->id,
        ]);

        $this->createItem($aidRequest, 'مساعدة غذائية');

        $this->actingAs($user);

        app(DeliveryService::class)->rejectDelivery($aidRequest, 'المستندات غير مكتملة');

        $aidRequest->refresh();

        $this->assertSame(AidRequestStatus::InExecution->value, $aidRequest->status);

        $this->assertDatabaseHas('aid_request_status_histories', [
            'aid_request_id' => $aidRequest->id,
            'from_status' => AidRequestStatus::PendingDeliveryReview->value,
            'to_status' => AidRequestStatus::InExecution->value,
            'changed_by' => $user->id,
            'notes' => 'المستندات غير مكتملة',
        ]);

        $this->assertDatabaseHas('alerts', [
            'type' => Alert::TYPE_AID_REQUEST_OVERDUE,
            'alertable_id' => $aidRequest->id,
            'notified_user_id' => $user->id,
        ]);
    }

    public function test_reject_delivery_fails_for_non_pending_review(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->approved()->create(['created_by' => $user->id]);
        $aidRequest = AidRequest::factory()->for($family)->create([
            'status' => AidRequestStatus::InExecution->value,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('لا يمكن رفض التسليم إلا للطلبات بانتظار مراجعة التسليم.');

        app(DeliveryService::class)->rejectDelivery($aidRequest, 'سبب');
    }
}
