<?php

namespace App\Livewire\Donors;

use App\Models\Donation;
use App\Models\Donor;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'تفاصيل المتبرع'])]
class Show extends Component
{
    use WithPagination;

    public ?int $donorId = null;

    public function mount(Donor $donor): void
    {
        $this->donorId = $donor->id;
    }

    public function render(): View
    {
        $donor = Donor::findOrFail($this->donorId);

        return view('livewire.pages.donors.show', [
            'donor' => $donor,
            'donations' => Donation::query()
                ->where('donor_id', $donor->id)
                ->with('project')
                ->orderByDesc('donated_at')
                ->paginate(15),
            'totalDonations' => (float) Donation::query()->where('donor_id', $donor->id)->sum('amount'),
            'countDonations' => Donation::query()->where('donor_id', $donor->id)->count(),
        ]);
    }
}
