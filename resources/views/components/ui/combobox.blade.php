@props([
    'label' => null,
    'name' => null,
    'options' => [],        // [value => label] OR [['value'=>..,'label'=>..,'meta'=>..], ...]
    'value' => null,        // القيمة المختارة حالياً (initial)
    'selected' => null,     // البديل لـ value
    'placeholder' => 'اختر...',
    'searchPlaceholder' => 'بحث...',
    'emptyText' => 'لا توجد نتائج مطابقة',
    'hint' => null,
    'error' => null,
    'required' => false,
    'disabled' => false,
    'size' => 'md',
    'align' => 'right',     // اتجاه القائمة المنسدلة (RTL = right)
])

@php
    $id = $attributes->get('id', $name ?? 'combobox-' . substr(md5(random_bytes(8)), 0, 8));

    $sizes = [
        'sm' => 'text-sm py-2 px-3',
        'md' => 'text-sm py-2.5 px-4',
        'lg' => 'text-base py-3 px-5',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // تطبيع الخيارات إلى مصفوفة موحدة [{value, label, meta}]
    $normalized = [];
    $isAssoc = count($options) && array_keys($options) !== range(0, count($options) - 1);
    if ($isAssoc) {
        foreach ($options as $v => $l) {
            $normalized[] = ['value' => is_numeric($v) ? (int) $v : (string) $v, 'label' => (string) $l, 'meta' => null];
        }
    } else {
        foreach ($options as $opt) {
            if (is_array($opt) && isset($opt['value'], $opt['label'])) {
                $normalized[] = ['value' => is_numeric($opt['value']) ? (int) $opt['value'] : (string) $opt['value'], 'label' => (string) $opt['label'], 'meta' => $opt['meta'] ?? null];
            }
        }
    }
    // القيمة المختارة حالياً
    $currentValue = $selected ?? $value ?? old($name);
    $currentLabel = null;
    foreach ($normalized as $opt) {
        if ((string) $opt['value'] === (string) $currentValue) {
            $currentLabel = $opt['label'];
            $currentValue = $opt['value'];
            break;
        }
    }

    $triggerClasses = 'flex items-center w-full bg-white border rounded-[var(--radius-md)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] shadow-xs focus:outline-none focus:ring-4 focus:ring-[var(--accent-500)]/15 focus:border-[var(--accent-500)] transition-all duration-[var(--motion-fast)] cursor-pointer text-start';
    $triggerClasses .= ' ' . $sizeClass;
    if ($error) {
        $triggerClasses .= ' border-[var(--color-danger-500)] focus:ring-[var(--color-danger-500)]/15 focus:border-[var(--color-danger-500)]';
    } else {
        $triggerClasses .= ' border-[var(--color-border)] hover:border-[var(--accent-500)]';
    }

    $alignClass = $align === 'left' ? 'left-0' : 'right-0';
    $isAr = app()->isLocale('ar');
    $searchIconPadding = $isAr ? 'pr-9 pl-3' : 'pl-9 pr-3';
    $chevronMargin = $isAr ? 'mr-auto' : 'ml-auto';

    // اكتشاف wire:model لاستخدامه في المزامنة
    $wireVar = null;
    $wireModelAttr = null;
    foreach (['wire:model.live', 'wire:model.lazy', 'wire:model.defer', 'wire:model.blur', 'wire:model'] as $wma) {
        if ($attributes->has($wma)) {
            $wireModelAttr = $wma;
            $wireVar = $attributes->get($wma);
            break;
        }
    }
    $hasWire = $wireVar !== null;
    if (! $wireVar && $name) {
        $wireVar = $name;
    }
    $isLive = in_array($wireModelAttr, ['wire:model', 'wire:model.live']);
@endphp

<div
    x-data="{
        isopen: false,
        search: '',
        selectedValue: {{ $currentValue !== null ? '\'' . e($currentValue) . '\'' : 'null' }},
        selectedLabel: {{ $currentLabel !== null ? json_encode($currentLabel, JSON_UNESCAPED_UNICODE) : 'null' }},
        activeIndex: -1,
        options: {{ json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }},
        disabled: {{ $disabled ? 'true' : 'false' }},

        get filteredOptions() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.options;
            return this.options.filter(o =>
                String(o.label).toLowerCase().includes(q) || String(o.value).includes(q)
            );
        },

        toggle() {
            if (this.disabled) return;
            this.isopen ? this.close() : this.open();
        },

        open() {
            this.isopen = true;
            this.search = '';
            this.activeIndex = -1;
            this.$nextTick(() => this.$refs.search?.focus());
        },

        close() {
            this.isopen = false;
            this.$nextTick(() => this.$refs.trigger?.focus());
        },

        focusOption(idx) {
            if (this.filteredOptions.length === 0) return;
            this.activeIndex = Math.max(0, Math.min(idx, this.filteredOptions.length - 1));
            this.$nextTick(() => {
                const el = this.$refs.options?.[this.activeIndex];
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        },

        selectActive() {
            if (this.activeIndex >= 0 && this.activeIndex < this.filteredOptions.length) {
                this.select(this.filteredOptions[this.activeIndex]);
            }
        },

        select(opt) {
            const wireVar = '{{ e($wireVar) }}';
            const newVal = opt.value;
            this.selectedValue = String(newVal);
            this.selectedLabel = opt.label;
            @if($hasWire)
                if (this.$wire && wireVar) {
                    this.$wire.set(wireVar, newVal, {{ $isLive ? 'true' : 'false' }});
                }
            @endif
            this.close();
        },

        clear() {
            this.selectedValue = null;
            this.selectedLabel = null;
            const wireVar = '{{ e($wireVar) }}';
            @if($hasWire)
                if (this.$wire && wireVar) {
                    this.$wire.set(wireVar, null, true);
                }
            @endif
            this.$nextTick(() => this.$refs.trigger?.focus());
        },
    }"
    @keydown.escape.window="if (isopen) close()"
    class="relative"
>
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">
            {{ $label }}
            @if($required)<span class="text-[var(--color-danger-500)]">*</span>@endif
        </label>
    @endif

    {{-- Hidden input يحمل القيمة المختارة لإرسالها مع النموذج --}}
    <input
        type="hidden"
        name="{{ $name }}"
        x-bind:value="selectedValue === null ? '' : selectedValue"
        @if($required) required @endif
    />

    <div class="relative">
        <button
            type="button"
            id="{{ $id }}"
            x-ref="trigger"
            @click="toggle()"
            @keydown.arrow-down.prevent="if (!isopen) open(); focusOption(0)"
            @keydown.arrow-up.prevent="if (!isopen) open(); focusOption(filteredOptions.length - 1)"
            @keydown.enter.prevent="if (isopen) selectActive()"
            @keydown.backspace.prevent="if (selectedValue !== null) clear()"
            x-bind:disabled="disabled"
            x-bind:aria-expanded="isopen.toString()"
            aria-haspopup="listbox"
            role="combobox"
            aria-autocomplete="list"
            x-bind:class="'{{ e($triggerClasses) }} ' + (disabled ? 'opacity-50 cursor-not-allowed' : '')"
        >
            <span class="flex-1 truncate text-start" x-show="selectedValue !== null && selectedLabel !== null" x-text="selectedLabel"></span>
            <span class="flex-1 truncate text-start text-[var(--color-text-muted)]" x-show="selectedValue === null || selectedLabel === null">{{ $placeholder }}</span>
            @if(! $required)
                <span
                    x-show="selectedValue !== null && !disabled"
                    @click.stop="clear()"
                    class="p-0.5 mx-1 rounded-full hover:bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors shrink-0"
                    role="button"
                    tabindex="-1"
                    aria-label="مسح الاختيار"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </span>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ $chevronMargin }} text-[var(--color-text-muted)] transition-transform duration-200" :class="{ 'rotate-180': isopen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div
            x-show="isopen"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.outside="close()"
            class="absolute z-[var(--z-tooltip)] mt-1 {{ $alignClass }} w-full max-h-80 overflow-auto rounded-[var(--radius-md)] bg-white shadow-xl border border-[var(--color-border)] py-2 focus:outline-none"
            role="listbox"
            aria-labelledby="{{ $id }}"
        >
            <div class="px-3 pb-2 sticky top-0 bg-white z-10">
                <div class="relative">
                    <span class="absolute inset-y-0 {{ $isAr ? 'right' : 'left' }}-0 flex items-center {{ $isAr ? 'pr-3' : 'pl-3' }} pointer-events-none text-[var(--color-text-muted)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        x-ref="search"
                        x-model="search"
                        @keydown.arrow-down.prevent="focusOption(activeIndex === -1 ? 0 : activeIndex + 1)"
                        @keydown.arrow-up.prevent="focusOption(activeIndex === -1 ? filteredOptions.length - 1 : activeIndex - 1)"
                        @keydown.enter.prevent="selectActive()"
                        @keydown.escape.prevent="close()"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full {{ $searchIconPadding }} h-9 text-sm bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-[var(--radius-sm)] focus:outline-none focus:ring-2 focus:ring-[var(--accent-500)]/30 focus:border-[var(--accent-500)]"
                        autocomplete="off"
                        role="searchbox"
                    />
                </div>
            </div>

            <ul role="presentation" class="py-1">
                <template x-for="(opt, idx) in filteredOptions" :key="opt.value">
                    <li>
                        <button
                            type="button"
                            x-ref="options"
                            @click="select(opt)"
                            @mousemove="activeIndex = idx"
                            @keydown.enter.prevent="select(opt)"
                            x-bind:class="activeIndex === idx ? 'bg-[var(--accent-50)] text-[var(--accent-700)]' : 'text-[var(--color-text-primary)]'"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm hover:bg-[var(--accent-50)] hover:text-[var(--accent-700)] transition-colors text-start"
                            role="option"
                            x-bind:aria-selected="String(selectedValue) === String(opt.value) ? 'true' : 'false'"
                        >
                            <span class="w-4 h-4 shrink-0 flex items-center justify-center text-[var(--accent-500)]" :class="String(selectedValue) === String(opt.value) ? '' : 'opacity-0'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <span class="flex-1 truncate text-start" x-text="opt.label"></span>
                            <template x-if="opt.meta">
                                <span class="text-xs text-[var(--color-text-muted)] shrink-0" x-text="opt.meta"></span>
                            </template>
                        </button>
                    </li>
                </template>
                <li x-show="filteredOptions.length === 0" class="px-3 py-6 text-sm text-[var(--color-text-muted)] text-center" role="presentation">
                    {{ $emptyText }}
                </li>
            </ul>
        </div>
    </div>

    @if($hint && !$error)
        <p class="text-xs text-[var(--color-text-muted)] mt-1.5">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs text-[var(--color-danger-500)] mt-1.5">{{ $error }}</p>
    @endif
</div>