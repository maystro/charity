@props([
    'variant' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $variants = [
        'info'    => 'bg-[var(--color-info-50)] text-[var(--color-info-500)] border-[var(--color-info-500)]/20',
        'success' => 'bg-[var(--color-success-50)] text-[var(--color-success-500)] border-[var(--color-success-500)]/20',
        'warning' => 'bg-[var(--color-warning-50)] text-[var(--color-warning-500)] border-[var(--color-warning-500)]/20',
        'danger'  => 'bg-[var(--color-danger-50)] text-[var(--color-danger-500)] border-[var(--color-danger-500)]/20',
    ];
    $classes = 'rounded-[var(--radius-md)] border p-4 flex gap-3' . ' ' . ($variants[$variant] ?? $variants['info']);
@endphp

<div
    {{ $attributes->merge(['class' => $classes]) }}
    @if($dismissible) x-data="{ show: true }" x-show="show" x-cloak @endif
>
    <div class="shrink-0 mt-0.5">
        @if($variant === 'info')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
        @elseif($variant === 'success')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        @elseif($variant === 'warning')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        @elseif($variant === 'danger')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
        @endif
    </div>
    <div class="flex-1 min-w-0">
        @if($title)
            <h4 class="text-sm font-semibold">{{ $title }}</h4>
        @endif
        <div class="text-sm {{ $title ? 'mt-1 opacity-90' : '' }}">
            {{ $slot }}
        </div>
    </div>
    @if($dismissible)
        <button x-on:click="show = false" class="shrink-0 p-1 rounded hover:bg-black/5 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    @endif
</div>
