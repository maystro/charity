<?php

namespace App\Livewire\Donors;

use App\Models\Donor;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تفاصيل المتبرع'])]
class Show extends Component
{
    public ?int $donorId = null;

    public function mount(Donor $donor): void
    {
        $this->donorId = $donor->id;
    }

    public function render(): View
    {
        $donor = Donor::with(['donations' => fn ($q) => $q->with('project')->orderByDesc('donated_at')])
            ->findOrFail($this->donorId);

        $total = (float) $donor->donations->sum(fn ($d) => (float) $d->amount);

        return view('livewire.pages.donors.show', [
            'donor' => $donor,
            'totalDonations' => $total,
            'countDonations' => $donor->donations->count(),
        ]);
    }
}
