@props([
    'variant' => 'neutral',
    'size' => 'md',
    'dot' => false,
])

@php
    $base = 'inline-flex items-center gap-1.5 font-medium rounded-full';

    $variants = [
        'primary'   => 'bg-[var(--accent-50)] text-[var(--accent-700)]',
        'secondary' => 'bg-[var(--color-bg-secondary)] text-[var(--color-text-secondary)]',
        'success'   => 'bg-[var(--color-success-50)] text-[var(--color-success-500)]',
        'danger'    => 'bg-[var(--color-danger-50)] text-[var(--color-danger-500)]',
        'warning'   => 'bg-[var(--color-warning-50)] text-[var(--color-warning-500)]',
        'info'      => 'bg-[var(--color-info-50)] text-[var(--color-info-500)]',
        'neutral'   => 'bg-gray-100 text-gray-600',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];

    $dotColors = [
        'primary'   => 'bg-[var(--accent-500)]',
        'secondary' => 'bg-gray-400',
        'success'   => 'bg-[var(--color-success-500)]',
        'danger'    => 'bg-[var(--color-danger-500)]',
        'warning'   => 'bg-[var(--color-warning-500)]',
        'info'      => 'bg-[var(--color-info-500)]',
        'neutral'   => 'bg-gray-400',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['neutral']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$variant] ?? $dotColors['neutral'] }}"></span>
    @endif
    {{ $slot }}
</span>
