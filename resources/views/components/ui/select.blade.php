@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => 'اختر...',
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'size' => 'md',
])

@php
    $id = $attributes->get('id', $name ?? 'select-' . substr(md5(random_bytes(8)), 0, 8));
    $sizes = [
        'sm' => 'h-[var(--control-height-sm)] pr-3 pl-8 text-sm',
        'md' => 'h-[var(--control-height)] pr-4 pl-10 text-sm',
        'lg' => 'h-[var(--control-height-lg)] pr-5 pl-12 text-base',
    ];
    $selectClasses = 'w-full bg-white border rounded-[var(--radius-md)] text-[var(--color-text-primary)] focus:outline-none focus:ring-4 focus:ring-[var(--accent-500)]/15 focus:border-[var(--accent-500)] shadow-xs transition-all duration-[var(--motion-fast)] appearance-none bg-[url("data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20fill%3D%27none%27%20viewBox%3D%270%200%2024%2024%27%20stroke-width%3D%271.5%27%20stroke%3D%27%23666%27%3E%3Cpath%20stroke-linecap%3D%27round%27%20stroke-linejoin%3D%27round%27%20d%3D%27M19.5%208.25l-7.5%207.5-7.5-7.5%27/%3E%3C/svg%3E")] bg-[length:1.25rem] bg-[position:left_0.75rem_center] bg-no-repeat';
    $selectClasses .= ' ' . ($sizes[$size] ?? $sizes['md']);
    if ($error) $selectClasses .= ' border-[var(--color-danger-500)] focus:ring-[var(--color-danger-500)]/15 focus:border-[var(--color-danger-500)]';
    else $selectClasses .= ' border-[var(--color-border)]';
@endphp

<div class="flex flex-col gap-1.5">
    @if($label)
        <label for="{{ $id }}" class="text-sm font-medium text-[var(--color-text-primary)]">
            {{ $label }}
            @if($required)<span class="text-[var(--color-danger-500)]">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->merge(['class' => $selectClasses]) }}
        >
            @if($placeholder)
                <option value="" disabled selected>{{ $placeholder }}</option>
            @endif
            @foreach($options as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
            {{ $slot }}
        </select>
    </div>

    @if($hint && !$error)
        <p class="text-xs text-[var(--color-text-muted)]">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs text-[var(--color-danger-500)]">{{ $error }}</p>
    @endif
</div>
