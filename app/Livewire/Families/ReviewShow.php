<?php

namespace App\Livewire\Families;

use App\Models\Family;
use App\Services\Families\FamilyApprovalService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'مراجعة الحالة'])]
class ReviewShow extends Component
{
    public Family $family;

    public string $reviewNotes = '';

    public string $rejectionReason = '';

    public string $returnReason = '';

    public bool $showApproveModal = false;

    public bool $showRejectModal = false;

    public bool $showReturnModal = false;

    public function mount(Family $family): void
    {
        $this->family = $family->load(['members', 'incomeSources', 'resources', 'burdens', 'housing', 'aids', 'statusHistories.changer', 'creator', 'submitter']);
    }

    public function approve(): void
    {
        $this->validate(['reviewNotes' => 'nullable|string']);

        app(FamilyApprovalService::class)->approve($this->family, $this->reviewNotes ?: null);

        $this->dispatch('notify', message: 'تم اعتماد الأسرة وإضافتها إلى قائمة الأسر والحالات بنجاح.', type: 'success');
        $this->redirect(route('families.index', ['status' => 'under_review']), navigate: true);
    }

    public function returnForCompletion(): void
    {
        $this->validate(['returnReason' => 'required|string']);

        app(FamilyApprovalService::class)->returnForCompletion($this->family, $this->returnReason);

        $this->dispatch('notify', message: 'تم إعادة الحالة للاستكمال.', type: 'info');
        $this->redirect(route('families.index', ['status' => 'under_review']), navigate: true);
    }

    public function reject(): void
    {
        $this->validate(['rejectionReason' => 'required|string']);

        app(FamilyApprovalService::class)->reject($this->family, $this->rejectionReason);

        $this->dispatch('notify', message: 'تم رفض الحالة.', type: 'warning');
        $this->redirect(route('families.index', ['status' => 'under_review']), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.families.review-show', [
            'family' => $this->family,
        ]);
    }
}
