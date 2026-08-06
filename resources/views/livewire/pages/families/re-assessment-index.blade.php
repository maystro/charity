<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        title="إعادة التقييم"
        subtitle="الأسر المعتمدة — يمكنك بدء إعادة تقييم لأي أسرة"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="user-group" href="{{ route('families.index') }}" wire:navigate>
                الأسر المعتمدة
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث برقم الحالة أو الاسم أو الهاتف..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-[var(--color-text-muted)]">
                عدد الأسر: <span class="font-semibold text-[var(--color-text-primary)]">{{ $families->total() }}</span>
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">رقم الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">اسم الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المنطقة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">هاتف</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">آخر تقييم</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">عدد التقييمات</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">تاريخ آخر تقييم</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($families as $family)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors" wire:key="family-{{ $family->id }}">
                            <td class="px-4 py-3 font-mono text-xs text-[var(--color-text-secondary)]">{{ $family->case_number }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('families.show', $family) }}" wire:navigate class="font-medium text-[var(--color-text-primary)] hover:text-[var(--accent-500)] transition-colors">
                                    {{ $family->currentAssessment?->case_name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $family->currentAssessment?->community ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]" dir="ltr">{{ $family->currentAssessment?->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <x-ui.badge variant="neutral" size="sm">#{{ $family->currentAssessment?->round ?? 1 }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full text-xs font-bold {{ $family->assessments_count > 1 ? 'bg-[var(--accent-50)] text-[var(--accent-600)]' : 'bg-[var(--color-bg-secondary)] text-[var(--color-text-muted)]' }}">
                                    {{ $family->assessments_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)] text-xs">{{ $family->currentAssessment?->approved_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('families.assessment-history', $family) }}" wire:navigate class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="عرض تاريخ التقييمات">
                                        <x-heroicon-o-clock class="w-4.5 h-4.5" />
                                    </a>
                                    <button wire:click="startReAssessment({{ $family->id }})" wire:confirm="سيتم إنشاء تقييم جديد بنسخ بيانات آخر تقييم. متابعة؟" class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-success-500)] hover:bg-[var(--color-success-50)] transition-colors" title="إعادة تقييم">
                                        <x-heroicon-o-arrow-path class="w-4.5 h-4.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-[var(--color-text-muted)]">لا توجد أسر معتمدة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 border-t border-[var(--color-border)] pt-4">
            {{ $families->links() }}
        </div>
    </x-ui.card>
</div>
