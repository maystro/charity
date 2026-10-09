<?php

namespace App\Services\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\AidRequestStatusHistory;
use App\Models\Alert;
use App\Services\ExecutionSchedule\RecurringExecutionCycle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    /**
     * بدء تنفيذ طلب مساعدة معتمد.
     * المندوب يبدأ مرحلة الشراء: approved/partially_approved → in_execution.
     */
    public function startExecution(AidRequest $aidRequest): AidRequest
    {
        return DB::transaction(function () use ($aidRequest) {
            $fromStatus = $aidRequest->status;

            if (! in_array($fromStatus, AidRequestStatus::approvedStatuses(), true)) {
                abort(422, 'لا يمكن بدء التنفيذ إلا للطلبات المعتمدة.');
            }

            $aidRequest->update([
                'status' => AidRequestStatus::InExecution->value,
            ]);

            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => AidRequestStatus::InExecution->value,
                'changed_by' => Auth::id(),
                'created_at' => now(),
            ]);

            return $aidRequest->fresh();
        });
    }

    /**
     * المندوب يرفع التكاليف الحقيقية ومستندات الشراء لمراجعة الإدارة.
     * in_execution → pending_delivery_review.
     *
     * @param  array<int, array{id: int, actual_cost: float, purchase_date?: string, purchase_notes?: string}>  $itemsData
     */
    public function submitForDeliveryReview(AidRequest $aidRequest, array $itemsData, ?string $generalNotes = null): AidRequest
    {
        return DB::transaction(function () use ($aidRequest, $itemsData, $generalNotes) {
            if ($aidRequest->status !== AidRequestStatus::InExecution->value) {
                abort(422, 'لا يمكن رفع مستندات التسليم إلا للطلبات قيد التنفيذ.');
            }

            $approvedItemIds = $aidRequest->items()
                ->where('approved', true)
                ->pluck('id')
                ->toArray();

            $itemIds = array_column($itemsData, 'id');
            $itemsById = $aidRequest->items()
                ->whereIn('id', $itemIds)
                ->get()
                ->keyBy('id');

            foreach ($itemsData as $itemData) {
                $itemId = $itemData['id'];

                if (! in_array($itemId, $approvedItemIds, true)) {
                    abort(422, "البند #{$itemId} غير معتمد أو غير موجود في هذا الطلب.");
                }

                $item = $itemsById->get($itemId);

                if (! $item) {
                    abort(422, "البند #{$itemId} غير موجود في هذا الطلب.");
                }

                $item->update([
                    'actual_cost' => $itemData['actual_cost'],
                    'purchase_date' => $itemData['purchase_date'] ?? now()->toDateString(),
                    'purchase_notes' => $itemData['purchase_notes'] ?? null,
                    'purchased_by' => Auth::id(),
                ]);
            }

            $fromStatus = $aidRequest->status;

            $aidRequest->update([
                'status' => AidRequestStatus::PendingDeliveryReview->value,
                'delivery_notes' => $generalNotes,
            ]);

            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => AidRequestStatus::PendingDeliveryReview->value,
                'changed_by' => Auth::id(),
                'notes' => $generalNotes,
                'created_at' => now(),
            ]);

            return $aidRequest->fresh();
        });
    }

    /**
     * الإدارة تراجع وتؤكد التسليم.
     * pending_delivery_review → delivered.
     */
    public function reviewAndConfirmDelivery(AidRequest $aidRequest, ?string $reviewNotes = null): AidRequest
    {
        return DB::transaction(function () use ($aidRequest, $reviewNotes) {
            if ($aidRequest->status !== AidRequestStatus::PendingDeliveryReview->value) {
                abort(422, 'لا يمكن تأكيد التسليم إلا للطلبات بانتظار مراجعة التسليم.');
            }

            $fromStatus = $aidRequest->status;
            $deliveryDate = now()->toDateString();
            $cycleHelper = app(RecurringExecutionCycle::class);
            $hasContinuingRecurring = false;

            $aidRequest->items()
                ->where('approved', true)
                ->get()
                ->each(function (AidRequestItem $item) use ($deliveryDate, $cycleHelper, &$hasContinuingRecurring): void {
                    $item->update([
                        'delivered' => true,
                        'delivery_date' => $deliveryDate,
                        'delivered_by' => Auth::id(),
                    ]);

                    $this->resolveExecutionAlertsForItem($item);

                    $item->refresh();

                    if ($item->isRecurring() && $cycleHelper->hasAnotherCycle($item)) {
                        $hasContinuingRecurring = true;
                        $item->update([
                            'delivered' => false,
                        ]);
                    }
                });

            $toStatus = $hasContinuingRecurring
                ? AidRequestStatus::Approved->value
                : AidRequestStatus::Delivered->value;

            $aidRequest->update([
                'status' => $toStatus,
            ]);

            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => Auth::id(),
                'notes' => $reviewNotes,
                'created_at' => now(),
            ]);

            // إشعار المندوب بأن التسليم تم تأكيده
            if ($aidRequest->submitted_by) {
                Alert::create([
                    'type' => Alert::TYPE_AID_REQUEST_DELIVERED,
                    'title' => 'تم تأكيد تسليم طلب المساعدة',
                    'message' => "تم تأكيد تسليم طلب المساعدة «{$aidRequest->title}» ({$aidRequest->request_number}) من قبل الإدارة.",
                    'severity' => Alert::SEVERITY_INFO,
                    'status' => Alert::STATUS_ACTIVE,
                    'alertable_type' => AidRequest::class,
                    'alertable_id' => $aidRequest->id,
                    'created_by' => Auth::id(),
                    'notified_user_id' => $aidRequest->submitted_by,
                ]);
            }

            return $aidRequest->fresh();
        });
    }

    protected function resolveExecutionAlertsForItem(AidRequestItem $item): void
    {
        Alert::active()
            ->forAlertable($item)
            ->whereIn('type', [
                Alert::TYPE_AID_EXECUTION_DUE,
                Alert::TYPE_AID_EXECUTION_OVERDUE,
            ])
            ->get()
            ->each(fn (Alert $alert) => $alert->resolve());
    }

    /**
     * الإدارة ترفض التسليم وتعيد الطلب للمندوب لاستكمال النواقص.
     * pending_delivery_review → in_execution.
     */
    public function rejectDelivery(AidRequest $aidRequest, string $rejectionReason): AidRequest
    {
        return DB::transaction(function () use ($aidRequest, $rejectionReason) {
            if ($aidRequest->status !== AidRequestStatus::PendingDeliveryReview->value) {
                abort(422, 'لا يمكن رفض التسليم إلا للطلبات بانتظار مراجعة التسليم.');
            }

            $fromStatus = $aidRequest->status;

            $aidRequest->update([
                'status' => AidRequestStatus::InExecution->value,
            ]);

            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => AidRequestStatus::InExecution->value,
                'changed_by' => Auth::id(),
                'notes' => $rejectionReason,
                'created_at' => now(),
            ]);

            // إشعار المندوب بسبب الرفض
            if ($aidRequest->submitted_by) {
                Alert::create([
                    'type' => Alert::TYPE_AID_REQUEST_OVERDUE,
                    'title' => 'تم إعادة طلب المساعدة للمراجعة',
                    'message' => "تم إعادة طلب المساعدة «{$aidRequest->title}» ({$aidRequest->request_number}) للمراجعة. السبب: {$rejectionReason}",
                    'severity' => Alert::SEVERITY_WARNING,
                    'status' => Alert::STATUS_ACTIVE,
                    'alertable_type' => AidRequest::class,
                    'alertable_id' => $aidRequest->id,
                    'created_by' => Auth::id(),
                    'notified_user_id' => $aidRequest->submitted_by,
                ]);
            }

            return $aidRequest->fresh();
        });
    }

    /**
     * استعلام الطلبات الجاهزة للتنفيذ (معتمدة ولم يبدأ تنفيذها بعد).
     *
     * @return Builder<AidRequest>
     */
    public function readyRequestsQuery(): Builder
    {
        return AidRequest::query()
            ->whereIn('status', AidRequestStatus::approvedStatuses())
            ->latest();
    }

    /**
     * @return Builder<AidRequest>
     */
    public function inExecutionRequestsQuery(): Builder
    {
        return AidRequest::query()
            ->where('status', AidRequestStatus::InExecution->value)
            ->latest();
    }

    /**
     * @return Builder<AidRequest>
     */
    public function pendingReviewRequestsQuery(): Builder
    {
        return AidRequest::query()
            ->where('status', AidRequestStatus::PendingDeliveryReview->value)
            ->latest();
    }

    /**
     * @return Builder<AidRequest>
     */
    public function deliveredRequestsQuery(): Builder
    {
        return AidRequest::query()
            ->where('status', AidRequestStatus::Delivered->value)
            ->latest();
    }

    /**
     * @return Builder<AidRequest>
     */
    public function overdueRequestsQuery(): Builder
    {
        return AidRequest::query()
            ->where('status', AidRequestStatus::InExecution->value)
            ->whereNotNull('needed_by')
            ->where('needed_by', '<', now()->toDateString())
            ->latest();
    }
}
