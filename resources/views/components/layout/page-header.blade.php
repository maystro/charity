@props([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div class="flex flex-col gap-2 mb-6">
    @if(count($breadcrumbs) > 0)
        <nav aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-sm text-[var(--color-text-muted)]">
                <li>
                    <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-[var(--accent-600)] transition-colors">
                        لوحة التحكم
                    </a>
                </li>
                @foreach($breadcrumbs as $crumb)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        @if($loop->last || !isset($crumb['route']))
                            <span class="text-[var(--color-text-primary)]">{{ $crumb['label'] }}</span>
                        @else
                            <a href="{{ route($crumb['route'], $crumb['query'] ?? []) }}" wire:navigate class="hover:text-[var(--accent-600)] transition-colors">
                                {{ $crumb['label'] }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="flex items-end justify-between gap-4">
        <div>
            @if($title)
                <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $title }}</h1>
            @endif
            @if($subtitle)
                <p class="text-sm text-[var(--color-text-muted)] mt-1">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
