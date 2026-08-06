<?php

namespace App\Livewire\Families;

use App\Enums\FamilyStatus;
use App\Models\Family;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'الأسر والحالات'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public string $search = '';

    public string $community = '';

    public string $caseType = '';

    public string $sort = 'newest';

    public function delete(int $id): void
    {
        $family = Family::findOrFail($id);
        $this->authorize('delete', $family);
        $family->delete();

        $this->dispatch('notify', message: 'تم حذف الأسرة بنجاح', type: 'success');
    }

    /**
     * The status currently being filtered (defaults to Approved).
     */
    protected function activeStatus(): FamilyStatus
    {
        return FamilyStatus::tryFrom($this->statusFilter) ?? FamilyStatus::Approved;
    }

    /**
     * Whether the current view is showing under-review cases.
     */
    public function getIsReviewProperty(): bool
    {
        return $this->activeStatus() === FamilyStatus::UnderReview;
    }

    #[Computed]
    public function communities(): array
    {
        return $this->scopedFamilyQuery()
            ->where('status', $this->activeStatus()->value)
            ->whereNotNull('community')
            ->distinct()
            ->pluck('community', 'community')
            ->toArray();
    }

    #[Computed]
    public function caseTypes(): array
    {
        return $this->scopedFamilyQuery()
            ->where('status', $this->activeStatus()->value)
            ->distinct()
            ->pluck('case_type', 'case_type')
            ->toArray();
    }

    /**
     * يُستدعى من خلال فئات مُنحِدة لتحديد سجللات الأسر التي يراها المستخدم.
     * القاعدة ترجع كل السجللات (للمدير). المندوب يرى فقط أسره.
     */
    protected function scopedFamilyQuery()
    {
        $user = auth()->user();
        if (! $user || $user->isAdmin()) {
            return Family::query();
        }

        // المندوب: يرى الأسر التي قام بإرسالها أو المرتبطة ببطاقته الميداني.
        return Family::query()->where(function ($q) use ($user) {
            $q->where('submitted_by', $user->id)
                ->orWhere('fieldworker_id', $user->fieldworker?->id);
        });
    }

    public function render(): View
    {
        $status = $this->activeStatus();

        $families = $this->scopedFamilyQuery()
            ->where('status', $status->value)
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('case_name', 'like', '%'.$this->search.'%')
                        ->orWhere('case_number', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('community', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->community, fn ($q) => $q->where('community', $this->community))
            ->when($this->caseType, fn ($q) => $q->where('case_type', $this->caseType))
            ->when($status === FamilyStatus::Approved && $this->sort === 'newest', fn ($q) => $q->orderByDesc('approved_at'))
            ->when($status === FamilyStatus::Approved && $this->sort === 'oldest', fn ($q) => $q->orderBy('approved_at'))
            ->when($status === FamilyStatus::UnderReview, fn ($q) => $q->orderByDesc('submitted_at'))
            ->when($status === FamilyStatus::Draft, fn ($q) => $q->orderByDesc('created_at'))
            ->paginate(10);

        return view('livewire.pages.families.index', [
            'families' => $families,
            'communities' => $this->communities,
            'caseTypes' => $this->caseTypes,
            'status' => $status,
        ]);
    }
}
