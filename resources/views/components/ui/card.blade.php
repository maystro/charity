@props([
    'variant' => 'default',
    'padding' => true,
    'hover' => false,
    'as' => 'div',
])

@php
    $classes = 'rounded-[var(--radius-lg)] border transition-all duration-[var(--motion-fast)]';

    $variants = [
        'default'     => 'bg-white border-[var(--color-border)] shadow-xs hover:shadow-sm',
        'stat'        => 'bg-white border-[var(--color-border)] shadow-xs hover:shadow-sm',
        'glass'       => 'glass border-[var(--glass-border)] shadow-xs',
        'interactive' => 'bg-white border-[var(--color-border)] shadow-xs hover:shadow-md hover:scale-[1.005] hover:border-[var(--accent-500)]/30 cursor-pointer',
    ];

    $classes .= ' ' . ($variants[$variant] ?? $variants['default']);
    if ($padding) $classes .= ' p-5';
    if ($hover && $variant !== 'interactive') $classes .= ' hover:shadow-md hover:scale-[1.005]';
@endphp

<{{ $as }} {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $as }}>
