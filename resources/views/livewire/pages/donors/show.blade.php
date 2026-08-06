<div class="space-y-6">
    <x-layout.page-header
        title="تفاصيل المتبرع"
        :breadcrumbs="[['label' => 'المتبرعون', 'route' => 'donors.index'], ['label' => $donor->name]]"
    />

    {{-- بطاقة معلومات المتبرع --}}
    <x-ui.card padding>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-[var(--accent-100)] text-[var(--accent-700)] text-xl font-bold">
                    {{ mb_substr($donor->name, 0, 1) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-[var(--color-text-primary)]">{{ $donor->name }}</h2>
                        <x-ui.badge variant="{{ $donor->type->variant() }}" size="sm">{{ $donor->type->label() }}</x-ui.badge>
                    </div>
                    <div class="flex flex-wrap gap-4 mt-1 text-sm text-[var(--color-text-muted)]">
                        @if($donor->phone)
                            <span dir="ltr" class="font-mono">📞 {{ $donor->phone }}</span>
                        @endif
                        @if($donor->email)
                            <span>✉️ {{ $donor->email }}</span>
                        @endif
                        @if($donor->city)
                            <span>📍 {{ $donor->city }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 md:gap-4">
                <div class="rounded-[var(--radius-md)] bg-[var(--accent-50)] border border-[var(--accent-200)] px-4 py-3 text-center">
                    <div class="text-xs text-[var(--color-text-muted)]">عدد التبرعات</div>
                    <div class="text-xl font-bold text-[var(--accent-700)] mt-0.5">{{ $countDonations }}</div>
                </div>
                <div class="rounded-[var(--radius-md)] bg-[var(--color-success-50)] border border-[var(--color-success-200)] px-4 py-3 text-center">
                    <div class="text-xs text-[var(--color-text-muted)]">إجمالي التبرعات</div>
                    <div class="text-xl font-bold text-[var(--color-success-700)] mt-0.5 font-mono">{{ number_format($totalDonations, 2) }}</div>
                </div>
            </div>
        </div>

        @if($donor->notes)
            <div class="mt-4 pt-4 border-t border-[var(--color-border)]">
                <div class="text-xs font-semibold text-[var(--color-text-muted)] uppercase mb-1">ملاحظات</div>
                <p class="text-sm text-[var(--color-text-secondary)]">{{ $donor->notes }}</p>
            </div>
        @endif
    </x-ui.card>

    {{-- جدول التبرعات --}}
    <x-ui.card padding>
        <div class="mb-4">
            <h3 class="text-base font-semibold text-[var(--color-text-primary)]">التبرعات التي قام بها</h3>
            <p class="text-xs text-[var(--color-text-muted)] mt-0.5">سجل كامل لكل التبرعات المرتبطة بهذا المتبرع.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">#</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">التاريخ</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المبلغ</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">وسيلة التبرع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">النوع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">المشروع</th>
                        <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">ملاحظات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--color-border)]">
                    @forelse($donor->donations as $i => $donation)
                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                            <td class="px-4 py-3 text-[var(--color-text-muted)] text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">{{ $donation->donated_at?->format('Y/m/d') ?? '—' }}</td>
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
                                <x-ui.badge variant="{{ $donation->type->variant() }}" size="sm">{{ $donation->type->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-[var(--color-text-secondary)]">
                                @if($donation->project_id)
                                    {{ $donation->project?->name ?? '—' }}
                                @else
                                    <span class="text-[var(--color-text-muted)]">تبرع عام</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-[var(--color-text-muted)] max-w-xs truncate">
                                {{ $donation->notes ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10">
                                <x-ui.empty-state
                                    icon="clipboard-document-list"
                                    title="لا توجد تبرعات"
                                    description="لم يقم هذا المتبرع بأي تبرعات حتى الآن."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($donor->donations->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-[var(--accent-200)] bg-[var(--accent-50)]/50">
                            <td colspan="2" class="px-4 py-3 text-sm font-semibold text-[var(--accent-700)]">الإجمالي</td>
                            <td class="px-4 py-3 font-mono text-base font-bold text-[var(--accent-700)]">
                                {{ number_format($totalDonations, 2) }} <span class="text-xs">ج.م</span>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-ui.card>

    <div class="flex justify-end">
        <x-ui.button variant="ghost" href="{{ route('donors.index') }}" wire:navigate icon="arrow-right">عودة لقائمة المتبرعين</x-ui.button>
    </div>
</div>