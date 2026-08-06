<?php

namespace App\Services\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Models\AidRequestStatusHistory;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AidRequestApprovalService
{
    /**
     * Approve a selection of items on the given aid request.
     *
     * - Marks the supplied item ids as approved (true) and any other items
     *   on the request as not approved (false).
     * - Sets reviewed_at / reviewer_id on each touched item.
     * - Transitions the aid request to Approved when every item is approved,
     *   otherwise to PartiallyApproved.
     * - Notifies the submitting fieldworker about the decision.
     *
     * @param  array<int, int>  $approvedItemIds  IDs of items the admin wants to approve.
     */
    public function approveItems(AidRequest $aidRequest, array $approvedItemIds, ?string $notes = null): AidRequest
    {
        return DB::transaction(function () use ($aidRequest, $approvedItemIds, $notes) {
            $reviewerId = Auth::id();
            $now = now();
            $approvedItemIds = array_map('intval', $approvedItemIds);

            $aidRequest->items()->each(function (AidRequestItem $item) use ($approvedItemIds, $reviewerId, $now) {
                $shouldBeApproved = in_array($item->id, $approvedItemIds, true);

                if ($item->approved !== $shouldBeApproved || $item->reviewed_at === null) {
                    $item->update([
                        'approved' => $shouldBeApproved,
                        'reviewed_at' => $now,
                        'reviewer_id' => $reviewerId,
                    ]);
                }
            });

            $approvedCount = $aidRequest->items()->where('approved', true)->count();
            $totalCount = $aidRequest->items()->count();

            $toStatus = ($totalCount > 0 && $approvedCount === $totalCount)
                ? AidRequestStatus::Approved
                : AidRequestStatus::PartiallyApproved;

            $fromStatus = $aidRequest->status;

            $aidRequest->update([
                'status' => $toStatus->value,
                'internal_notes' => $notes ?? $aidRequest->internal_notes,
            ]);

            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus->value,
                'changed_by' => $reviewerId,
                'notes' => $notes,
                'created_at' => $now,
            ]);

            $this->notifySubmitter(
                $aidRequest,
                $toStatus === AidRequestStatus::Approved
                    ? Alert::TYPE_AID_REQUEST_APPROVED
                    : Alert::TYPE_AID_REQUEST_PARTIALLY_APPROVED,
                $toStatus === AidRequestStatus::Approved
                    ? 'تم اعتماد طلب المساعدة'
                    : 'تم الاعتماد الجزئي لطلب المساعدة',
                $toStatus === AidRequestStatus::Approved
                    ? "تم اعتماد طلب المساعدة «{$aidRequest->title}» ({$aidRequest->request_number}) بكامل بنوده ({$approvedCount} بند)."
                    : "تم اعتماد {$approvedCount} بند من أصل {$totalCount} بنود لطلب المساعدة «{$aidRequest->title}» ({$aidRequest->request_number})."
            );

            return $aidRequest->fresh();
        });
    }

    /**
     * Reject an aid request entirely (un-approve all items).
     */
    public function reject(AidRequest $aidRequest, string $reason): AidRequest
    {
        return DB::transaction(function () use ($aidRequest, $reason) {
            $reviewerId = Auth::id();
            $now = now();

            $aidRequest->items()->update([
                'approved' => false,
                'reviewed_at' => $now,
                'reviewer_id' => $reviewerId,
            ]);

            $fromStatus = $aidRequest->status;

            $aidRequest->update([
                'status' => AidRequestStatus::Rejected->value,
                'internal_notes' => $reason,
            ]);

            AidRequestStatusHistory::create([
                'aid_request_id' => $aidRequest->id,
                'from_status' => $fromStatus,
                'to_status' => AidRequestStatus::Rejected->value,
                'changed_by' => $reviewerId,
                'notes' => $reason,
                'created_at' => $now,
            ]);

            $this->notifySubmitter(
                $aidRequest,
                Alert::TYPE_AID_REQUEST_REJECTED,
                'تم رفض طلب المساعدة',
                "تم رفض طلب المساعدة «{$aidRequest->title}» ({$aidRequest->request_number}). السبب: {$reason}",
                Alert::SEVERITY_WARNING,
            );

            return $aidRequest->fresh();
        });
    }

    /**
     * Notify the user who submitted the aid request (the fieldworker).
     */
    private function notifySubmitter(AidRequest $aidRequest, string $type, string $title, string $message, string $severity = Alert::SEVERITY_INFO): void
    {
        $userId = $aidRequest->submitted_by;

        // No submitter means the request wasn't yet pushed into review by a fieldworker.
        if (! $userId) {
            return;
        }

        Alert::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'status' => Alert::STATUS_ACTIVE,
            'alertable_type' => AidRequest::class,
            'alertable_id' => $aidRequest->id,
            'created_by' => Auth::id(),
            'notified_user_id' => $userId,
        ]);
    }
}
