<?php

use App\Models\UserPreference;
use Livewire\Volt\Component;

new
class extends Component
{
    public string $accentColor = 'copper';
    public string $fontSize = 'medium';
    public string $uiDensity = 'balanced';
    public bool $reducedMotion = false;
    public bool $sidebarCollapsed = false;

    public array $accentOptions = [
        'copper' => ['label' => 'نحاسي', 'color' => '#b87333'],
        'gold' => ['label' => 'ذهبي', 'color' => '#d4a843'],
        'olive' => ['label' => 'زيتوني', 'color' => '#6b8e23'],
        'emerald' => ['label' => 'زمردي', 'color' => '#2e8b57'],
        'blue' => ['label' => 'أزرق', 'color' => '#4682b4'],
        'purple' => ['label' => 'بنفسجي', 'color' => '#7b68ae'],
        'rose' => ['label' => 'وردي', 'color' => '#c46b7c'],
        'orange' => ['label' => 'برتقالي', 'color' => '#d2691e'],
    ];

    public function mount(): void
    {
        $pref = auth()->user()?->preferences;

        if ($pref) {
            $this->accentColor = $pref->accent_color ?: 'copper';
            $this->fontSize = $pref->font_size ?: 'medium';
            $this->uiDensity = $pref->ui_density ?: 'balanced';
            $this->reducedMotion = (bool) $pref->reduced_motion;
            $this->sidebarCollapsed = $pref->sidebar_state === 'collapsed';
        }
    }

    public function updatedAccentColor(): void
    {
        $this->persistAndApply();
    }

    public function updatedFontSize(): void
    {
        $this->persistAndApply();
    }

    public function updatedUiDensity(): void
    {
        $this->persistAndApply();
    }

    public function updatedReducedMotion(): void
    {
        $this->persistAndApply();
    }

    public function updatedSidebarCollapsed(): void
    {
        $this->persistAndApply();
        $this->dispatch('sidebar-state-changed', collapsed: $this->sidebarCollapsed);
    }

    private function persistAndApply(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'accent_color' => $this->accentColor,
                'font_size' => $this->fontSize,
                'ui_density' => $this->uiDensity,
                'reduced_motion' => $this->reducedMotion,
                'sidebar_state' => $this->sidebarCollapsed ? 'collapsed' : 'open',
            ]
        );

        $this->dispatch('preferences-applied', [
            'accent' => $this->accentColor,
            'fontSize' => $this->fontSize,
            'density' => $this->uiDensity,
            'reducedMotion' => $this->reducedMotion,
        ]);
    }
}

?>

<div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'user-preferences') open = true">
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[var(--z-modal)]"
        @keydown.escape.window="open = false"
    >
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="open = false"></div>

        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-[var(--motion-normal)]"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-[var(--motion-fast)]"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="w-full max-w-lg bg-white rounded-[var(--radius-xl)] shadow-2xl"
                >
                    <div class="flex items-start justify-between gap-4 p-5 border-b border-[var(--color-border)]">
                        <div>
                            <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">{{ __('ui.interface_preferences') }}</h3>
                            <p class="mt-1 text-sm text-[var(--color-text-muted)]">خصص مظهر النظام حسب راحتك</p>
                        </div>
                        <button @click="open = false" class="p-1 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-5 space-y-6">
                        {{-- Accent Color --}}
                        <div>
                            <label class="block text-sm font-medium mb-3 text-[var(--color-text-primary)]">{{ __('ui.accent_color') }}</label>
                            <div class="grid grid-cols-4 gap-3">
                                @foreach($this->accentOptions as $key => $option)
                                    <label class="relative cursor-pointer">
                                        <input type="radio" wire:model.live="accentColor" value="{{ $key }}" class="sr-only peer">
                                        <div class="flex flex-col items-center gap-2 p-3 rounded-[var(--radius-md)] border border-[var(--color-border)] hover:bg-[var(--color-bg-secondary)] peer-checked:border-[var(--accent-500)] peer-checked:bg-[var(--accent-50)] transition-colors">
                                            <span class="w-8 h-8 rounded-full shadow-sm" style="background: {{ $option['color'] }};"></span>
                                            <span class="text-xs text-[var(--color-text-secondary)]">{{ $option['label'] }}</span>
                                        </div>
                                        <div class="absolute top-2 left-2 w-4 h-4 rounded-full bg-[var(--accent-500)] text-white items-center justify-center hidden peer-checked:flex">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Font Size --}}
                        <div>
                            <label class="block text-sm font-medium mb-3 text-[var(--color-text-primary)]">{{ __('ui.font_size') }}</label>
                            <div class="flex rounded-[var(--radius-md)] border border-[var(--color-border)] p-1 bg-[var(--color-bg-secondary)]">
                                @foreach(['small' => 'صغير', 'medium' => 'متوسط', 'large' => 'كبير'] as $key => $label)
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" wire:model.live="fontSize" value="{{ $key }}" class="sr-only peer">
                                        <div class="text-center py-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-secondary)] peer-checked:bg-white peer-checked:text-[var(--accent-600)] peer-checked:shadow-sm transition-colors">
                                            {{ $label }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- UI Density --}}
                        <div>
                            <label class="block text-sm font-medium mb-3 text-[var(--color-text-primary)]">{{ __('ui.ui_density') }}</label>
                            <div class="flex rounded-[var(--radius-md)] border border-[var(--color-border)] p-1 bg-[var(--color-bg-secondary)]">
                                @foreach(['compact' => 'مضغوط', 'balanced' => 'متوازن', 'spacious' => 'واسع'] as $key => $label)
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" wire:model.live="uiDensity" value="{{ $key }}" class="sr-only peer">
                                        <div class="text-center py-2 rounded-[var(--radius-sm)] text-sm text-[var(--color-text-secondary)] peer-checked:bg-white peer-checked:text-[var(--accent-600)] peer-checked:shadow-sm transition-colors">
                                            {{ $label }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Sidebar State --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-[var(--color-text-primary)]">{{ __('ui.sidebar_collapsed') }}</p>
                                <p class="text-xs text-[var(--color-text-muted)]">تقليل عرض القائمة الجانبية</p>
                            </div>
                            <x-ui.switch wire:model.live="sidebarCollapsed" />
                        </div>

                        {{-- Reduced Motion --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-[var(--color-text-primary)]">{{ __('ui.reduced_motion') }}</p>
                                <p class="text-xs text-[var(--color-text-muted)]">إيقاف التأثيرات الحركية</p>
                            </div>
                            <x-ui.switch wire:model.live="reducedMotion" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 p-5 border-t border-[var(--color-border)]">
                        <x-ui.button variant="secondary" @click="open = false">إغلاق</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
