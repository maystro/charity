@props([
    'title' => null,
    'open' => false,
    'icon' => null,
])

<div
    x-data="{ open: {{ $open ? 'true' : 'false' }} }"
    class="border border-[var(--color-border)] rounded-[var(--radius-md)] overflow-hidden transition-all duration-[var(--motion-fast)]"
    {{ $attributes }}
>
    <button
        x-on:click="open = !open"
        class="w-full flex items-center justify-between gap-3 px-5 py-4 text-sm font-medium text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)]/50 transition-colors focus:outline-none focus-visible:bg-[var(--color-bg-secondary)]"
    >
        <div class="flex items-center gap-3">
            @if($icon)
                <span class="w-5 h-5 flex items-center justify-center shrink-0 text-[var(--color-text-muted)]">
                    <x-dynamic-component :component="'heroicon-s-' . $icon" class="w-5 h-5 text-current" />
                </span>
            @endif
            <span class="text-right">{{ $title ?? $slot }}</span>
        </div>
        <svg
            class="w-4 h-4 text-[var(--color-text-muted)] transition-transform duration-[var(--motion-fast)]"
            :class="{ 'rotate-180': open }"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 9l-7.5 7.5L4.5 9" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-collapse.duration.250ms
        class="px-5 pb-4"
    >
        {{ $content ?? '' }}
    </div>
</div>
