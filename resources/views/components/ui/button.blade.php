@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'disabled' => false,
    'icon' => null,
    'iconPosition' => 'right', // right (start in RTL), left (end in RTL)
    'block' => false,
    'as' => 'button',
    'href' => null,
])

@php
    $tag = $href ? 'a' : $as;
    $base = 'inline-flex items-center justify-center gap-2 font-medium transition-all duration-[var(--motion-fast)] hover:scale-[1.01] active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:scale-100 select-none cursor-pointer';

    $variants = [
        'primary'   => 'bg-[var(--accent-500)] text-white hover:bg-[var(--accent-600)] hover:shadow-md focus-visible:ring-[var(--accent-500)] active:bg-[var(--accent-700)] shadow-sm',
        'secondary' => 'bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] border border-[var(--color-border)] hover:bg-[var(--color-border)] focus-visible:ring-[var(--color-border-strong)]',
        'outline'   => 'bg-transparent text-[var(--accent-500)] border border-[var(--accent-500)] hover:bg-[var(--accent-50)] focus-visible:ring-[var(--accent-500)]',
        'ghost'     => 'bg-transparent text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] focus-visible:ring-[var(--color-border-strong)]',
        'danger'    => 'bg-[var(--color-danger-500)] text-white hover:bg-[var(--color-danger-500)]/90 hover:shadow-md focus-visible:ring-[var(--color-danger-500)] active:bg-[var(--color-danger-600)] shadow-sm',
        'success'   => 'bg-[var(--color-success-500)] text-white hover:bg-[var(--color-success-500)]/90 hover:shadow-md focus-visible:ring-[var(--color-success-500)] active:bg-[var(--color-success-600)] shadow-sm',
    ];

    $sizes = [
        'sm' => 'h-[var(--control-height-sm)] px-3 text-sm rounded-[var(--radius-sm)]',
        'md' => 'h-[var(--control-height)] px-5 text-sm rounded-[var(--radius-md)]',
        'lg' => 'h-[var(--control-height-lg)] px-7 text-base rounded-[var(--radius-lg)]',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    if ($block) $classes .= ' w-full';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if($tag === 'button') type="{{ $attributes->get('type', 'button') }}" @endif
    @if($disabled || $loading) disabled @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if($loading)
        <svg class="animate-spin h-4 w-4 shrink-0 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span>{{ __('ui.loading') }}</span>
    @else
        {{-- In RTL, 'right' represents the start of the button (right-to-left) --}}
        @if($icon && $iconPosition === 'right')
            <span class="w-4 h-4 flex items-center justify-center shrink-0 text-current">
                <x-dynamic-component :component="'heroicon-s-' . $icon" class="w-4 h-4 text-current" />
            </span>
        @endif

        <span>{{ $slot }}</span>

        @if($icon && $iconPosition === 'left')
            <span class="w-4 h-4 flex items-center justify-center shrink-0 text-current">
                <x-dynamic-component :component="'heroicon-s-' . $icon" class="w-4 h-4 text-current" />
            </span>
        @endif
    @endif
</{{ $tag }}>
