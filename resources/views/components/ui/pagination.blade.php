@props([
    'paginator' => null,
    'simple' => false,
    'size' => 'md',
])

@php
    if (!$paginator || $paginator->isEmpty()) {
        return;
    }

    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-10 w-10 text-sm',
    ];
    $btnClass = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="flex items-center justify-between gap-4 py-3">
    <p class="text-xs text-[var(--color-text-muted)]">
        {{ __('عرض') }}
        <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->firstItem() ?? 0 }}</span>
        -
        <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->lastItem() ?? 0 }}</span>
        {{ __('من') }}
        <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->total() }}</span>
    </p>

    <nav class="flex items-center gap-1" aria-label="Pagination">
        @if($paginator->onFirstPage())
            <span class="{{ $btnClass }} inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-muted)] opacity-40 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="{{ $btnClass }} inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
        @endif

        @if(!$simple)
            @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <a
                    href="{{ $url }}"
                    class="{{ $btnClass }} inline-flex items-center justify-center rounded-[var(--radius-sm)] transition-colors
                        {{ $page === $paginator->currentPage()
                            ? 'bg-[var(--accent-500)] text-white font-medium'
                            : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)]'
                        }}"
                >
                    {{ $page }}
                </a>
            @endforeach
        @endif

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="{{ $btnClass }} inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        @else
            <span class="{{ $btnClass }} inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-muted)] opacity-40 cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </span>
        @endif
    </nav>
</div>
