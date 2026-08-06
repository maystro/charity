@props([
    'message' => __('تم الحفظ بنجاح'),
    'type' => 'success',
    'duration' => 4000,
])

@php
    $icons = [
        'success' => 'check-circle',
        'error' => 'exclamation-triangle',
        'warning' => 'exclamation-triangle',
        'info' => 'bell',
    ];
@endphp

<div
    x-data="{
        show: false,
        message: '{{ $message }}',
        type: '{{ $type }}',
        init() {
            Livewire.on('notify', (data) => {
                this.message = data.message ?? '{{ $message }}';
                this.type = data.type ?? '{{ $type }}';
                this.show = true;
                setTimeout(() => this.show = false, {{ $duration }});
            });
        }
    }"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
    class="fixed bottom-6 {{ app()->isLocale('ar') ? 'left-6' : 'right-6' }} z-[var(--z-toast)] max-w-sm"
    role="alert"
>
    <div class="flex items-center gap-3 px-5 py-4 rounded-[var(--radius-lg)] shadow-xl border text-sm font-medium backdrop-blur-md
        {{ $type === 'success' ? 'bg-[var(--color-success-50)]/90 border-[var(--color-success-500)]/20 text-[var(--color-success-500)]' : '' }}
        {{ $type === 'error' ? 'bg-[var(--color-danger-50)]/90 border-[var(--color-danger-500)]/20 text-[var(--color-danger-500)]' : '' }}
        {{ $type === 'warning' ? 'bg-[var(--color-warning-50)]/90 border-[var(--color-warning-500)]/20 text-[var(--color-warning-500)]' : '' }}
        {{ $type === 'info' ? 'bg-[var(--color-info-50)]/90 border-[var(--color-info-500)]/20 text-[var(--color-info-500)]' : '' }}
    ">
        <span class="w-5 h-5 flex items-center justify-center shrink-0 text-current">
            <x-dynamic-component :component="'heroicon-s-' . ($icons[$type] ?? 'check-circle')" class="w-5 h-5 text-current" />
        </span>
        <span x-text="message" class="flex-1"></span>
        <button x-on:click="show = false" class="shrink-0 p-1 rounded-lg hover:bg-black/5 text-current transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>
</div>
