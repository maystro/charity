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
            'assessments.members',
            'assessments.incomeSources',
            'assessments.resources',
            'assessments.burdens',
            'assessments.housing',
            'assessments.aids',
            'assessments.creator',
            'assessments.approver',
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
