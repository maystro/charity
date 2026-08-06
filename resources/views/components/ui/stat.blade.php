@props([
    'icon' => 'bell',
    'number' => 0,
    'label' => '',
    'variant' => 'neutral',
    'href' => null,
    'pulse' => false,
])

@php
    $variants = [
        'primary' => [
            'bg'        => 'bg-[var(--accent-50)]',
            'iconBg'    => 'bg-[var(--accent-500)]',
            'iconColor' => 'text-white',
            'numColor'  => 'text-[var(--accent-700)]',
            'labColor'  => 'text-[var(--color-text-secondary)]',
            'hover'     => 'hover:bg-[var(--accent-100)]',
        ],
        'warning' => [
            'bg'        => 'bg-[var(--color-warning-50)]',
            'iconBg'    => 'bg-[var(--color-warning-500)]',
            'iconColor' => 'text-white',
            'numColor'  => 'text-[var(--color-warning-700)]',
            'labColor'  => 'text-[var(--color-text-secondary)]',
            'hover'     => 'hover:bg-[var(--color-warning-100)]',
        ],
        'danger' => [
            'bg'        => 'bg-[var(--color-danger-50)]',
            'iconBg'    => 'bg-[var(--color-danger-500)]',
            'iconColor' => 'text-white',
            'numColor'  => 'text-[var(--color-danger-700)]',
            'labColor'  => 'text-[var(--color-text-secondary)]',
            'hover'     => 'hover:bg-[var(--color-danger-100)]',
        ],
        'success' => [
            'bg'        => 'bg-[var(--color-success-50)]',
            'iconBg'    => 'bg-[var(--color-success-500)]',
            'iconColor' => 'text-white',
            'numColor'  => 'text-[var(--color-success-700)]',
            'labColor'  => 'text-[var(--color-text-secondary)]',
            'hover'     => 'hover:bg-[var(--color-success-100)]',
        ],
        'neutral' => [
            'bg'        => 'bg-[var(--color-bg-secondary)]',
            'iconBg'    => 'bg-[var(--color-text-muted)]',
            'iconColor' => 'text-white',
            'numColor'  => 'text-[var(--color-text-primary)]',
            'labColor'  => 'text-[var(--color-text-secondary)]',
            'hover'     => 'hover:bg-[var(--color-bg-tertiary)]',
        ],
    ];

    $v = $variants[$variant] ?? $variants['neutral'];

    // Inline icon library (Heroicons outline 24x24) — keeps the topbar light and dependency-free
    $icons = [
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
        'arrow-path' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>',
        'bell-alert' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5"/>',
        'exclamation-triangle' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>',
        'archive-box' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
        'calendar-days' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008z"/>',
    ];
    $iconPath = $icons[$icon] ?? $icons['bell'];

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => "group inline-flex items-center gap-3 px-3 py-2 rounded-[var(--radius-lg)] transition-colors {$v['bg']} {$v['hover']}"]) }}
>
    {{-- Icon circle --}}
    <span class="relative shrink-0 w-9 h-9 rounded-full {{ $v['iconBg'] }} flex items-center justify-center shadow-sm">
        <svg class="w-5 h-5 {{ $v['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            {!! $iconPath !!}
        </svg>
        @if($pulse && $number > 0)
            <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
        @endif
    </span>

    {{-- Number + label --}}
    <span class="flex flex-col leading-tight min-w-0">
        <span class="text-lg font-bold {{ $v['numColor'] }} tabular-nums">{{ $number }}</span>
        <span class="text-[11px] font-medium {{ $v['labColor'] }} truncate">{{ $label }}</span>
    </span>
</{{ $tag }}>
