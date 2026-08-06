<?php

namespace App\Livewire\Donations;

use App\Enums\DonationMethod;
use App\Enums\DonationType;
use App\Models\Donation;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'التبرعات'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url(as: 'method', except: '')]
    public string $methodFilter = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    public string $sort = 'newest';

    public function delete(int $id): void
    {
        $donation = Donation::findOrFail($id);
        $donation->delete();

        $this->dispatch('notify', message: 'تم حذف التبرع بنجاح', type: 'success');
    }

    #[Computed]
    public function methodOptions(): array
    {
        return DonationMethod::options();
    }

    #[Computed]
    public function typeOptions(): array
    {
        return DonationType::options();
    }

    public function render(): View
    {
        $donations = Donation::query()
            ->with(['donor', 'project'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('donor_name', 'like', '%'.$this->search.'%')
                        ->orWhere('notes', 'like', '%'.$this->search.'%')
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->methodFilter, fn ($q) => $q->where('method', $this->methodFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('donated_at'))
            ->when($this->sort === 'oldest', fn ($q) => $q->orderBy('donated_at'))
            ->when($this->sort === 'amount_desc', fn ($q) => $q->orderByDesc('amount'))
            ->when($this->sort === 'amount_asc', fn ($q) => $q->orderBy('amount'))
            ->paginate(10);

        return view('livewire.pages.donations.index', [
            'donations' => $donations,
            'methodOptions' => $this->methodOptions,
            'typeOptions' => $this->typeOptions,
            'totalAmount' => (float) Donation::when($this->methodFilter, fn ($q) => $q->where('method', $this->methodFilter))->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))->sum('amount'),
        ]);
    }
}
