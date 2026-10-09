@php
    $count = $this->requestsCount;
    $overdue = $this->overdueCount;
    $variant = $overdue > 0 ? 'danger' : ($count > 0 ? 'warning' : 'success');
@endphp

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <button
        type="button"
        @click="open = !open"
        class="group inline-flex items-center gap-3 px-3 py-2 rounded-[var(--radius-lg)] transition-colors cursor-pointer
            {{ $variant === 'danger' ? 'bg-[var(--color-danger-50)] hover:bg-[var(--color-danger-100)]' : '' }}
            {{ $variant === 'warning' ? 'bg-[var(--color-warning-50)] hover:bg-[var(--color-warning-100)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-50)] hover:bg-[var(--color-success-100)]' : '' }}
        "
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ $count }} طلبات مستحقة التنفيذ"
    >
        <span class="relative shrink-0 w-9 h-9 rounded-full flex items-center justify-center shadow-sm
            {{ $variant === 'danger' ? 'bg-[var(--color-danger-500)]' : '' }}
            {{ $variant === 'warning' ? 'bg-[var(--color-warning-500)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-500)]' : '' }}
        ">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            @if($count > 0)
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
            @endif
        </span>

        <span class="flex flex-col leading-tight min-w-0 text-right">
            <span class="text-lg font-bold tabular-nums
                {{ $variant === 'danger' ? 'text-[var(--color-danger-700)]' : '' }}
                {{ $variant === 'warning' ? 'text-[var(--color-warning-700)]' : '' }}
                {{ $variant === 'success' ? 'text-[var(--color-success-700)]' : '' }}
            ">{{ $count }}</span>
            <span class="text-[11px] font-medium text-[var(--color-text-secondary)] truncate">مواعيد تنفيذ قريبة</span>
        </span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute z-[var(--z-dropdown)] mt-2 left-0 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-[var(--radius-lg)] shadow-xl border border-[var(--color-border)] overflow-hidden"
        role="menu"
    >
        <div class="px-4 py-3 border-b border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-[var(--color-text-primary)]">مواعيد التنفيذ</h3>
                <span class="text-xs text-[var(--color-text-muted)]">{{ $count }} طلب</span>
            </div>
        </div>

        @if($this->topRequests->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-[var(--color-text-muted)]">
                لا توجد مواعيد تنفيذ قريبة
            </div>
        @else
            <ul class="max-h-80 overflow-y-auto divide-y divide-[var(--color-border)]">
                @foreach($this->topRequests as $row)
                    @php
                        $request = $row['request'];
                        $dueDate = $row['due_date'];
                        $isOverdue = $row['state'] === \App\Enums\ExecutionDueState::Overdue;
                    @endphp
                    <li>
                        <a
                            href="{{ route('aid-requests.show', $request) }}"
                            wire:navigate
                            class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-bg-secondary)] transition-colors"
                            role="menuitem"
                        >
                            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $isOverdue ? 'bg-[var(--color-danger-50)] text-[var(--color-danger-600)]' : 'bg-[var(--color-warning-50)] text-[var(--color-warning-600)]' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-[var(--color-text-primary)] truncate">
                                    {{ $request->title }}
                                </div>
                                <div class="text-xs text-[var(--color-text-muted)]">
                                    {{ $request->request_number }}
                                    —
                                    @if($isOverdue)
                                        متأخر منذ {{ $dueDate->format('Y-m-d') }}
                                    @else
                                        موعد {{ $dueDate->format('Y-m-d') }}
                                    @endif
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="px-4 py-2 border-t border-[var(--color-border)] bg-[var(--color-bg-secondary)]">
            <a
                href="{{ route('delivery.index') }}"
                wire:navigate
                class="block text-center text-sm font-medium text-[var(--accent-600)] hover:text-[var(--accent-700)] py-1"
            >
                التنفيذ والمتابعة ←
            </a>
        </div>
    </div>
</div>
