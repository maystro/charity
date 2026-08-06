@props([
    'label' => null,
    'name' => null,
    'checked' => false,
    'disabled' => false,
    'hint' => null,
    'error' => null,
])

@php
    $id = $attributes->get('id', $name ?? 'checkbox-' . substr(md5(random_bytes(8)), 0, 8));
@endphp

<div class="flex flex-col gap-1">
    <label for="{{ $id }}" class="inline-flex items-center gap-3 cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
        <input
            type="checkbox"
            id="{{ $id }}"
            name="{{ $name }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            {{ $attributes->except(['label', 'name', 'checked', 'disabled', 'hint', 'error']) }}
            class="w-4 h-4 rounded border-[var(--color-border)] text-[var(--accent-500)] focus:ring-[var(--accent-500)]/20 cursor-pointer accent-[var(--accent-500)]"
        />
        @if($label)
            <span class="text-sm text-[var(--color-text-primary)]">{{ $label }}</span>
        @endif
    </label>
    @if($hint && !$error)
        <p class="text-xs text-[var(--color-text-muted)] mr-7">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs text-[var(--color-danger-500)] mr-7">{{ $error }}</p>
    @endif
</div>
