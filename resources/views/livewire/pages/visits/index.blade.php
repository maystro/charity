@php
use App\Enums\VisitStatus;
@endphp

<div class="space-y-6">
    <x-layout.page-header title="الزيارات والمتابعة" subtitle="جدولة ومتابعة الزيارات الميدانية المرتبطة بالأسر.">
        <x-slot:actions>
            <a href="{{ route('visits.create') }}" wire:navigate>
                <x-ui.button variant="primary" icon="plus">إضافة زيارة</x-ui.button>
            </a>
            <a href="{{ route('visits.calendar') }}" wire:navigate>
                <x-ui.button variant="outline" icon="calendar-days">التقويم</x-ui.button>
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-ui.stat title="زيارات اليوم" :value="$todayCount" variant="info" icon="clock" />
        <x-ui.stat title="الزيارات القادمة" :value="$upcomingCount" variant="neutral" icon="calendar-days" />
        <x-ui.stat title="الزيارات المتأخرة" :value="$overdueCount" variant="danger" icon="exclamation-triangle" />
        <x-ui.stat title="المكتملة" :value="$completedCount" variant="success" icon="check-circle" />
    </div>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-ui.input label="بحث" name="search" wire:model.live.debounce.300ms="search" placeholder="اسم الأسرة أو رقم الزيارة..." />
            <x-ui.select label="الحالة" name="status" wire:model.live="status" :options="array_merge(['' => 'كل الحالات'], $statusOptions)" />
            <x-ui.select label="نوع الزيارة" name="visitType" wire:model.live="visitType" :options="array_merge(['' => 'كل الأنواع'], $typeOptions)" />
            <div class="grid grid-cols-2 gap-2">
                <x-ui.date-input label="من تاريخ" name="dateFrom" wire:model.live="dateFrom" />
                <x-ui.date-input label="إلى تاريخ" name="dateTo" wire:model.live="dateTo" />
            </div>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-[var(--color-border)]">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">رقم الزيارة</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الأسرة</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">النوع</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الموعد</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الباحث / المندوب</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الحالة</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($visits as $visit)
                        @php
                            $visitStatus = VisitStatus::tryFrom($visit->status);
                            $visitTypeEnum = \App\Enums\VisitType::tryFrom($visit->visit_type);
                        @endphp
                        <tr wire:key="visit-{{ $visit->id }}">
                            <td class="px-4 py-3 font-medium">
                                <span class="text-[var(--accent-600)]">{{ $visit->visit_number ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $visit->family?->case_name }}</div>
                                <div class="text-xs text-[var(--color-text-muted)]">{{ $visit->family?->case_number }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $visitTypeEnum?->variant() ?? 'neutral' }}">
                                    {{ $visitTypeEnum?->label() ?? $visit->visit_type }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3">{{ $visit->scheduled_at?->format('Y/m/d H:i') ?? 'غير محدد' }}</td>
                            <td class="px-4 py-3">
                                @if($visit->researcher)
                                    <div class="text-xs">{{ $visit->researcher->name }}</div>
                                @endif
                                @if($visit->representative)
                                    <div class="text-xs text-[var(--color-text-muted)]">{{ $visit->representative->name }}</div>
                                @endif
                                @if(!$visit->researcher && !$visit->representative)
                                    <span class="text-[var(--color-text-muted)]">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $visitStatus?->variant() ?? 'neutral' }}">
                                    {{ $visitStatus?->label() ?? $visit->status }}
                                </x-ui.badge>
                                @if($visit->is_overdue)
                                    <span class="me-1 text-xs text-[var(--color-danger-500)]">متأخرة!</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1.5">
                                    <a href="{{ route('visits.show', $visit) }}" wire:navigate>
                                        <x-ui.button variant="ghost" size="sm" icon="eye" title="عرض" />
                                    </a>
                                    @if(in_array($visit->status, VisitStatus::pendingStatuses()))
                                        <a href="{{ route('visits.edit', $visit) }}" wire:navigate>
                                            <x-ui.button variant="ghost" size="sm" icon="pencil" title="تعديل" />
                                        </a>
                                        <a href="{{ route('visits.execute', $visit) }}" wire:navigate>
                                            <x-ui.button variant="ghost" size="sm" icon="play" title="تنفيذ" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-[var(--color-text-muted)]">
                                <x-ui.empty-state title="لا توجد زيارات" description="لم يتم تسجيل أي زيارات بعد.">
                                    <a href="{{ route('visits.create') }}" wire:navigate>
                                        <x-ui.button variant="primary" icon="plus">إضافة أول زيارة</x-ui.button>
                                    </a>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $visits->links() }}</div>
    </x-ui.card>
</div>
