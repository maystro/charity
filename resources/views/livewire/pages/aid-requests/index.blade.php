
{{-- قالب مشترك (BaseAidRequestsIndex + المُنحدرات) يدعم المدير والمندوب بشكل موحد --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        :title="$title ?? 'طلبات المساعدة'"
        subtitle="عرض وإدارة طلبات المساعدة المقدمة"
    >
        <x-slot:actions>
            @if($showCreate ?? true)
                <x-ui.button variant="primary" icon="plus" href="{{ route($this->createRouteName()) }}" wire:navigate>
                    إضافة طلب
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    {{-- التبويبات --}}
    <div class="border-b border-[var(--color-border)]">
        <nav class="flex gap-1 -mb-px overflow-x-auto" aria-label="Tabs">
            <button
                wire:click="$set('tab', 'under_review')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'under_review',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'under_review',
                ])
                role="tab"
            >
                طلبات تحت المراجعة
                <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--accent-500)]/10 text-[var(--accent-600)]">
                    {{ $this->underReviewCount }}
                </span>
            </button>
            <button
                wire:click="$set('tab', 'approved')"
                @class([
                    'text-sm px-4 py-2.5 border-b-2 transition-colors duration-[var(--motion-fast)] whitespace-nowrap',
                    'border-[var(--accent-500)] text-[var(--accent-700)] font-medium' => $tab === 'approved',
                    'border-transparent text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border)]' => $tab !== 'approved',
                ])
                role="tab"
            >
                طلبات معتمدة
                <span class="ms-1.5 text-xs px-1.5 py-0.5 rounded-full bg-[var(--color-success-500)]/10 text-[var(--color-success-600)]">
                    {{ $this->approvedCount }}
                </span>
            </button>
        </nav>
    </div>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث برقم الطلب أو العنوان أو الأسرة..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="priority" wire:model.live="priority">
                <option value="">كل الأولويات</option>
                <option value="عادية">عادية</option>
                <option value="متوسطة">متوسطة</option>
                <option value="مرتفعة">مرتفعة</option>
                <option value="عاجلة جداً">عاجلة جداً</option>
            </x-ui.select>
            <x-ui.select name="sort" wire:model.live="sort">
                <option value="newest">الأحدث</option>
                <option value="oldest">الأقدم</option>
                <option value="priority">الأعلى أولوية</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">رقم الطلب</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الأسرة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">العنوان</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">النوع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الأولوية</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الحالة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الإجمالي</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">التاريخ</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($this->requests as $request)
                        <tr
                            wire:key="aid-request-{{ $request->id }}"
                            @click="Livewire.navigate('{{ route('aid-requests.show', $request) }}')"
                            class="cursor-pointer hover:bg-[var(--color-bg-secondary)]/50 transition-colors"
                            title="فتح الطلب"
                        >
                            <td class="px-4 py-3 font-mono text-xs text-[var(--color-text-secondary)]">{{ $request->request_number }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-primary)] font-medium">{{ $request->family?->case_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-primary)]">{{ $request->title }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $request->request_type }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$this->priorityVariant($request->priority)" size="sm">
                                    {{ $request->priority }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$this->statusVariant($request->status)" size="sm" dot>
                                    {{ $this->statusLabel($request->status) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)] font-medium">{{ number_format((float) $request->total_estimated_amount, 2) }} ج.م</td>
                            <td class="px-4 py-3 text-[var(--color-text-muted)] text-xs">{{ $request->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('aid-requests.show', $request) }}" wire:navigate @click.stop class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="عرض التفاصيل">
                                        <x-heroicon-o-eye class="w-4.5 h-4.5" />
                                    </a>
                                    @if($tab === 'under_review' && in_array($request->status, ['draft', 'needs_completion']))
                                        <a href="{{ route('aid-requests.edit', $request) }}" wire:navigate @click.stop class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--accent-500)] hover:bg-[var(--color-bg-secondary)] transition-colors" title="تعديل">
                                            <x-heroicon-o-pencil class="w-4.5 h-4.5" />
                                        </a>
                                    @endif
                                    @if($showDelete)
                                        <button wire:click="delete({{ $request->id }})" @click.stop wire:confirm="هل أنت متأكد من حذف هذا الطلب؟" class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors" title="حذف">
                                            <x-heroicon-o-trash class="w-4.5 h-4.5" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-[var(--color-text-muted)]">
                                @if($tab === 'approved')
                                    لا توجد طلبات معتمدة
                                @else
                                    لا توجد طلبات تحت المراجعة
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 border-t border-[var(--color-border)] pt-4">
            {{ $this->requests->links() }}
        </div>
    </x-ui.card>
</div>
