<?php

namespace App\Livewire\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Models\AidRequestItem;
use App\Services\AidRequests\AidRequestApprovalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'عرض طلب مساعدة'])]
class ShowAidRequest extends Component
{
    public AidRequest $aidRequest;

    /** @var array<int, int> IDs of items the admin has marked for approval in the review panel. */
    public array $approvedItemIds = [];

    public ?string $reviewNotes = null;

    public function mount(AidRequest $aidRequest): void
    {
        $this->authorize('view', $aidRequest);
        $this->aidRequest = $aidRequest;

        // Pre-select currently-approved items so the review panel reflects the saved state.
        $this->approvedItemIds = $aidRequest->items()
            ->where('approved', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    #[Computed]
    public function statusLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->aidRequest->status));
    }

    /**
     * Items visible to the current user.
     * - Admin: all items.
     * - Fieldworker (the submitter) : only items that have been approved via review.
     */
    #[Computed]
    public function visibleItems()
    {
        $user = Auth::user();

        if ($user && $user->isFieldworker()) {
            return $this->aidRequest->items()->where('approved', true)->orderBy('sort_order')->get();
        }

        return $this->aidRequest->items()->orderBy('sort_order')->get();
    }

    /**
     * Whether the current user can see approval controls on this aid request (admins only).
     */
    #[Computed]
    public function canReviewItems(): bool
    {
        $user = Auth::user();

        return $user && $user->isAdmin();
    }

    /**
     * Whether the request is still reviewable by an admin.
     */
    #[Computed]
    public function isReviewable(): bool
    {
        return in_array($this->aidRequest->status, AidRequestStatus::underReviewStatuses(), true);
    }

    public function render(): View
    {
        return view('livewire.pages.aid-requests.show', [
            'aidRequest' => $this->aidRequest,
        ]);
    }

    /**
     * Toggle an item's membership in the approvedItemIds selection.
     */
    public function toggleApproval(int $itemId): void
    {
        $key = array_search($itemId, $this->approvedItemIds, true);

        if ($key === false) {
            $this->approvedItemIds[] = $itemId;
        } else {
            unset($this->approvedItemIds[$key]);
        }

        $this->approvedItemIds = array_values($this->approvedItemIds);
    }

    /**
     * Trigger browser print dialog.
     */
    public function printPage(): void
    {
        $this->dispatch('print-page');
    }

    /**
     * Persist the admin's approval selection onto the aid request.
     */
    public function saveApproval(AidRequestApprovalService $service): void
    {
        $this->authorizeReview();

        $this->validate([
            'approvedItemIds' => ['array'],
            'approvedItemIds.*' => ['integer', 'exists:aid_request_items,id'],
            'reviewNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $belongsToRequest = AidRequestItem::where('aid_request_id', $this->aidRequest->id)
            ->whereIn('id', $this->approvedItemIds)
            ->pluck('id')
            ->all();

        $service->approveItems($this->aidRequest, $belongsToRequest, $this->reviewNotes);

        $this->aidRequest->refresh();
        unset($this->visibleItems, $this->isReviewable);

        $this->dispatch('aid-request-reviewed', id: $this->aidRequest->id);
        $this->dispatch('toast', message: 'تم حفظ اعتماد البنود بنجاح.', type: 'success');
    }

    /**
     * Reject the aid request entirely.
     */
    public function rejectRequest(AidRequestApprovalService $service): void
    {
        $this->authorizeReview();

        $this->validate([
            'reviewNotes' => ['required', 'string', 'max:1000'],
        ]);

        $service->reject($this->aidRequest, $this->reviewNotes);

        $this->aidRequest->refresh();
        $this->approvedItemIds = [];
        unset($this->visibleItems, $this->isReviewable);

        $this->dispatch('aid-request-reviewed', id: $this->aidRequest->id);
        $this->dispatch('toast', message: 'تم رفض طلب المساعدة.', type: 'warning');
    }

    private function authorizeReview(): void
    {
        if (! $this->canReviewItems) {
            abort(403, 'غير مصرح لك بالاعتماد.');
        }

        if (! $this->isReviewable) {
            abort(422, 'لا يمكن مراجعة هذا الطلب في حالته الحالية.');
        }
    }
}
