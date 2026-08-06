<?php

namespace App\Livewire\Donors;

use App\Enums\DonorType;
use App\Models\Donor;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'المتبرعون'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    public string $sort = 'most_donations';

    public function delete(int $id): void
    {
        $donor = Donor::findOrFail($id);
        $donor->delete();

        $this->dispatch('notify', message: 'تم حذف المتبرع بنجاح', type: 'success');
    }

    #[Computed]
    public function typeOptions(): array
    {
        return DonorType::options();
    }

    public function render(): View
    {
        $donors = Donor::query()
            ->withCount('donations')
            ->withSum('donations', 'amount')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('city', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->sort === 'most_donations', fn ($q) => $q->orderByDesc('donations_sum_amount'))
            ->when($this->sort === 'fewest_donations', fn ($q) => $q->orderBy('donations_sum_amount'))
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('created_at'))
            ->when($this->sort === 'oldest', fn ($q) => $q->orderBy('created_at'))
            ->when($this->sort === 'name', fn ($q) => $q->orderBy('name'))
            ->paginate(10);

        return view('livewire.pages.donors.index', [
            'donors' => $donors,
            'typeOptions' => $this->typeOptions,
        ]);
    }
}
