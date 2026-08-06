@props([
    'type' => 'text',
    'width' => null,
    'height' => null,
    'lines' => 1,
    'rounded' => true,
])

@php
    $base = 'animate-pulse bg-[var(--color-bg-secondary)]';
    if ($rounded) $base .= ' rounded-[var(--radius-md)]';

    $typeClasses = match($type) {
        'text' => 'h-4 w-full',
        'title' => 'h-6 w-48',
        'avatar' => 'rounded-full',
        'thumbnail' => 'aspect-square',
        'button' => 'h-[var(--control-height)] w-24',
        'card' => 'h-40 w-full',
        default => '',
    };

    $style = '';
    if ($width) $style .= "width: $width;";
    if ($height) $style .= "height: $height;";
@endphp

@if($type === 'text' && $lines > 1)
    <div class="space-y-2.5">
        @for($i = 0; $i < $lines; $i++)
            <div class="{{ $base }} {{ $typeClasses }}" @if($style) style="{{ $style }}" @endif></div>
        @endfor
    </div>
@else
    <div class="{{ $base }} {{ $typeClasses }}" @if($style) style="{{ $style }}" @endif {{ $attributes }}></div>
@endif
