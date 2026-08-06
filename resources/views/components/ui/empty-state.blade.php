@props([
    'icon' => 'archive',
    'title' => 'لا توجد بيانات',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div class="flex flex-col items-center justify-center py-16 px-6 text-center">
    <div class="w-16 h-16 rounded-full bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)] flex items-center justify-center mb-4 shadow-inner">
        <span class="w-8 h-8 flex items-center justify-center text-current">
            @php
                $component = 'heroicon-s-' . $icon;
                $iconExists = class_exists($component) || view()->exists('components.'.$component);
            @endphp
            @if($iconExists)
                <x-dynamic-component :component="$component" class="w-8 h-8 text-current" />
            @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="w-8 h-8 text-current">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7.5V19a2 2 0 01-2 2H6a2 2 0 01-2-2V7.5m16 0a2 2 0 00-2-2H6a2 2 0 00-2 2m16 0H4m4 0v-1a2 2 0 012-2h4a2 2 0 012 2v1" />
                </svg>
            @endif
        </span>
    </div>

    <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ $title }}</h3>

    @if($description)
        <p class="mt-2 text-sm text-[var(--color-text-muted)] max-w-sm leading-relaxed">{{ $description }}</p>
    @endif

    @if($actionLabel && $actionHref)
        <a href="{{ $actionHref }}" class="mt-5 inline-flex items-center gap-2 h-[var(--control-height)] px-5 bg-[var(--accent-500)] text-white text-sm font-medium rounded-[var(--radius-md)] hover:bg-[var(--accent-600)] hover:scale-[1.01] active:scale-[0.98] transition-all duration-[var(--motion-fast)] shadow-sm hover:shadow-md">
            {{ $actionLabel }}
        </a>
    @elseif($actionLabel)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
