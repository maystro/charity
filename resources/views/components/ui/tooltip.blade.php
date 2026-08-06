@props([
    'content' => null,
    'position' => 'top',
    'delay' => 200,
])

@php
    $positions = [
        'top' => 'bottom-full mb-2 left-1/2 -translate-x-1/2',
        'bottom' => 'top-full mt-2 left-1/2 -translate-x-1/2',
        'left' => 'right-full mr-2 top-1/2 -translate-y-1/2',
        'right' => 'left-full ml-2 top-1/2 -translate-y-1/2',
    ];
@endphp

<div
    x-data="{ show: false }"
    @mouseenter="setTimeout(() => show = true, {{ $delay }})"
    @mouseleave="show = false"
    @focusin="show = true"
    @focusout="show = false"
    class="relative inline-flex"
>
    {{ $slot }}

    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="absolute z-[var(--z-tooltip)] {{ $positions[$position] ?? $positions['top'] }} px-2 py-1 text-xs text-white bg-[var(--color-text-primary)] rounded-[var(--radius-sm)] whitespace-nowrap pointer-events-none shadow-lg"
    >
        {{ $content ?? $attributes->get('content', '') }}
    </div>
</div>
