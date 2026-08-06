<?php

namespace App\Livewire\AidRequests;

use App\Enums\AidRequestStatus;
use App\Models\AidRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * القاعدة المشتركة لعرض تبويبات طلبات المساعدة (تحت المراجعة / معتمدة).
 *
 * يثبت الصنف المنحدر أساليب ال_scope الخاصة به عبر override
 * {@see self::scopedQuery()} لإضافة قيود حسب المستخدم/الدور.
 * القالب المشترك الوحيد livewire.pages.aid-requests.index يخدم الكلاسين.
 */
#[Layout('layouts.app', ['title' => 'طلبات المساعدة'])]
abstract class BaseAidRequestsIndex extends Component
{
    use WithPagination;

    /** التبويب النشط: تحت المراجعة / معتمدة. */
    public string $tab = 'under_review';

    public string $search = '';

    public string $priority = '';

    public string $sort = 'newest';

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

    public function updatingPriority(): void
    {
        $this->resetPage();
    }

    /**
     * القيد التطبيقي (override): يُضاف إلى الاستعلام الأساسي
     * لتحديد سجللات هذا المستخدم فقط (للمندوب) أو أي限制 آخر.
     */
    protected function scopedQuery(Builder $query): Builder
    {
        return $query;
    }

    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        $statuses = $this->tab === 'approved'
            ? AidRequestStatus::approvedStatuses()
            : AidRequestStatus::underReviewStatuses();

        $query = AidRequest::query()
            ->with('family')
            ->whereIn('status', $statuses);

        $query = $this->scopedQuery($query);

        return $query
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('request_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('family', fn ($f) => $f->where('case_name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->priority, fn ($q) => $q->where('priority', $this->priority))
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
    public function underReviewCount(): int
    {
        return $this->scopedQuery(
            AidRequest::whereIn('status', AidRequestStatus::underReviewStatuses())
        )->count();
    }

    #[Computed]
    public function approvedCount(): int
    {
        return $this->scopedQuery(
            AidRequest::whereIn('status', AidRequestStatus::approvedStatuses())
        )->count();
    }

    public function delete(int $id): void
    {
        $query = $this->scopedQuery(AidRequest::whereKey($id));
        $request = $query->firstOrFail();
        $this->authorize('delete', $request);
        $request->delete();

        $this->dispatch('notify', message: 'تم حذف الطلب بنجاح', type: 'success');
    }

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

    /**
     * زر «إضافة طلب»: يُتجاوز للمندوب ليؤدي إلى نفس صفحة الإنشاء.
     */
    public function createRouteName(): string
    {
        return 'aid-requests.create';
    }

    public function render(): View
    {
        return view('livewire.pages.aid-requests.index', [
            'requests' => $this->requests,
            'showDelete' => $this->showDelete(),
            'showCreate' => $this->showCreate(),
            'title' => $this->pageTitle(),
        ]);
    }

    /**
     * عنوان الصفحة في واجهة المستخدم (يُجاوز في الصنف المنحدر).
     */
    protected function pageTitle(): string
    {
        return 'طلبات المساعدة';
    }

    /**
     * هل يُعرض زر الحذف لهذا المنظر؟ (يراه المشرف فقط).
     */
    protected function showDelete(): bool
    {
        return true;
    }

    /**
     * هل يُعرض زر «إضافة طلب» لهذا المنظر؟
     * المشرف فقط هو من يستطيع إنشاء طلبات مساعدة جديدة؛
     * المندوب يُنشئ الطلبات من خلال مسار آخر بصلاحيات مخصصة.
     */
    protected function showCreate(): bool
    {
        return true;
    }
}
