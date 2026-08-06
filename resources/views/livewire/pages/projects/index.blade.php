<div class="space-y-6">
    <x-layout.page-header
        title="المشروعات"
        subtitle="إدارة مشروعات المنشأة الخيرية ومراحلها"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" href="{{ route('projects.create') }}" wire:navigate>
                إضافة مشروع
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث باسم المشروع أو المحافظة..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="status" wire:model.live="statusFilter">
                <option value="">كل الحالات</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="sort" wire:model.live="sort">
                <option value="newest">الأحدث</option>
                <option value="oldest">الأقدم</option>
                <option value="budget_desc">الميزانية (الأعلى)</option>
                <option value="budget_asc">الميزانية (الأقل)</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        @if(session('notify'))
            <div class="mb-4">{{ session('notify') }}</div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">اسم المشروع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المحافظة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المراحل</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الميزانية</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">التبرعات</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المدة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($projects as $project)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                            <td class="px-4 py-3 text-[var(--color-text-primary)] font-medium">
                                {{ $project->name }}
                                @if($project->description)
                                    <div class="text-xs text-[var(--color-text-muted)] mt-0.5 line-clamp-1">{{ $project->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $project->governorate ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $project->status->variant() }}" size="sm" dot>
                                    {{ $project->status->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $project->phases->count() }}</td>
                            <td class="px-4 py-3 font-mono text-[var(--color-text-primary)] font-medium">
                                {{ number_format((float) $project->total_budget, 2) }} <span class="text-xs text-[var(--color-text-muted)]">ج.م</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-[var(--color-text-secondary)]">
                                {{ number_format($project->total_donations, 2) }} <span class="text-xs text-[var(--color-text-muted)]">ج.م</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-[var(--color-text-muted)]">
                                @if($project->start_date || $project->end_date)
                                    {{ $project->start_date?->format('Y/m/d') ?? '—' }}
                                    <span class="text-[var(--color-text-muted)]">→</span>
                                    {{ $project->end_date?->format('Y/m/d') ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    class="text-xs px-2 py-1 rounded-md text-red-600 hover:bg-red-50 transition-colors"
                                    onclick="if(confirm('هل أنت متأكد من حذف المشروع؟')) Livewire.find('{{ $this->getId() }}').call('delete', {{ $project->id }})"
                                    aria-label="حذف المشروع"
                                >
                                    حذف
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12">
                                <x-ui.empty-state
                                    icon="briefcase"
                                    title="لا توجد مشاريع"
                                    description="ابدأ بإضافة أول مشروع — سيظهر هنا مع مراحله وميزانيته."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $projects->links() }}
    </x-ui.card>
</div>