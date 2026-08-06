{{--
    PendingApprovalsStat — top bar tile showing families/cases awaiting approval.

    Displays: icon + number + label ("أسر وحالات جديدة").
    On click: opens a dropdown with the top 5 pending families and a
    "View all" link to the families index filtered by under_review.
--}}
@php
    $count = $this->pendingCount;
    $variant = $count > 0 ? 'warning' : 'success';
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
            {{ $variant === 'warning' ? 'bg-[var(--color-warning-50)] hover:bg-[var(--color-warning-100)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-50)] hover:bg-[var(--color-success-100)]' : '' }}
        "
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="أسر وحالات جديدة {{ $count }}"
    >
        {{-- Icon --}}
        <span class="relative shrink-0 w-9 h-9 rounded-full flex items-center justify-center shadow-sm
            {{ $variant === 'warning' ? 'bg-[var(--color-warning-500)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-500)]' : '' }}
        ">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>

            @if($count > 0)
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
            @endif
        </span>

        {{-- Number + label --}}
        <span class="flex flex-col leading-tight min-w-0 text-right">
            <span class="text-lg font-bold tabular-nums
                {{ $variant === 'warning' ? 'text-[var(--color-warning-700)]' : '' }}
                {{ $variant === 'success' ? 'text-[var(--color-success-700)]' : '' }}
            ">{{ $count }}</span>
            <span class="text-[11px] font-medium text-[var(--color-text-secondary)] truncate">أسر وحالات جديدة</span>
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
                    حالات بانتظار الاعتماد
                </h3>
                <span class="text-xs text-[var(--color-text-muted)]">
                    {{ $count }} حالة
                </span>
            </div>
        </div>

        {{-- List --}}
        @if($this->topPendingFamilies->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-[var(--color-text-muted)]">
                <svg class="w-10 h-10 mx-auto mb-2 text-[var(--color-success-500)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                لا توجد حالات بانتظار الاعتماد
            </div>
        @else
            <ul class="max-h-80 overflow-y-auto divide-y divide-[var(--color-border)]">
                @foreach($this->topPendingFamilies as $family)
                    <li>
                        <a
                            href="{{ route('families.index', ['status' => 'under_review']) }}"
                            wire:navigate
                            class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-bg-secondary)] transition-colors"
                            role="menuitem"
                        >
                            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                {{ $family->status === 'under_review' ? 'bg-[var(--color-warning-50)] text-[var(--color-warning-500)]' : 'bg-[var(--color-info-50)] text-[var(--color-info-500)]' }}
                            ">
                                @if($family->status === 'under_review')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                                    </svg>
                                @endif
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-[var(--color-text-primary)] truncate">
                                    {{ $family->case_name ?? __('ui.family_no') . ' ' . $family->case_number }}
                                </div>
                                <div class="text-xs text-[var(--color-text-muted)]">
                                    {{ $family->case_number }} — {{ $family->status === 'under_review' ? 'تحت المراجعة' : 'تحتاج استكمال' }}
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
                href="{{ route('families.index', ['status' => 'under_review']) }}"
                wire:navigate
                class="block text-center text-sm font-medium text-[var(--accent-600)] hover:text-[var(--accent-700)] py-1"
            >
                عرض كل الحالات ←
            </a>
        </div>
    </div>
</div>
