@props([
    'label' => null,
    'name' => null,
    'checked' => false,
    'disabled' => false,
    'size' => 'md',
])

@php
    $id = $attributes->get('id', $name ?? 'switch-' . substr(md5(random_bytes(8)), 0, 8));
    $sizes = [
        'sm' => 'w-9 h-5 after:w-4 after:h-4 after:top-0.5 after:start-0.5',
        'md' => 'w-11 h-6 after:w-5 after:h-5 after:top-0.5 after:start-0.5',
        'lg' => 'w-14 h-7 after:w-6 after:h-6 after:top-0.5 after:start-0.5',
    ];
@endphp

<label for="{{ $id }}" class="inline-flex items-center gap-3 cursor-pointer select-none {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    <input
        type="checkbox"
        id="{{ $id }}"
        name="{{ $name }}"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        class="sr-only peer"
        style="top:0;left:0;"
        {{ $attributes->except(['class', 'id', 'name', 'checked', 'disabled']) }}
    />
    <div class="relative {{ $sizes[$size] ?? $sizes['md'] }} bg-[var(--color-border-strong)] rounded-full peer-checked:bg-[var(--accent-500)] peer-focus-visible:ring-4 peer-focus-visible:ring-[var(--accent-500)]/20 transition-all duration-[var(--motion-fast)] after:content-[''] after:absolute after:bg-white after:rounded-full after:shadow-md after:transition-transform after:duration-[var(--motion-fast)] peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full">
    </div>
    @if($label)
        <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $label }}</span>
    @endif
</label>
