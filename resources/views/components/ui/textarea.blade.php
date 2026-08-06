@props([
    'label' => null,
    'name' => null,
    'placeholder' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'rows' => 4,
])

@php
    $id = $attributes->get('id', $name ?? 'textarea-' . substr(md5(random_bytes(8)), 0, 8));
    $baseClasses = 'w-full bg-white border rounded-[var(--radius-md)] px-4 py-3 text-sm text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] shadow-xs focus:outline-none focus:ring-4 focus:ring-[var(--accent-500)]/15 focus:border-[var(--accent-500)] transition-all duration-[var(--motion-fast)] resize-y';
    if ($error) $baseClasses .= ' border-[var(--color-danger-500)] focus:ring-[var(--color-danger-500)]/15 focus:border-[var(--color-danger-500)]';
    else $baseClasses .= ' border-[var(--color-border)]';
@endphp

<div class="flex flex-col gap-1.5">
    @if($label)
        <label for="{{ $id }}" class="text-sm font-medium text-[var(--color-text-primary)]">
            {{ $label }}
            @if($required)<span class="text-[var(--color-danger-500)]">*</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => $baseClasses]) }}
    >{{ $slot }}</textarea>

    @if($hint && !$error)
        <p class="text-xs text-[var(--color-text-muted)]">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs text-[var(--color-danger-500)]">{{ $error }}</p>
    @endif
</div>
