<?php

namespace App\Livewire\Visits;

use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Visit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تفاصيل الزيارة'])]
class Show extends Component
{
    public Visit $visit;

    public function mount(Visit $visit): void
    {
        $this->visit = $visit->load([
            'family', 'research', 'aidRequest',
            'researcher', 'representative', 'creator', 'completer',
            'statusHistories.changer',
        ]);
    }

    #[Computed]
    public function visitStatus(): ?VisitStatus
    {
        return VisitStatus::tryFrom($this->visit->status);
    }

    #[Computed]
    public function visitTypeEnum(): ?VisitType
    {
        return VisitType::tryFrom($this->visit->visit_type);
    }

    #[Computed]
    public function canEdit(): bool
    {
        return in_array($this->visit->status, VisitStatus::pendingStatuses(), true);
    }

    public function render()
    {
        return view('livewire.pages.visits.show');
    }
}
