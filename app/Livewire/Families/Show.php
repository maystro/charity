<?php

namespace App\Livewire\Families;

use App\Models\Family;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تفاصيل الأسرة'])]
class Show extends Component
{
    public Family $family;

    public function mount(Family $family): void
    {
        $this->family = $family->load(['members', 'incomeSources', 'resources', 'burdens', 'housing', 'statusHistories.changer', 'creator', 'approver']);
    }

    public function render(): View
    {
        return view('livewire.pages.families.show', [
            'family' => $this->family,
        ]);
    }
}
