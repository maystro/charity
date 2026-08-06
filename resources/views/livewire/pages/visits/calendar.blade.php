@php
$today = now()->format('Y-m-d');
$currentMonthDate = \Carbon\Carbon::parse($currentMonth.'-01');
@endphp

<div class="space-y-6">
    <x-layout.page-header title="تقويم الزيارات" subtitle="{{ $monthLabel }}">
        <x-slot:actions>
            <a href="{{ route('visits.index') }}" wire:navigate>
                <x-ui.button variant="ghost" icon="arrow-right">العودة للقائمة</x-ui.button>
            </a>
            <a href="{{ route('visits.create') }}" wire:navigate>
                <x-ui.button variant="primary" icon="plus">إضافة زيارة</x-ui.button>
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        {{-- Month Navigation --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <x-ui.button variant="ghost" size="sm" icon="chevron-right" wire:click="prevMonth" />
                <span class="text-lg font-bold">{{ $monthLabel }}</span>
                <x-ui.button variant="ghost" size="sm" icon="chevron-left" wire:click="nextMonth" />
            </div>
            <x-ui.button variant="outline" size="sm" wire:click="goToToday">اليوم</x-ui.button>
        </div>

        {{-- Day names header --}}
        <div class="grid grid-cols-7 gap-1 mb-1">
            @foreach(['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'] as $dayName)
                <div class="px-2 py-1 text-center text-xs font-bold text-[var(--color-text-muted)]">{{ $dayName }}</div>
            @endforeach
        </div>

        {{-- Calendar grid --}}
        <div class="grid grid-cols-7 gap-1">
            @foreach($days as $day)
                @php
                    $dateKey = $day->format('Y-m-d');
                    $isToday = $dateKey === $today;
                    $isCurrentMonth = $day->month === $currentMonthDate->month;
                    $dayVisits = $visitsByDate[$dateKey] ?? collect();
                @endphp
                <div class="min-h-[90px] rounded-lg border p-1.5 {{ $isCurrentMonth ? '' : 'opacity-40' }} {{ $isToday ? 'border-[var(--accent-500)] bg-[var(--accent-50)]' : 'border-[var(--color-border)]' }}">
                    <div class="text-xs mb-1 {{ $isToday ? 'font-bold text-[var(--accent-600)]' : 'text-[var(--color-text-muted)]' }}">
                        {{ $day->day }}
                    </div>
                    <div class="space-y-0.5">
                        @foreach($dayVisits->take(3) as $visit)
                            @php
                                $visitStatus = App\Enums\VisitStatus::tryFrom($visit->status);
                                $bgColor = match($visitStatus?->variant() ?? 'neutral') {
                                    'danger' => 'var(--color-danger-100)',
                                    'warning' => 'var(--color-warning-100)',
                                    'success' => 'var(--color-success-100)',
                                    'info' => 'var(--color-info-100)',
                                    default => 'var(--color-bg-muted)',
                                };
                                $textColor = match($visitStatus?->variant() ?? 'neutral') {
                                    'danger' => 'var(--color-danger-700)',
                                    'warning' => 'var(--color-warning-700)',
                                    'success' => 'var(--color-success-700)',
                                    'info' => 'var(--color-info-700)',
                                    default => 'var(--color-text-secondary)',
                                };
                            @endphp
                            <a href="{{ route('visits.show', $visit) }}" wire:navigate class="block rounded px-1 py-0.5 text-[10px] leading-tight truncate" style="background: {{ $bgColor }}; color: {{ $textColor }};" title="{{ $visit->family?->case_name }}">
                                {{ $visit->visit_number }} - {{ Str::limit($visit->family?->case_name ?? '—', 15) }}
                            </a>
                        @endforeach
                        @if($dayVisits->count() > 3)
                            <div class="text-[10px] text-[var(--color-text-muted)] text-center">+{{ $dayVisits->count() - 3 }} أخرى</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Legend --}}
    <x-ui.card>
        <div class="flex flex-wrap gap-4 text-xs">
            <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background: var(--color-info-100);"></span> مجدولة/مُسندة</span>
            <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background: var(--color-warning-100);"></span> قيد التنفيذ</span>
            <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background: var(--color-success-100);"></span> مكتملة</span>
            <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background: var(--color-danger-100);"></span> متأخرة/ملغاة</span>
        </div>
    </x-ui.card>
</div>
