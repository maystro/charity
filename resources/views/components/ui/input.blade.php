@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'size' => 'md',
    'icon' => null,
    'prefix' => null,
    'suffix' => null,
])

@php
    $id = $attributes->get('id', $name ?? 'input-' . substr(md5(random_bytes(8)), 0, 8));
    $sizes = [
        'sm' => 'h-[var(--control-height-sm)] px-3 text-sm',
        'md' => 'h-[var(--control-height)] px-4 text-sm',
        'lg' => 'h-[var(--control-height-lg)] px-5 text-base',
    ];
    $inputClasses = 'w-full bg-white border rounded-[var(--radius-md)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] shadow-xs focus:outline-none focus:ring-4 focus:ring-[var(--accent-500)]/15 focus:border-[var(--accent-500)] transition-all duration-[var(--motion-fast)]';
    $inputClasses .= ' ' . ($sizes[$size] ?? $sizes['md']);
    if ($error) $inputClasses .= ' border-[var(--color-danger-500)] focus:ring-[var(--color-danger-500)]/15 focus:border-[var(--color-danger-500)]';
    else $inputClasses .= ' border-[var(--color-border)]';
    
    // In RTL / Arabic, the start padding should be right (ps) and end padding should be left (pe)
    if ($icon || $prefix) $inputClasses .= ' ps-10';
    if ($suffix) $inputClasses .= ' pe-10';
    $inputClasses .= ' ' . $attributes->get('class', '');
@endphp

<div class="flex flex-col gap-1.5">
    @if($label)
        <label for="{{ $id }}" class="text-sm font-medium text-[var(--color-text-primary)]">
            {{ $label }}
            @if($required)
                <span class="text-[var(--color-danger-500)]">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        @if($icon || $prefix)
            <div class="absolute inset-y-0 {{ app()->isLocale('ar') ? 'right' : 'left' }}-0 flex items-center {{ $icon ? 'px-3.5' : 'ps-3.5' }} pointer-events-none text-[var(--color-text-muted)]">
                @if($icon)
                    <span class="w-4 h-4 flex items-center justify-center text-current">
                        <x-dynamic-component :component="'heroicon-s-' . $icon" class="w-4 h-4 text-current" />
                    </span>
                @endif
                @if($prefix)
                    <span class="text-sm font-medium">{{ $prefix }}</span>
                @endif
            </div>
        @endif

        @if($suffix)
            <div class="absolute inset-y-0 {{ app()->isLocale('ar') ? 'left' : 'right' }}-0 flex items-center pe-3.5 pointer-events-none text-[var(--color-text-muted)]">
                <span class="text-sm font-medium">{{ $suffix }}</span>
            </div>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => $inputClasses]) }}
        />
    </div>

    @if($hint && !$error)
        <p class="text-xs text-[var(--color-text-muted)]">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="text-xs text-[var(--color-danger-500)]">{{ $error }}</p>
    @endif
</div>
