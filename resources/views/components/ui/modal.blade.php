@props([
    'name',
    'title' => null,
    'subtitle' => null,
    'size' => 'md',
    'closeable' => true,
])

@php
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ((Array.isArray($event.detail) && $event.detail.includes('{{ $name }}')) || $event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ((Array.isArray($event.detail) && $event.detail.includes('{{ $name }}')) || $event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="if (open) open = false"
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[var(--z-modal)]"
    style="display: none;"
>
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" x-on:click="{{ $closeable ? 'open = false' : '' }}"></div>

    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="{{ $sizes[$size] ?? $sizes['md'] }} w-full bg-white rounded-[var(--radius-xl)] shadow-2xl"
            >
                @if($title || $subtitle || $closeable)
                    <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                        <div>
                            @if($title)
                                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $title }}</h3>
                            @endif
                            @if($subtitle)
                                <p class="mt-1 text-sm text-[var(--color-text-muted)]">{{ $subtitle }}</p>
                            @endif
                        </div>
                        @if($closeable)
                            <button x-on:click="open = false" class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        @endif
                    </div>
                @endif

                <div class="p-5">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
