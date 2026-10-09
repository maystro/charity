<?php

namespace App\Livewire\Families;

use App\Models\Family;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تاريخ التقييمات'])]
class AssessmentHistory extends Component
{
    public Family $family;

    public function mount(Family $family): void
    {
        $this->family = $family->load([
            'assessments' => fn ($query) => $query->orderByDesc('round'),
            'assessments.creator:id,name',
            'assessments.approver:id,name',
        ]);
    }

    public function render(): View
    {
        return view('livewire.pages.families.assessment-history', [
            'family' => $this->family,
            'assessments' => $this->family->assessments,
        ]);
    }
}
