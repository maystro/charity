@props([
    'name',
    'title' => 'تأكيد الإجراء',
    'message' => 'هل أنت متأكد من رغبتك في تنفيذ هذا الإجراء؟',
    'confirmText' => 'تأكيد',
    'cancelText' => 'إلغاء',
    'variant' => 'danger',
    'size' => 'sm',
    'action' => null,
])

<x-ui.modal :name="$name" :title="$title" size="sm">
    <p class="text-sm text-[var(--color-text-secondary)]">{{ $message }}</p>

    @if(isset($slot) && $slot->isNotEmpty())
        <div class="mt-3">
            {{ $slot }}
        </div>
    @endif

    <div class="flex items-center justify-end gap-3 mt-6">
        <button
            x-on:click="$dispatch('close-modal', '{{ $name }}')"
            type="button"
            class="inline-flex items-center justify-center h-[var(--control-height)] px-5 text-sm font-medium rounded-[var(--radius-md)] bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] hover:bg-[var(--color-border)] transition-colors"
        >
            {{ $cancelText }}
        </button>

        <button
            x-on:click="$dispatch('close-modal', '{{ $name }}')@if($action);{{ $action }}@endif"
            type="button"
            class="inline-flex items-center justify-center gap-2 h-[var(--control-height)] px-5 text-sm font-medium rounded-[var(--radius-md)] transition-colors
                {{ $variant === 'danger' ? 'bg-[var(--color-danger-500)] text-white hover:bg-[var(--color-danger-500)]/90' : 'bg-[var(--accent-500)] text-white hover:bg-[var(--accent-600)]' }}"
        >
            {{ $confirmText }}
        </button>
    </div>
</x-ui.modal>
