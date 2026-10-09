<?php

namespace App\Livewire\Fieldworkers;

use App\Enums\FamilyStatus;
use App\Models\Family;
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
            ->orderByDesc('created_at')
            ->paginate(10, pageName: 'familiesPage');

        $byStatus = Family::query()
            ->selectRaw('status, count(*) as aggregate')
            ->where('fieldworker_id', $this->fieldworker->id)
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = [
            'total' => (int) $byStatus->sum(),
            'approved' => (int) ($byStatus[FamilyStatus::Approved->value] ?? 0),
            'underReview' => (int) ($byStatus[FamilyStatus::UnderReview->value] ?? 0),
            'drafts' => (int) ($byStatus[FamilyStatus::Draft->value] ?? 0),
        ];

        return view('livewire.pages.fieldworkers.show', [
            'families' => $families,
            'stats' => $stats,
        ]);
    }
}
