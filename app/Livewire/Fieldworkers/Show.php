<?php

namespace App\Livewire\Fieldworkers;

use App\Enums\FamilyStatus;
use App\Models\Fieldworker;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تفاصيل المندوب'])]
class Show extends Component
{
    public Fieldworker $fieldworker;

    public function mount(Fieldworker $fieldworker): void
    {
        $this->fieldworker = $fieldworker;
    }

    public function render(): View
    {
        $families = $this->fieldworker->families()
            ->with('fieldworker')
            ->orderByDesc('created_at')
            ->paginate(10, pageName: 'familiesPage');

        $stats = [
            'total' => $this->fieldworker->families()->count(),
            'approved' => $this->fieldworker->families()->where('status', FamilyStatus::Approved->value)->count(),
            'underReview' => $this->fieldworker->families()->where('status', FamilyStatus::UnderReview->value)->count(),
            'drafts' => $this->fieldworker->families()->where('status', FamilyStatus::Draft->value)->count(),
        ];

        return view('livewire.pages.fieldworkers.show', [
            'families' => $families,
            'stats' => $stats,
        ]);
    }
}
