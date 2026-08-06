@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
])

@php
    $id = $attributes->get('id', $name . '-' . $value ?? 'radio-' . substr(md5(random_bytes(8)), 0, 8));
@endphp

<label for="{{ $id }}" class="inline-flex items-center gap-3 cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
    <input
        type="radio"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @if($checked) checked @endif
        @if($disabled) disabled @endif
        class="w-4 h-4 border-[var(--color-border)] text-[var(--accent-500)] focus:ring-[var(--accent-500)]/20 cursor-pointer accent-[var(--accent-500)]"
    />
    @if($label)
        <span class="text-sm text-[var(--color-text-primary)]">{{ $label }}</span>
    @endif
</label>
