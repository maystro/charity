<?php

namespace App\Livewire\Families;

use App\Enums\FamilyStatus;
use App\Models\Family;
use App\Services\Families\ReAssessmentService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'إعادة التقييم'])]
class ReAssessmentIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function startReAssessment(int $familyId): void
    {
        $family = Family::findOrFail($familyId);

        if (! $family->currentAssessment) {
            $this->dispatch('notify', message: 'لا يوجد تقييم حالي لهذه الأسرة.', type: 'error');

            return;
        }

        $assessment = app(ReAssessmentService::class)->startReAssessment($family);

        $this->dispatch('notify', message: "تم إنشاء التقييم رقم {$assessment->round} بنجاح.", type: 'success');
        $this->redirect(route('families.edit', $family), navigate: true);
    }

    #[Computed]
    public function families()
    {
        return Family::query()
            ->whereHas('currentAssessment', fn ($q) => $q->where('status', FamilyStatus::Approved->value))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('case_name', 'like', '%'.$this->search.'%')
                        ->orWhere('case_number', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->withCount('assessments')
            ->with('currentAssessment')
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    public function render(): View
    {
        return view('livewire.pages.families.re-assessment-index', [
            'families' => $this->families,
        ]);
    }
}
