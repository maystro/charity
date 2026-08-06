<div class="space-y-6">
    <x-layout.page-header
        title="الإصدارات والنشر"
        subtitle="إدارة إصدارات التطبيق وتوثيق التغييرات وعمليات النشر"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" href="{{ route('deployments.create') }}" wire:navigate>
                إصدار جديد
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filter Tabs --}}
    <x-ui.card padding>
        <div class="flex items-center gap-2 flex-wrap">
            @php
                $tabs = [
                    '' => ['label' => 'الكل', 'color' => 'var(--color-text-muted)'],
                    'draft' => ['label' => 'مسودة', 'color' => 'var(--color-warning-500)'],
                    'published' => ['label' => 'منشور', 'color' => 'var(--color-success-500)'],
                    'rolled_back' => ['label' => 'متراجع عنه', 'color' => 'var(--color-danger-500)'],
                ];
            @endphp
            @foreach($tabs as $key => $tab)
                <button
                    wire:click="setFilter('{{ $key }}')"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-colors {{ $filter === $key ? 'text-white' : 'hover:bg-black/5 text-[var(--color-text-secondary)]' }}"
                    style="{{ $filter === $key ? 'background: ' . $tab['color'] . ';' : '' }}"
                >
                    <span>{{ $tab['label'] }}</span>
                </button>
            @endforeach
        </div>
    </x-ui.card>

    {{-- Releases List --}}
    <div class="space-y-4">
        @forelse($releases as $release)
            <x-ui.card padding hover class="hover:border-[var(--accent-500)]/30" wire:key="release-{{ $release->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-bold text-[var(--color-text-primary)] font-mono">{{ $release->version }}</h3>
                            @php
                                $statusConfig = [
                                    'draft' => ['variant' => 'warning', 'label' => 'مسودة'],
                                    'published' => ['variant' => 'success', 'label' => 'منشور'],
                                    'rolled_back' => ['variant' => 'danger', 'label' => 'متراجع عنه'],
                                ];
                                $cfg = $statusConfig[$release->status->value] ?? ['variant' => 'neutral', 'label' => $release->status->value];
                            @endphp
                            <x-ui.badge :variant="$cfg['variant']" dot>{{ $cfg['label'] }}</x-ui.badge>
                        </div>
                        <p class="mt-1 text-base font-medium text-[var(--color-text-primary)]">{{ $release->title }}</p>
                        @if($release->description)
                            <p class="mt-1 text-sm text-[var(--color-text-secondary)] leading-relaxed line-clamp-2">{{ $release->description }}</p>
                        @endif
                        <div class="mt-3 flex items-center gap-4 text-xs text-[var(--color-text-muted)]">
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-document-text class="w-3.5 h-3.5" />
                                {{ $release->changes_count }} تغيير
                            </span>
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-rocket-launch class="w-3.5 h-3.5" />
                                {{ $release->deployments_count }} عملية نشر
                            </span>
                            @if($release->released_at)
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                                    {{ $release->released_at->format('Y-m-d') }}
                                </span>
                            @endif
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-user class="w-3.5 h-3.5" />
                                {{ $release->creator?->name }}
                            </span>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <x-ui.button variant="secondary" size="sm" href="{{ route('deployments.show', $release) }}" wire:navigate>
                            عرض التفاصيل
                        </x-ui.button>
                        @if($release->isDraft())
                            <x-ui.button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="deleteRelease({{ $release->id }})"
                                wire:confirm="هل أنت متأكد من حذف هذا الإصدار؟"
                            />
                        @endif
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card padding>
                <x-ui.empty-state
                    icon="rocket-launch"
                    title="لا توجد إصدارات"
                    description="لم يتم إنشاء أي إصدارات بعد. أنشئ أول إصدار لتوثيق تغييرات التطبيق."
                >
                    <x-slot:actions>
                        <x-ui.button variant="primary" icon="plus" href="{{ route('deployments.create') }}" wire:navigate>
                            إنشاء إصدار
                        </x-ui.button>
                    </x-slot:actions>
                </x-ui.empty-state>
            </x-ui.card>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($releases->hasPages())
        <div class="mt-4">
            {{ $releases->links() }}
        </div>
    @endif
</div>
