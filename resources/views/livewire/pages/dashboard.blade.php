<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use App\Models\Family;
use App\Models\AidRequest;
use App\Models\Fieldworker;
use App\Models\Donation;
use App\Models\FamilyAssessment;
use Illuminate\Support\Number;

new
#[Layout('layouts.app', ['title' => 'لوحة التحكم'])]
class extends Component
{
    public string $dateFrom;
    public string $dateTo;

    public function mount(): void
    {
        // السوبر أدمن لا يرى لوحة التحكم الخيرية إطلاقاً.
        if (auth()->user()?->isSuperAdmin()) {
            $this->redirect(route('deployments.index'));
        }

        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    #[Computed]
    public function stats(): array
    {
        $currentStart = $this->dateFrom;
        $currentEnd = $this->dateTo;

        $daysDiff = (int) round((strtotime($currentEnd) - strtotime($currentStart)) / 86400);
        $previousStart = date('Y-m-d', strtotime($currentStart . " -{$daysDiff} days"));
        $previousEnd = date('Y-m-d', strtotime($currentStart . ' -1 day'));

        // Families registered in period
        $familiesCurrent = Family::whereBetween('created_at', [$currentStart, $currentEnd . ' 23:59:59'])->count();
        $familiesPrevious = Family::whereBetween('created_at', [$previousStart, $previousEnd . ' 23:59:59'])->count();

        // Active aid requests (approved, partially_approved, in_execution, delivered, completed)
        $activeStatuses = ['approved', 'partially_approved', 'in_execution', 'delivered', 'completed'];
        $aidCurrent = AidRequest::whereIn('status', $activeStatuses)
            ->whereBetween('created_at', [$currentStart, $currentEnd . ' 23:59:59'])->count();
        $aidPrevious = AidRequest::whereIn('status', $activeStatuses)
            ->whereBetween('created_at', [$previousStart, $previousEnd . ' 23:59:59'])->count();

        // Active fieldworkers
        $fieldworkersCurrent = Fieldworker::where('status', 'active')->count();
        $fieldworkersPrevious = Fieldworker::where('status', 'active')
            ->where('created_at', '<', $currentStart)->count();

        // Donations total amount in period
        $donationsCurrent = (int) Donation::whereBetween('donated_at', [$currentStart, $currentEnd])->sum('amount');
        $donationsPrevious = (int) Donation::whereBetween('donated_at', [$previousStart, $previousEnd])->sum('amount');

        return [
            [
                'label' => 'الأسر المسجلة',
                'value' => Number::format(Family::count()),
                'change' => $this->formatChange($familiesCurrent, $familiesPrevious),
                'trend' => $familiesCurrent >= $familiesPrevious ? 'up' : 'down',
                'icon' => 'user-group',
            ],
            [
                'label' => 'المساعدات النشطة',
                'value' => Number::format(AidRequest::whereIn('status', $activeStatuses)->count()),
                'change' => $this->formatChange($aidCurrent, $aidPrevious),
                'trend' => $aidCurrent >= $aidPrevious ? 'up' : 'down',
                'icon' => 'gift',
            ],
            [
                'label' => 'المندوبون النشطون',
                'value' => Number::format($fieldworkersCurrent),
                'change' => $this->formatChange($fieldworkersCurrent, $fieldworkersPrevious),
                'trend' => $fieldworkersCurrent >= $fieldworkersPrevious ? 'up' : 'down',
                'icon' => 'user',
            ],
            [
                'label' => 'إجمالي التبرعات',
                'value' => Number::format(Donation::sum('amount')) . ' ج.م',
                'change' => $this->formatChange($donationsCurrent, $donationsPrevious),
                'trend' => $donationsCurrent >= $donationsPrevious ? 'up' : 'down',
                'icon' => 'banknotes',
            ],
        ];
    }

    #[Computed]
    public function urgentItems(): array
    {
        return AidRequest::with('family')
            ->whereIn('priority', ['عاجلة جداً', 'مرتفعة'])
            ->whereNotIn('status', ['completed', 'rejected', 'cancelled'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (AidRequest $r) => [
                'title' => $r->title,
                'family' => $r->family?->case_name ?? '—',
                'priority' => $r->priority,
                'date' => $r->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    #[Computed]
    public function recentActivity(): array
    {
        $activities = collect();

        // Latest families
        Family::latest()->limit(3)->get()->each(function (Family $f) use ($activities) {
            $activities->push([
                'action' => 'تم تسجيل أسرة جديدة',
                'detail' => $f->case_name,
                'time' => $f->created_at->diffForHumans(),
                'icon' => 'user-plus',
                'timestamp' => $f->created_at,
            ]);
        });

        // Latest aid requests
        AidRequest::with('family')->latest()->limit(3)->get()->each(function (AidRequest $r) use ($activities) {
            $activities->push([
                'action' => 'طلب مساعدة: ' . $r->title,
                'detail' => $r->family?->case_name ?? '—',
                'time' => $r->created_at->diffForHumans(),
                'icon' => 'gift',
                'timestamp' => $r->created_at,
            ]);
        });

        // Latest donations
        Donation::latest()->limit(2)->get()->each(function (Donation $d) use ($activities) {
            $activities->push([
                'action' => 'تبرع من ' . $d->donor_name,
                'detail' => Number::currency($d->amount, 'ج.م', 'ar'),
                'time' => $d->created_at->diffForHumans(),
                'icon' => 'banknotes',
                'timestamp' => $d->created_at,
            ]);
        });

        return $activities
            ->sortByDesc('timestamp')
            ->take(5)
            ->values()
            ->toArray();
    }

    private function formatChange(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $pct = round((($current - $previous) / $previous) * 100);

        return ($pct >= 0 ? '+' : '') . $pct . '%';
    }
}

?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">مرحباً بك</h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-1">نظرة عامة على أداء النظام</p>
        </div>
        <div class="flex items-center gap-3">
            <x-ui.date-input wire:model.live="dateFrom" name="date-from" label="من" />
            <x-ui.date-input wire:model.live="dateTo" name="date-to" label="إلى" />
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($this->stats as $stat)
            <x-ui.card variant="stat">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-[var(--color-text-muted)]">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-bold text-[var(--color-text-primary)] mt-1">{{ $stat['value'] }}</p>
                        <span class="inline-flex items-center gap-1 text-xs mt-2
                            {{ $stat['trend'] === 'up' ? 'text-[var(--color-success-500)]' : 'text-[var(--color-danger-500)]' }}">
                            @if($stat['trend'] === 'up')
                                <x-heroicon-o-arrow-up class="w-3 h-3 text-current" />
                            @else
                                <x-heroicon-o-arrow-down class="w-3 h-3 text-current" />
                            @endif
                            {{ $stat['change'] }}
                        </span>
                    </div>
                    <div class="w-10 h-10 rounded-[var(--radius-md)] bg-[var(--accent-50)] text-[var(--accent-600)] flex items-center justify-center">
                        <x-dynamic-component :component="'heroicon-s-' . $stat['icon']" class="w-5 h-5" />
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>

    {{-- Two column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Urgent Items --}}
        <x-ui.card class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">العناصر العاجلة</h2>
                <x-ui.badge variant="danger" size="sm" dot>{{ count($this->urgentItems) }}</x-ui.badge>
            </div>
            <div class="space-y-3">
                @forelse($this->urgentItems as $item)
                    <div class="flex items-center justify-between p-3 rounded-[var(--radius-md)] hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full {{ $item['priority'] === 'عاجلة جداً' || $item['priority'] === 'عالية' ? 'bg-[var(--color-danger-500)]' : ($item['priority'] === 'متوسطة' ? 'bg-[var(--color-warning-500)]' : 'bg-[var(--color-info-500)]') }}"></span>
                            <div>
                                <p class="text-sm font-medium text-[var(--color-text-primary)]">{{ $item['title'] }}</p>
                                <p class="text-xs text-[var(--color-text-muted)]">{{ $item['family'] }} · {{ $item['date'] }}</p>
                            </div>
                        </div>
                        <x-ui.badge :variant="$item['priority'] === 'عاجلة جداً' || $item['priority'] === 'عالية' ? 'danger' : ($item['priority'] === 'متوسطة' ? 'warning' : 'info')" size="sm">{{ $item['priority'] }}</x-ui.badge>
                    </div>
                @empty
                    <p class="text-sm text-[var(--color-text-muted)]">لا توجد عناصر عاجلة</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Recent Activity --}}
        <x-ui.card>
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">آخر النشاطات</h2>
            <div class="space-y-4">
                @forelse($this->recentActivity as $activity)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-[var(--accent-50)] text-[var(--accent-600)] flex items-center justify-center shrink-0">
                            <x-dynamic-component :component="'heroicon-s-' . $activity['icon']" class="w-5 h-5 text-current" />
                        </div>
                        <div>
                            <p class="text-sm text-[var(--color-text-primary)]">{{ $activity['action'] }}</p>
                            <p class="text-xs text-[var(--color-text-muted)]">{{ $activity['detail'] }} · {{ $activity['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-[var(--color-text-muted)]">لا توجد نشاطات حديثة</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>
</div>
