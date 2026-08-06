<div class="space-y-6">
    <x-layout.page-header
        title="المسارات المسموحة"
        subtitle="حدد الفولدرات والملفات التي يُسمح لنظام النشر بالتعامل معها — تُستخدم في اكتشاف التغييرات وتنفيذ عمليات النشر."
        :breadcrumbs="[
            ['label' => 'الإصدارات والنشر', 'route' => 'deployments.index'],
            ['label' => 'المسارات المسموحة'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="check" wire:click="save" :loading="$saving">
                حفظ المسارات
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.alert variant="info" :dismissible="false">
        القائمة تعرض الفولدرات والملفات الموجودة على الجذر مباشرة. تحديد أي مجلد يعني السماح بكل ما بداخله تلقائيًا دون عرضه. إذا لم تحفظ أي مسار، يعمل النظام بمساراته الافتراضية من
        <code dir="ltr">config/deployment.php</code>.
    </x-ui.alert>

    <x-ui.card padding>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">
                قائمة الجذر
                <span class="ms-2 text-sm font-normal text-[var(--color-text-muted)]">
                    ({{ $this->selectedCount }} مُحدد — {{ count($this->entries) }} عنصر)
                </span>
            </h2>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.input
                    type="search"
                    wire:model.live.debounce.200ms="search"
                    placeholder="ابحث عن مسار…"
                    icon="magnifying-glass"
                    size="sm"
                    class="w-56"
                />
                <x-ui.button variant="secondary" size="sm" wire:click="selectAll">
                    تحديد الكل
                </x-ui.button>
                <x-ui.button variant="ghost" size="sm" wire:click="clearAll">
                    إلغاء الكل
                </x-ui.button>
            </div>
        </div>

        @php
            $visible = $this->entries;
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $visible = array_values(array_filter(
                    $this->entries,
                    fn (array $entry): bool => str_contains(mb_strtolower($entry['path']), $needle)
                ));
            }
        @endphp

        @if ($visible === [])
            <x-ui.empty-state
                icon="folder"
                title="لا توجد نتائج"
                description="جرّب كلمة بحث أخرى، أو ألغِ البحث لعرض كل الفولدرات والملفات."
            />
        @else
            <div class="mt-4 max-h-[60vh] overflow-y-auto rounded-lg border border-[var(--color-border)]">
                <ul class="divide-y divide-[var(--color-border)]">
                    @foreach ($visible as $entry)
                        <li
                            wire:key="entry-{{ $entry['path'] }}"
                            class="flex items-center gap-3 px-3 py-2 hover:bg-[var(--color-bg-secondary)]/60"
                        >
                            <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2">
                                <input
                                    type="checkbox"
                                    wire:model.live="selected"
                                    value="{{ $entry['path'] }}"
                                    class="h-4 w-4 shrink-0 rounded border-[var(--color-border)] accent-[var(--accent-500)]"
                                />
                                <span class="flex items-center gap-1.5 text-[var(--color-text-muted)]">
                                    <x-dynamic-component
                                        :component="'heroicon-s-' . ($entry['type'] === 'dir' ? 'folder' : 'document-text')"
                                        class="w-4 h-4 {{ $entry['type'] === 'dir' ? 'text-[var(--accent-500)]' : 'text-[var(--color-text-muted)]' }}"
                                    />
                                </span>
                                <span
                                    dir="ltr"
                                    class="truncate font-mono text-xs text-[var(--color-text-primary)]"
                                    title="{{ $entry['path'] }}"
                                >
                                    {{ $entry['label'] }}
                                </span>
                            </label>
                            <span class="shrink-0 font-mono text-[10px] text-[var(--color-text-muted)]/70" dir="ltr">
                                {{ $entry['type'] === 'dir' ? 'مجلد (يشمل ما بداخله)' : 'ملف' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4 flex items-center justify-between gap-3 border-t border-[var(--color-border)] pt-4">
            <p class="text-sm text-[var(--color-text-muted)]">
                سيتم حفظ {{ $this->selectedCount }} مسار مسموح — أي ملف خارج هذه القائمة يُتجاهل في الاستيراد والنشر، واختيار مجلد يغطي كل ما بداخله.
            </p>
            <x-ui.button variant="primary" icon="check" wire:click="save" :loading="$saving">
                حفظ المسارات
            </x-ui.button>
        </div>
    </x-ui.card>
</div>
