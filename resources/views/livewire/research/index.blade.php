<div class="space-y-6">
    <x-layout.page-header title="البحوث الميدانية" subtitle="متابعة البحوث الاجتماعية المرتبطة بالأسر.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" href="{{ route('research.create') }}" wire:navigate>إنشاء بحث</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card padding>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input label="بحث" name="search" wire:model.live.debounce.300ms="search" placeholder="رقم البحث أو اسم الأسرة..." />
            <x-ui.select label="الحالة" name="status" wire:model.live="status" :options="['' => 'كل الحالات', 'draft' => 'مسودة', 'approved' => 'معتمد', 'expired' => 'منتهي']" />
        </div>
    </x-ui.card>

    <x-ui.card padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-[var(--color-border)]">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">رقم البحث</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الأسرة</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">النوع</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">تاريخ الإجراء</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الانتهاء</th>
                        <th class="px-4 py-3 text-start text-xs text-[var(--color-text-muted)]">الحالة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($researches as $research)
                        <tr wire:key="research-{{ $research->id }}">
                            <td class="px-4 py-3 font-mono text-xs">{{ $research->research_number }}</td>
                            <td class="px-4 py-3 font-medium">{{ $research->family?->case_name }}<br><span class="text-xs text-[var(--color-text-muted)]">{{ $research->family?->case_number }}</span></td>
                            <td class="px-4 py-3">{{ $research->research_type }}</td>
                            <td class="px-4 py-3">{{ $research->conducted_at?->format('Y/m/d') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $research->expiry_date?->format('Y/m/d') ?? '—' }}</td>
                            <td class="px-4 py-3"><x-ui.badge variant="{{ $research->status === 'approved' ? 'success' : ($research->status === 'expired' ? 'danger' : 'neutral') }}">{{ ['draft' => 'مسودة', 'approved' => 'معتمد', 'expired' => 'منتهي'][$research->status] ?? $research->status }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-[var(--color-text-muted)]">لا توجد أبحاث مسجلة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $researches->links() }}</div>
    </x-ui.card>
</div>
