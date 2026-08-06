@props(['items' => []])

@if(count($items) > 0)
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-[var(--color-text-muted)]">
        <ol class="flex items-center gap-1.5 flex-wrap">
            @foreach($items as $index => $item)
                @if($index > 0)
                    <svg class="w-3.5 h-3.5 text-[var(--color-text-muted)]/50 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                @endif

                @if($item['href'] ?? null)
                    <a href="{{ $item['href'] }}" class="text-[var(--color-text-muted)] hover:text-[var(--accent-500)] transition-colors">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-[var(--color-text-primary)] font-medium">{{ $item['label'] }}</span>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
