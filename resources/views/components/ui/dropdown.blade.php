@props([
    'align' => 'right',
    'width' => '48',
])

@php
    $widths = [
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        '72' => 'w-72',
        'auto' => 'w-auto',
    ];
@endphp

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    class="relative inline-block"
>
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger ?? $slot }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="open = false"
        class="absolute z-[var(--z-tooltip)] mt-2 {{ $align === 'left' ? 'left-0' : 'right-0' }} {{ $widths[$width] ?? $widths['48'] }} bg-white rounded-[var(--radius-lg)] shadow-xl border border-[var(--color-border)] py-1 focus:outline-none"
    >
        {{ $content ?? '' }}
    </div>
</div>
