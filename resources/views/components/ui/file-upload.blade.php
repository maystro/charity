@props([
    'label' => null,
    'name' => null,
    'accept' => 'image/*, .pdf, .doc, .docx',
    'multiple' => false,
    'required' => false,
    'error' => null,
    'hint' => null,
    'maxSize' => '5MB',
])

@php
    $id = $attributes->get('id', $name ?? 'file-' . substr(md5(random_bytes(8)), 0, 8));
@endphp

<div class="flex flex-col gap-1.5">
    @if($label)
        <label for="{{ $id }}" class="text-sm font-medium text-[var(--color-text-primary)]">
            {{ $label }}
            @if($required)<span class="text-[var(--color-danger-500)]">*</span>@endif
        </label>
    @endif

    <label
        for="{{ $id }}"
        class="flex flex-col items-center justify-center px-6 py-8 border-2 border-dashed border-[var(--color-border)] rounded-[var(--radius-lg)] bg-[var(--color-bg-secondary)]/30 hover:bg-[var(--color-bg-secondary)]/50 hover:border-[var(--accent-500)]/40 cursor-pointer transition-colors duration-[var(--motion-fast)]"
    >
        <svg class="w-10 h-10 text-[var(--color-text-muted)] mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <span class="text-sm font-medium text-[var(--color-text-secondary)]">{{ __('انقر لرفع ملف') }}</span>
        <span class="text-xs text-[var(--color-text-muted)] mt-1">{{ $hint ?? __('الحد الأقصى') . ': ' . $maxSize }}</span>
    </label>

    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        @if($multiple) multiple @endif
        @if($required) required @endif
        class="sr-only"
    />

    @if($error)
        <p class="text-xs text-[var(--color-danger-500)]">{{ $error }}</p>
    @endif
</div>
