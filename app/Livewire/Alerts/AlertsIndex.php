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
    public function counts(): array
    {
        $byStatus = Alert::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $overdue = Alert::query()
            ->active()
            ->overdue()
            ->count();

        return [
            'active' => (int) ($byStatus[Alert::STATUS_ACTIVE] ?? 0),
            'overdue' => $overdue,
            'dismissed' => (int) ($byStatus[Alert::STATUS_DISMISSED] ?? 0),
            'resolved' => (int) ($byStatus[Alert::STATUS_RESOLVED] ?? 0),
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
