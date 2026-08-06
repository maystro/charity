<div class="space-y-6">
    <x-layout.page-header
        title="التبرعات"
        subtitle="سجل التبرعات الواردة للمنشأة من المتبرعين الأفراد والجهات"
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" href="{{ route('donations.create') }}" wire:navigate>
                إضافة تبرع
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- إجمالي التبرعات (بعد الفلاتر) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-ui.stat
            label="إجمالي التبرعات (الفلترة الحالية)"
            number="{{ number_format($totalAmount, 2) }} ج.م"
            icon="currency-dollar"
            variant="success"
        />
    </div>

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث باسم المتبرع أو المشروع..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="method" wire:model.live="methodFilter">
                <option value="">كل الوسائل</option>
                @foreach($methodOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="type" wire:model.live="typeFilter">
                <option value="">كل الأنواع</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="sort" wire:model.live="sort">
                <option value="newest">الأحدث</option>
                <option value="oldest">الأقدم</option>
                <option value="amount_desc">المبلغ (الأعلى)</option>
                <option value="amount_asc">المبلغ (الأقل)</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المتبرع / الجهة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">النوع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">قيمة التبرع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">وسيلة التبرع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">نوع التبرع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المشروع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">تاريخ التبرع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($donations as $donation)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                            <td class="px-4 py-3 text-[var(--color-text-primary)] font-medium">
                                @if($donation->donor_id)
                                    <a href="{{ route('donors.show', $donation->donor_id) }}" wire:navigate class="hover:text-[var(--accent-600)] transition-colors">
                                        {{ $donation->donor_name }}
                                    </a>
                                @else
                                    {{ $donation->donor_name }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $donation->donor_type->variant() }}" size="sm">
                                    {{ $donation->donor_type->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 font-mono text-[var(--color-text-primary)] font-semibold">
                                {{ number_format((float) $donation->amount, 2) }} <span class="text-xs text-[var(--color-text-muted)]">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 text-[var(--color-text-secondary)]">
                                    <x-dynamic-component :component="'heroicon-s-' . $donation->method->icon()" class="w-4 h-4 text-[var(--color-text-muted)]" />
                                    {{ $donation->method->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $donation->type->variant() }}" size="sm">
                                    {{ $donation->type->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">
                                @if($donation->project_id)
                                    {{ $donation->project?->name ?? '—' }}
                                @else
                                    <span class="text-[var(--color-text-muted)]">تبرع عام</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-[var(--color-text-muted)]">
                                {{ $donation->donated_at?->format('Y/m/d') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    class="text-xs px-2 py-1 rounded-md text-red-600 hover:bg-red-50 transition-colors"
                                    onclick="if(confirm('هل أنت متأكد من حذف هذا التبرع؟')) Livewire.find('{{ $this->getId() }}').call('delete', {{ $donation->id }})"
                                    aria-label="حذف التبرع"
                                >
                                    حذف
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12">
                                <x-ui.empty-state
                                    icon="currency-dollar"
                                    title="لا توجد تبرعات"
                                    description="ستظهر التبرعات هنا عند إضافتها من المتبرعين."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $donations->links() }}
    </x-ui.card>
</div>
