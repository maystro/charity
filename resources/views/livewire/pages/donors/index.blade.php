<div class="space-y-6">
    <x-layout.page-header
        title="المتبرعون"
        subtitle="قائمة المتبرعين الأفراد والجهات مع إجمالي تبرعاتهم"
    />

    {{-- Filters --}}
    <x-ui.card padding>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    name="search"
                    placeholder="بحث بالاسم أو الهاتف أو المدينة..."
                    icon="magnifying-glass"
                    wire:model.live.debounce.300ms="search"
                />
            </div>
            <x-ui.select name="type" wire:model.live="typeFilter">
                <option value="">كل الأنواع</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="sort" wire:model.live="sort">
                <option value="most_donations">الأعلى تبرعاً</option>
                <option value="fewest_donations">الأقل تبرعاً</option>
                <option value="newest">الأحدث إضافة</option>
                <option value="oldest">الأقدم إضافة</option>
                <option value="name">حسب الاسم</option>
            </x-ui.select>
        </div>
    </x-ui.card>

    {{-- Table --}}
    <x-ui.card padding>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الاسم / الجهة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">النوع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">رقم الهاتف</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المدينة</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">عدد التبرعات</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">إجمالي التبرعات</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($donors as $donor)
                        <tr class="hover:bg-[var(--accent-50)]/50 transition-colors cursor-pointer" wire:click="$set('selected', {{ $donor->id }})">
                            <td class="px-4 py-3 text-[var(--color-text-primary)] font-medium">
                                <a href="{{ route('donors.show', $donor->id) }}" wire:navigate class="hover:text-[var(--accent-600)] transition-colors block" onclick="event.stopPropagation()">
                                    {{ $donor->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge variant="{{ $donor->type->variant() }}" size="sm">
                                    {{ $donor->type->label() }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-[var(--color-text-secondary)]" dir="ltr">
                                {{ $donor->phone ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $donor->city ?? '—' }}</td>
                            <td class="px-4 py-3 text-center text-[var(--color-text-secondary)]">
                                <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-[var(--color-bg-secondary)] text-xs font-medium">
                                    {{ $donor->donations_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-[var(--color-success-700)] font-semibold">
                                {{ number_format((float) $donor->donations_sum_amount, 2) }} <span class="text-xs text-[var(--color-text-muted)]">ج.م</span>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    class="text-xs px-2 py-1 rounded-md text-red-600 hover:bg-red-50 transition-colors"
                                    onclick="event.stopPropagation(); if(confirm('هل أنت متأكد من حذف المتبرع؟')) Livewire.find('{{ $this->getId() }}').call('delete', {{ $donor->id }})"
                                    aria-label="حذف المتبرع"
                                >
                                    حذف
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12">
                                <x-ui.empty-state
                                    icon="heart"
                                    title="لا يوجد متبرعون"
                                    description="اضغط على أي صف لعرض تفاصيل المتبرع وكل تبرعاته."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $donors->links() }}
    </x-ui.card>
</div>