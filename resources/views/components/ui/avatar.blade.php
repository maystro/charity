@props([
    'src' => null,
    'alt' => null,
    'name' => null,
    'size' => 'md',
    'status' => null,
])

@php
    $sizes = [
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-14 h-14 text-lg',
        'xl' => 'w-20 h-20 text-xl',
    ];
    $statusSizes = [
        'sm' => 'w-2.5 h-2.5 border',
        'md' => 'w-3 h-3 border-2',
        'lg' => 'w-3.5 h-3.5 border-2',
        'xl' => 'w-4 h-4 border-2',
    ];
    $statusColors = [
        'online' => 'bg-[var(--color-success-500)]',
        'offline' => 'bg-gray-400',
        'busy' => 'bg-[var(--color-danger-500)]',
    ];

    $initials = '';
    if ($name && !$src) {
        $parts = explode(' ', trim($name));
        $initials = mb_substr($parts[0], 0, 1);
        if (count($parts) > 1) $initials .= mb_substr(end($parts), 0, 1);
        $initials = strtoupper($initials);
    }
@endphp

<div class="relative inline-flex shrink-0" {{ $attributes }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt ?? $name }}" class="{{ $sizes[$size] ?? $sizes['md'] }} rounded-full object-cover" />
    @else
        <div class="{{ $sizes[$size] ?? $sizes['md'] }} rounded-full bg-[var(--accent-500)]/10 text-[var(--accent-700)] flex items-center justify-center font-semibold">
            {{ $initials }}
        </div>
    @endif

    @if($status)
        <span class="absolute bottom-0 {{ app()->isLocale('ar') ? 'left-0' : 'right-0' }} block {{ $statusSizes[$size] ?? $statusSizes['md'] }} {{ $statusColors[$status] ?? $statusColors['offline'] }} rounded-full ring-2 ring-white"></span>
    @endif
</div>
