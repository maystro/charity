@props([
    'steps' => [],
    'current' => 1,
    'orientation' => 'horizontal',
    'size' => 'md',
])

@php
    $isVertical = $orientation === 'vertical';
    $connectorClass = $isVertical ? '' : 'flex-1 h-0.5';
@endphp

<div class="{{ $isVertical ? 'flex flex-col gap-0' : 'flex items-center gap-0' }}" {{ $attributes }}>
    @foreach($steps as $index => $step)
        @php
            $stepNum = $index + 1;
            $isCompleted = $stepNum < $current;
            $isCurrent = $stepNum === $current;
            $isUpcoming = $stepNum > $current;

            $circleBase = 'flex items-center justify-center rounded-full font-semibold shrink-0 transition-all duration-[var(--motion-normal)]';
            $circleSizes = [
                'sm' => 'w-8 h-8 text-xs',
                'md' => 'w-10 h-10 text-sm',
                'lg' => 'w-12 h-12 text-base',
            ];

            if ($isCompleted) {
                $circleClasses = $circleBase . ' ' . ($circleSizes[$size] ?? $circleSizes['md']) . ' bg-[var(--accent-500)] text-white';
                $textClasses = 'text-[var(--accent-700)] font-medium';
                $label = __('تم');
            } elseif ($isCurrent) {
                $circleClasses = $circleBase . ' ' . ($circleSizes[$size] ?? $circleSizes['md']) . ' bg-[var(--accent-500)] text-white ring-4 ring-[var(--accent-500)]/20';
                $textClasses = 'text-[var(--accent-700)] font-medium';
                $label = $step['label'] ?? $step;
            } else {
                $circleClasses = $circleBase . ' ' . ($circleSizes[$size] ?? $circleSizes['md']) . ' bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)]';
                $textClasses = 'text-[var(--color-text-muted)]';
                $label = $step['label'] ?? $step;
            }
        @endphp

        <div class="{{ $isVertical ? 'flex items-start gap-3 pb-6 last:pb-0' : 'flex items-center gap-3' }}">
            <div class="flex flex-col items-center">
                <div class="{{ $circleClasses }}">
                    @if($isCompleted)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    @else
                        {{ $stepNum }}
                    @endif
                </div>
            </div>

            <div class="{{ $isVertical ? 'flex flex-col' : 'hidden md:flex flex-col shrink-0' }}">
                <span class="text-sm {{ $textClasses }}">{{ $step['label'] ?? $step }}</span>
                @if(isset($step['description']))
                    <span class="text-xs text-[var(--color-text-muted)]">{{ $step['description'] }}</span>
                @endif
            </div>
        </div>

        @if(!$loop->last)
            <div class="{{ $isVertical ? 'mr-5 w-0.5 h-8' : 'flex-1 h-0.5 mx-3' }} {{ $stepNum < $current ? 'bg-[var(--accent-500)]' : 'bg-[var(--color-border)]' }}"></div>
        @endif
    @endforeach
</div>
