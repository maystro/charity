<?php

namespace App\Services\Families;

use App\Enums\FamilyStatus;
use App\Models\Alert;
use App\Models\Family;
use App\Models\FamilyStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FamilyApprovalService
{
    /**
     * Approve a family under review.
     */
    public function approve(Family $family, ?string $notes = null): Family
    {
        return DB::transaction(function () use ($family, $notes) {
            $fromStatus = $family->status;

            $family->update([
                'status' => FamilyStatus::Approved->value,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'review_notes' => $notes,
                'updated_by' => Auth::id(),
            ]);

            FamilyStatusHistory::create([
                'family_id' => $family->id,
                'from_status' => $fromStatus,
                'to_status' => FamilyStatus::Approved->value,
                'changed_by' => Auth::id(),
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $this->notifyFieldworker($family, Alert::TYPE_FAMILY_APPROVED, 'تم اعتماد بحث الأسرة', "تم اعتماد بحث أسرة «{$family->case_name}» ({$family->case_number}).");

            return $family->fresh();
        });
    }

    /**
     * Return a family for completion.
     */
    public function returnForCompletion(Family $family, string $reason): Family
    {
        return DB::transaction(function () use ($family, $reason) {
            $fromStatus = $family->status;

            $family->update([
                'status' => FamilyStatus::NeedsCompletion->value,
                'review_notes' => $reason,
                'updated_by' => Auth::id(),
            ]);

            FamilyStatusHistory::create([
                'family_id' => $family->id,
                'from_status' => $fromStatus,
                'to_status' => FamilyStatus::NeedsCompletion->value,
                'changed_by' => Auth::id(),
                'notes' => $reason,
                'created_at' => now(),
            ]);

            return $family->fresh();
        });
    }

    /**
     * Reject a family.
     */
    public function reject(Family $family, string $reason): Family
    {
        return DB::transaction(function () use ($family, $reason) {
            $fromStatus = $family->status;

            $family->update([
                'status' => FamilyStatus::Rejected->value,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'updated_by' => Auth::id(),
            ]);

            FamilyStatusHistory::create([
                'family_id' => $family->id,
                'from_status' => $fromStatus,
                'to_status' => FamilyStatus::Rejected->value,
                'changed_by' => Auth::id(),
                'notes' => $reason,
                'created_at' => now(),
            ]);

            $this->notifyFieldworker($family, Alert::TYPE_FAMILY_REJECTED, 'تم رفض بحث الأسرة', "تم رفض بحث أسرة «{$family->case_name}» ({$family->case_number}). السبب: {$reason}", Alert::SEVERITY_WARNING);

            return $family->fresh();
        });
    }

    /**
     * إنشاء إشعار موجّه إلى المندوب الذي قام بالبحث عن الأسرة.
     */
    private function notifyFieldworker(Family $family, string $type, string $title, string $message, string $severity = Alert::SEVERITY_INFO): void
    {
        $userId = $family->submitted_by ?: $family->fieldworker?->user_id;

        if (! $userId) {
            return;
        }

        Alert::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'severity' => $severity,
            'status' => Alert::STATUS_ACTIVE,
            'alertable_type' => Family::class,
            'alertable_id' => $family->id,
            'created_by' => Auth::id(),
            'notified_user_id' => $userId,
        ]);
    }
}
