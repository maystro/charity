{{--
    ReAssessmentAlertsStat — top bar tile showing due/overdue re-assessments.

    Displays: icon + number + label.
    On click: opens a dropdown with the top 5 due/overdue families and a
    "View all" link to the alerts index.
--}}
@php
    $count = $this->dueCount;
    $overdue = $this->overdueCount;
    $variant = $overdue > 0 ? 'danger' : ($count > 0 ? 'warning' : 'success');
    $icon    = $overdue > 0 ? 'exclamation-triangle' : 'arrow-path';
    $label   = $overdue > 0
        ? __('ui.reassessment_overdue')
        : ($count > 0 ? __('ui.reassessment_due') : __('ui.no_alerts'));
@endphp

<div
    wire:poll.30s.visible
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    {{-- Trigger: the stat tile (click toggles the dropdown only) --}}
    <button
        type="button"
        @click="open = !open"
        class="group inline-flex items-center gap-3 px-3 py-2 rounded-[var(--radius-lg)] transition-colors cursor-pointer
            {{ $variant === 'danger'  ? 'bg-[var(--color-danger-50)]  hover:bg-[var(--color-danger-100)]'  : '' }}
            {{ $variant === 'warning' ? 'bg-[var(--color-warning-50)] hover:bg-[var(--color-warning-100)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-50)] hover:bg-[var(--color-success-100)]' : '' }}
        "
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ $label }}"
    >
        {{-- Icon --}}
        <span class="relative shrink-0 w-9 h-9 rounded-full flex items-center justify-center shadow-sm
            {{ $variant === 'danger'  ? 'bg-[var(--color-danger-500)]'  : '' }}
            {{ $variant === 'warning' ? 'bg-[var(--color-warning-500)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-500)]' : '' }}
        ">
            @if($icon === 'exclamation-triangle')
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            @else
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
            @endif

            @if($count > 0)
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
            @endif
        </span>

        {{-- Number + label --}}
        <span class="flex flex-col leading-tight min-w-0 text-right">
            <span class="text-lg font-bold tabular-nums
                {{ $variant === 'danger'  ? 'text-[var(--color-danger-700)]'  : '' }}
                {{ $variant === 'warning' ? 'text-[var(--color-warning-700)]' : '' }}
                {{ $variant === 'success' ? 'text-[var(--color-success-700)]' : '' }}
            ">{{ $count }}</span>
            <span class="text-[11px] font-medium text-[var(--color-text-secondary)] truncate">{{ $label }}</span>
        </span>
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
        class="absolute z-[var(--z-dropdown)] mt-2 left-0 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-[var(--radius-lg)] shadow-xl border border-[var(--color-border)] overflow-hidden"
        role="menu"
    >
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--color-text-primary)]">
                    {{ __('ui.reassessment_periodic') }}
                </h3>
                <span class="text-xs text-[var(--color-text-muted)]">
                    {{ $this->activeAlertsCount }} {{ __('ui.active_alert') }}
                </span>
            </div>
        </div>

        {{-- List --}}
        @if($this->topDueFamilies->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-[var(--color-text-muted)]">
                <svg class="w-10 h-10 mx-auto mb-2 text-[var(--color-success-500)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('ui.no_due_families') }}
            </div>
        @else
            <ul class="max-h-80 overflow-y-auto divide-y divide-[var(--color-border)]">
                @foreach($this->topDueFamilies as $family)
                    @php
                        $approvedAt = $family->currentAssessment?->approved_at;
                        $dueAt = $approvedAt?->copy()->addMonths((int) \App\Models\SystemSetting::get('reassessment_interval_months', 3));
                        $isOverdue = $dueAt?->isPast();
                    @endphp
                    <li>
                        <a
                            href="{{ route('families.re-assessment-index') }}"
                            wire:navigate
                            class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-bg-secondary)] transition-colors"
                            role="menuitem"
                        >
                            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                {{ $isOverdue ? 'bg-[var(--color-danger-50)] text-[var(--color-danger-500)]' : 'bg-[var(--color-warning-50)] text-[var(--color-warning-500)]' }}
                            ">
                                @if($isOverdue)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-[var(--color-text-primary)] truncate">
                                    {{ $family->case_name ?? __('ui.family_no') . ' ' . $family->case_number }}
                                </div>
                                <div class="text-xs text-[var(--color-text-muted)]">
                                    @if($isOverdue)
                                        {{ __('ui.overdue_since') }} {{ (int) $dueAt->diffInDays(now()) }} {{ __('ui.overdue_days') }}
                                    @else
                                        {{ __('ui.due_on') }} {{ $dueAt?->format('Y-m-d') }}
                                    @endif
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-[var(--color-text-muted)] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                            </svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Footer --}}
        <div class="px-4 py-2 border-t border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
            <a
                href="{{ route('families.re-assessment-index') }}"
                wire:navigate
                class="block text-center text-sm font-medium text-[var(--accent-600)] hover:text-[var(--accent-700)] py-1"
            >
                {{ __('ui.view_all_alerts') }} ←
            </a>
        </div>
    </div>
</div>
