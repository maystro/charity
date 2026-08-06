<?php

use Livewire\Volt\Component;

new class extends Component {
    public array $items = [];

    #[\Livewire\Attributes\Url]
    public string $homeLabel = 'لوحة التحكم';

    public function mount(array $items = []): void
    {
        $this->items = $items;
    }

    #[\Livewire\Attributes\On('breadcrumb-updated')]
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}; ?>

<nav aria-label="breadcrumb" class="text-sm text-[var(--color-text-muted)]">
    <ol class="flex items-center gap-2">
        <li>
            <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-[var(--accent-600)] transition-colors">{{ $homeLabel }}</a>
        </li>
        @foreach($items as $item)
            <li class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                @if($loop->last)
                    <span class="text-[var(--color-text-primary)] font-medium">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] ?? route($item['route'] ?? 'dashboard') }}" wire:navigate class="hover:text-[var(--accent-600)] transition-colors">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
