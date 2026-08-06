<?php

namespace App\Livewire\Alerts;

use App\Models\Alert;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'التنبيهات'])]
class AlertsIndex extends Component
{
    use WithPagination;

    public string $filter = 'active';

    public function dismissAlert(int $alertId): void
    {
        $alert = Alert::findOrFail($alertId);

        if (! $alert->isActive()) {
            return;
        }

        $alert->dismiss();

        $this->dispatch('notify', message: 'تم تجاهل التنبيه.', type: 'success');
        $this->resetPage();
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = Alert::findOrFail($alertId);

        if (! $alert->isActive()) {
            return;
        }

        $alert->resolve();

        $this->dispatch('notify', message: 'تم حل التنبيه.', type: 'success');
        $this->resetPage();
    }

    #[Computed]
    public function alerts()
    {
        return Alert::query()
            ->when($this->filter === 'active', fn ($q) => $q->active())
            ->when($this->filter === 'dismissed', fn ($q) => $q->dismissed())
            ->when($this->filter === 'resolved', fn ($q) => $q->resolved())
            ->when($this->filter === 'overdue', fn ($q) => $q->active()->overdue())
            ->with('alertable')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function counts()
    {
        return [
            'active' => Alert::active()->count(),
            'overdue' => Alert::active()->overdue()->count(),
            'dismissed' => Alert::dismissed()->count(),
            'resolved' => Alert::resolved()->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.alerts.index', [
            'alerts' => $this->alerts,
            'counts' => $this->counts,
        ]);
    }
}
