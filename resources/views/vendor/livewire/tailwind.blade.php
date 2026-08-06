@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$perSide = 1;
$start = max(1, $paginator->currentPage() - $perSide);
$end = min($paginator->lastPage(), $paginator->currentPage() + $perSide);
$pages = $paginator->getUrlRange($start, $end);
$btnPrev = 'السابق';
$btnNext = 'التالي';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between gap-4 py-3">
            <p class="text-xs text-[var(--color-text-muted)]">
                <span>{!! __('Showing') !!}</span>
                <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->firstItem() ?? 0 }}</span>
                <span>{!! __('to') !!}</span>
                <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->lastItem() ?? 0 }}</span>
                <span>{!! __('of') !!}</span>
                <span class="font-medium text-[var(--color-text-secondary)]">{{ $paginator->total() }}</span>
            </p>

            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-muted)] opacity-40 cursor-not-allowed" aria-disabled="true" aria-label="{{ $btnPrev }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() === 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors" aria-label="{{ $btnPrev }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                @endif

                {{-- Page numbers --}}
                @foreach ($pages as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm font-medium bg-[var(--accent-500)] text-white" aria-current="page">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors">{{ $page }}</button>
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() === 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-secondary)] transition-colors" aria-label="{{ $btnNext }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                @else
                    <span class="h-9 min-w-9 inline-flex items-center justify-center px-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-muted)] opacity-40 cursor-not-allowed" aria-disabled="true" aria-label="{{ $btnNext }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>