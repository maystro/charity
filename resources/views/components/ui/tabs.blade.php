@props([
    'tabs' => [],
    'active' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'text-sm px-3 py-2',
        'md' => 'text-sm px-4 py-2.5',
        'lg' => 'text-base px-5 py-3',
    ];
    $activeTab = $active ?? array_key_first($tabs);
@endphp

<div
    x-data="{ activeTab: '{{ $activeTab }}' }"
    class="flex flex-col"
>
    <div class="border-b border-[var(--color-border)]" role="tablist">
        <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
            @foreach($tabs as $key => $tab)
                <button
                    x-on:click="activeTab = '{{ $key }}'"
                    :class="activeTab === '{{ $key }}' ? 'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' : 'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]'"
                    class="{{ $sizes[$size] ?? $sizes['md'] }} border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap"
                    role="tab"
                    :aria-selected="activeTab === '{{ $key }}'"
                >
                    {{ $tab }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="py-5">
        {{ $slot }}
    </div>
</div>
