<?php

namespace App\Livewire\Delivery;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use App\Services\AidRequests\DeliveryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'التنفيذ والمتابعة'])]
class DeliveryIndex extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** التبويب النشط */
    public string $tab = 'ready';

    public string $search = '';

    public string $sort = 'newest';

    /** معرّف الطلب المفتوح في نافذة التسليم */
    public ?int $activeRequestId = null;

    /** ملاحظات عامة للتسليم */
    public ?string $deliveryNotes = null;

    /**
     * بيانات التكاليف الحقيقية لكل بند.
     *
     * @var array<int, array{actual_cost: float, purchase_date: string, purchase_notes: string}>
     */
    public array $itemsCostData = [];

    /**
     * مرفقات كل بند (فواتير، مستندات شراء).
     *
     * @var array<int, array<int, UploadedFile>>
     */
    #[Validate(['itemAttachments.*.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx'])]
    public array $itemAttachments = [];

    /** سبب رفض التسليم (للإدارة). */
    public string $rejectionReason = '';

    public function mount(): void
    {
        $this->sort = 'newest';
    }

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    // ─── Computed Properties ──────────────────────────────────────────────────

    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        $statuses = match ($this->tab) {
            'ready' => AidRequestStatus::approvedStatuses(),
            'in_execution' => [AidRequestStatus::InExecution->value],
            'pending_review' => [AidRequestStatus::PendingDeliveryReview->value],
            'delivered' => [AidRequestStatus::Delivered->value],
            'overdue' => [AidRequestStatus::InExecution->value],
            default => AidRequestStatus::approvedStatuses(),
        };

        $query = AidRequest::query()
            ->with(['family', 'items' => fn ($q) => $q->orderBy('sort_order')])
            ->whereIn('status', $statuses);

        // المتأخرات: طلبات قيد التنفيذ وتجاوزت تاريخ needed_by
        if ($this->tab === 'overdue') {
            $query->whereNotNull('needed_by')
                ->where('needed_by', '<', now()->toDateString());
        }

        return $query
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('request_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('family', fn ($f) => $f->where('case_name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('created_at'))
            ->when($this->sort === 'oldest', fn ($q) => $q->orderBy('created_at'))
            ->when($this->sort === 'priority', fn ($q) => $q->orderByRaw("
                CASE priority
                    WHEN 'عاجلة جداً' THEN 1
                    WHEN 'مرتفعة' THEN 2
                    WHEN 'متوسطة' THEN 3
                    WHEN 'عادية' THEN 4
                    ELSE 5
                END
            "))
            ->paginate(10);
    }

    #[Computed]
    public function activeRequest(): ?AidRequest
    {
        if (! $this->activeRequestId) {
            return null;
        }

        return AidRequest::with([
            'family',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'attachments' => fn ($q) => $q->whereNotNull('aid_request_item_id'),
        ])
            ->find($this->activeRequestId);
    }

    #[Computed]
    public function readyCount(): int
    {
        return AidRequest::whereIn('status', AidRequestStatus::approvedStatuses())->count();
    }

    #[Computed]
    public function inExecutionCount(): int
    {
        return AidRequest::where('status', AidRequestStatus::InExecution->value)->count();
    }

    #[Computed]
    public function pendingReviewCount(): int
    {
        return AidRequest::where('status', AidRequestStatus::PendingDeliveryReview->value)->count();
    }

    #[Computed]
    public function deliveredCount(): int
    {
        return AidRequest::where('status', AidRequestStatus::Delivered->value)->count();
    }

    #[Computed]
    public function overdueCount(): int
    {
        return AidRequest::where('status', AidRequestStatus::InExecution->value)
            ->whereNotNull('needed_by')
            ->where('needed_by', '<', now()->toDateString())
            ->count();
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

    /**
     * فتح نافذة رفع مستندات التسليم (للمندوب).
     */
    public function openSubmitReviewPanel(int $requestId): void
    {
        $this->activeRequestId = $requestId;
        $this->deliveryNotes = null;
        $this->itemsCostData = [];
        $this->itemAttachments = [];
        $this->rejectionReason = '';

        // تهيئة بيانات التكاليف من البنود المعتمدة
        $request = AidRequest::with(['items' => fn ($q) => $q->where('approved', true)->orderBy('sort_order')])
            ->find($requestId);

        if ($request) {
            foreach ($request->items as $item) {
                $this->itemsCostData[$item->id] = [
                    'actual_cost' => (float) ($item->actual_cost ?? $item->estimated_total ?? 0),
                    'purchase_date' => $item->purchase_date ?? now()->toDateString(),
                    'purchase_notes' => $item->purchase_notes ?? '',
                ];
            }
        }
    }

    /**
     * فتح نافذة مراجعة التسليم (للإدارة).
     */
    public function openReviewPanel(int $requestId): void
    {
        $this->activeRequestId = $requestId;
        $this->deliveryNotes = null;
        $this->itemsCostData = [];
        $this->rejectionReason = '';
    }

    /**
     * إغلاق النافذة.
     */
    public function closePanel(): void
    {
        $this->activeRequestId = null;
        $this->deliveryNotes = null;
        $this->itemsCostData = [];
        $this->itemAttachments = [];
        $this->rejectionReason = '';
    }

    /**
     * بدء تنفيذ الطلب (للمندوب).
     */
    public function startExecution(int $requestId, DeliveryService $service): void
    {
        $request = AidRequest::findOrFail($requestId);
        $service->startExecution($request);

        $this->dispatch('toast', message: 'تم بدء تنفيذ الطلب. يمكنك الآن إدخال التكاليف ورفع المستندات.', type: 'success');
        $this->resetPage();
    }

    /**
     * المندوب يرفع التكاليف الحقيقية ومستندات الشراء.
     */
    public function submitForReview(DeliveryService $service): void
    {
        if (! $this->activeRequestId) {
            return;
        }

        $itemsData = [];
        foreach ($this->itemsCostData as $itemId => $data) {
            if (empty($data['actual_cost'])) {
                $this->addError("itemsCostData.{$itemId}.actual_cost", 'يجب إدخال التكلفة الحقيقية لكل بند.');

                return;
            }
            $itemsData[] = [
                'id' => (int) $itemId,
                'actual_cost' => (float) $data['actual_cost'],
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'purchase_notes' => $data['purchase_notes'] ?? null,
            ];
        }

        $request = AidRequest::findOrFail($this->activeRequestId);
        $service->submitForDeliveryReview($request, $itemsData, $this->deliveryNotes);

        // حفظ مرفقات كل بند (فواتير، مستندات شراء)
        foreach ($this->itemAttachments as $itemId => $files) {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $size = $file->getSize();
                $storedName = $file->hashName();

                $path = $file->storeAs(
                    'aid-requests/'.$request->id.'/items/'.$itemId,
                    $storedName,
                    'local'
                );

                $request->attachments()->create([
                    'aid_request_item_id' => (int) $itemId,
                    'attachment_type_id' => 1,
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'path' => $path,
                    'disk' => 'local',
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'notes' => 'مستند شراء',
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        $this->closePanel();
        $this->dispatch('toast', message: 'تم تنفيذ الطلب وإرساله للمراجعة بنجاح.', type: 'success');
        $this->resetPage();
    }

    /**
     * حذف مرفق من قائمة مرفقات بند معين.
     */
    public function removeItemAttachment(int $itemId, int $index): void
    {
        if (isset($this->itemAttachments[$itemId][$index])) {
            unset($this->itemAttachments[$itemId][$index]);
            $this->itemAttachments[$itemId] = array_values($this->itemAttachments[$itemId]);

            if (empty($this->itemAttachments[$itemId])) {
                unset($this->itemAttachments[$itemId]);
            }
        }
    }

    /**
     * الإدارة تؤكد التسليم بعد المراجعة.
     */
    public function confirmDelivery(DeliveryService $service): void
    {
        if (! $this->activeRequestId) {
            return;
        }

        $request = AidRequest::findOrFail($this->activeRequestId);
        $service->reviewAndConfirmDelivery($request, $this->deliveryNotes);

        $this->closePanel();
        $this->dispatch('toast', message: 'تم تأكيد التسليم بنجاح.', type: 'success');
        $this->resetPage();
    }

    /**
     * الإدارة ترفض التسليم وتعيده للمندوب.
     */
    public function rejectDelivery(DeliveryService $service): void
    {
        if (! $this->activeRequestId || empty($this->rejectionReason)) {
            $this->addError('rejectionReason', 'يجب ذكر سبب الرفض.');

            return;
        }

        $request = AidRequest::findOrFail($this->activeRequestId);
        $service->rejectDelivery($request, $this->rejectionReason);

        $this->closePanel();
        $this->dispatch('toast', message: 'تم إعادة الطلب للمندوب للمراجعة.', type: 'warning');
        $this->resetPage();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function statusLabel(string $status): string
    {
        return AidRequestStatus::tryFrom($status)?->label() ?? $status;
    }

    public function statusVariant(string $status): string
    {
        return AidRequestStatus::tryFrom($status)?->variant() ?? 'neutral';
    }

    public function priorityVariant(string $priority): string
    {
        return match ($priority) {
            'عاجلة جداً' => 'danger',
            'مرتفعة' => 'warning',
            'متوسطة' => 'info',
            'عادية' => 'neutral',
            default => 'neutral',
        };
    }

    public function render(): View
    {
        return view('livewire.pages.delivery.index', [
            'requests' => $this->requests,
            'activeRequest' => $this->activeRequest,
        ]);
    }
}
