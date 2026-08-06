{{--
    NewAidRequestsStat — top bar tile showing new aid requests awaiting review.

    Displays: icon + number + label ("X طلبات مساعدة جديدة").
    On click: opens a dropdown with the top 5 new requests and a
    "View all" link to the aid requests index.
--}}
@php
    $count = $this->newRequestsCount;
    $variant = $count > 0 ? 'info' : 'success';
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
            {{ $variant === 'info' ? 'bg-[var(--color-info-50)] hover:bg-[var(--color-info-100)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-50)] hover:bg-[var(--color-success-100)]' : '' }}
        "
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ $count }} طلبات مساعدة جديدة"
    >
        {{-- Icon --}}
        <span class="relative shrink-0 w-9 h-9 rounded-full flex items-center justify-center shadow-sm
            {{ $variant === 'info' ? 'bg-[var(--color-info-500)]' : '' }}
            {{ $variant === 'success' ? 'bg-[var(--color-success-500)]' : '' }}
        ">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>

            @if($count > 0)
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
            @endif
        </span>

        {{-- Number + label --}}
        <span class="flex flex-col leading-tight min-w-0 text-right">
            <span class="text-lg font-bold tabular-nums
                {{ $variant === 'info' ? 'text-[var(--color-info-700)]' : '' }}
                {{ $variant === 'success' ? 'text-[var(--color-success-700)]' : '' }}
            ">{{ $count }}</span>
            <span class="text-[11px] font-medium text-[var(--color-text-secondary)] truncate">طلبات مساعدة جديدة</span>
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
                    طلبات مساعدة جديدة
                </h3>
                <span class="text-xs text-[var(--color-text-muted)]">
                    {{ $count }} طلب
                </span>
            </div>
        </div>

        {{-- List --}}
        @if($this->topNewRequests->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-[var(--color-text-muted)]">
                <svg class="w-10 h-10 mx-auto mb-2 text-[var(--color-success-500)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                لا توجد طلبات مساعدة جديدة
            </div>
        @else
            <ul class="max-h-80 overflow-y-auto divide-y divide-[var(--color-border)]">
                @foreach($this->topNewRequests as $request)
                    <li>
                        <a
                            href="{{ route('aid-requests.show', $request) }}"
                            wire:navigate
                            class="flex items-center gap-3 px-4 py-3 hover:bg-[var(--color-bg-secondary)] transition-colors"
                            role="menuitem"
                        >
                            <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-[var(--color-info-50)] text-[var(--color-info-500)]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                                </svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-[var(--color-text-primary)] truncate">
                                    {{ $request->title }}
                                </div>
                                <div class="text-xs text-[var(--color-text-muted)]">
                                    {{ $request->request_number }} — {{ $request->family?->case_name ?? '—' }}
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
                href="{{ route('aid-requests.index') }}"
                wire:navigate
                class="block text-center text-sm font-medium text-[var(--accent-600)] hover:text-[var(--accent-700)] py-1"
            >
                عرض كل الطلبات ←
            </a>
        </div>
    </div>
</div>
