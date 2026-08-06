@php
/** Pagination links view (used by $paginator->links()) uses app design tokens
 *  instead of default Tailwind + dark: classes that render black in dark OS.
 */
$perSide = 1;
$start = max(1, $paginator->currentPage() - $perSide);
$end = min($paginator->lastPage(), $paginator->currentPage() + $perSide);
$pages = $paginator->getUrlRange($start, $end);
@endphp

<nav class="flex items-center justify-between gap-4 py-3" aria-label="Pagination Navigation" role="navigation">
    <p class="text-xs text-[var(--color-text-muted)]">
        {{ __('عرض') }}
        <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->firstItem() ?? 0 }}</span>
        -
        <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->lastItem() ?? 0 }}</span>
        {{ __('من') }}
        <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->total() }}</span>
    </p>

    <div class="flex items-center gap-1">
        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="h-9 w-9 inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-muted)] opacity-40 cursor-not-allowed" aria-disabled="true" aria-label="{{ __('السابق') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" wire:navigate class="h-9 w-9 inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors" aria-label="{{ __('السابق') }}" rel="prev">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
            </a>
        @endif

        @foreach($pages as $page => $url)
            @if($page === $paginator->currentPage())
                <span class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm font-medium bg-[var(--accent-500)] text-white" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}" wire:navigate class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" wire:navigate class="h-9 w-9 inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors" aria-label="{{ __('التالي') }}" rel="next">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </a>
        @else
            <span class="h-9 w-9 inline-flex items-center justify-center rounded-[var(--radius-sm)] text-[var(--color-text-muted)] opacity-40 cursor-not-allowed" aria-disabled="true" aria-label="{{ __('التالي') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </span>
        @endif
    </div>
</nav>