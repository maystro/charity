<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════ Page Header ═══ --}}
    <x-layout.page-header
        title="{{ $family->case_name }}"
        subtitle="رقم الحالة: {{ $family->case_number }}"
        :breadcrumbs="[
            ['label' => 'الأسر والحالات', 'route' => 'families.index'],
            ['label' => $family->case_number],
        ]"
    >
        <x-slot:actions>
            @if($family->isEditable)
                <x-ui.button
                    variant="primary"
                    icon="pencil"
                    href="{{ route('families.edit', $family) }}"
                    wire:navigate
                >
                    تعديل
                </x-ui.button>
            @endif
            <x-ui.button
                variant="outline"
                icon="hand-raised"
                href="{{ route('aid-requests.create') }}"
                wire:navigate
            >
                إنشاء طلب مساعدة
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- ═══════════════════════════════════════════════════════ Status Bar ═════ --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">رقم الحالة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->case_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">نوع الحالة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->case_type }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">المنطقة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->community ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">تاريخ الإنشاء</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->created_at->format('Y/m/d') }}</p>
                </div>
            </div>
            @php
                $status = $family->status instanceof \BackedEnum ? $family->status : \App\Enums\FamilyStatus::from($family->status);
            @endphp
            <x-ui.badge :variant="$status->variant()" size="md" dot>{{ $status->label() }}</x-ui.badge>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Tabs ══════════ --}}
    <x-ui.tabs
        :tabs="[
            'basic'    => 'البيانات الأساسية',
            'members'  => 'أفراد الأسرة',
            'income'   => 'حالة الدخل',
            'resources' => 'موارد الأسرة',
            'burdens'  => 'الأعباء',
            'housing'  => 'حالة السكن',
        ]"
        active="basic"
    >
        {{-- ── Tab: Basic ── --}}
        <x-ui.tab-panel name="basic">
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">البيانات الأساسية</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">رقم الحالة</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $family->case_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">اسم الحالة</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $family->case_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">نوع الحالة</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $family->case_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">نوع الأسرة</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $family->family_type ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">المنطقة</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]">{{ $family->community ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">الهاتف</dt>
                        <dd class="text-sm text-[var(--color-text-primary)]" dir="ltr">{{ $family->phone ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">العنوان التفصيلي</dt>
                        <dd class="text-sm text-[var(--color-text-primary)] leading-relaxed">{{ $family->detailed_address ?? '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </x-ui.tab-panel>

        {{-- ── Tab: Members ── --}}
        <x-ui.tab-panel name="members">
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">
                    أفراد الأسرة
                    <span class="text-xs font-normal text-[var(--color-text-muted)] mr-2">
                        ({{ $family->members->count() }} فرد)
                    </span>
                </h2>
                @if($family->members->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)]">
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">#</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">الاسم</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">الرقم القومي</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">صلة القرابة</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">المهنة</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">الدخل</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--color-border)]">
                                @foreach($family->members as $i => $member)
                                    <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                                        <td class="px-3 py-2.5 text-[var(--color-text-muted)]">{{ $i + 1 }}</td>
                                        <td class="px-3 py-2.5 font-medium text-[var(--color-text-primary)]">{{ $member->name }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-secondary)]" dir="ltr">{{ $member->national_id ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $member->relationship ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $member->occupation ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $member->income ? number_format($member->income, 2) . ' ج.م' : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state icon="users" title="لا يوجد أفراد" description="لم يتم تسجيل أفراد لهذه الأسرة." />
                @endif
            </x-ui.card>
        </x-ui.tab-panel>

        {{-- ── Tab: Income ── --}}
        <x-ui.tab-panel name="income">
            <div class="space-y-4">
                <x-ui.card padding>
                    <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">مصادر الدخل</h2>
                    @if($family->incomeSources->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-[var(--color-border)]">
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">المصدر</th>
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">فعال</th>
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">القيمة</th>
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--color-border)]">
                                    @foreach($family->incomeSources as $source)
                                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                                            <td class="px-3 py-2.5 font-medium text-[var(--color-text-primary)]">{{ $incomeLabels[$source->source_type] ?? $source->source_type }}</td>
                                            <td class="px-3 py-2.5">
                                                @if($source->is_active)
                                                    <x-ui.badge variant="success" size="sm">نعم</x-ui.badge>
                                                @else
                                                    <x-ui.badge variant="neutral" size="sm">لا</x-ui.badge>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $source->amount ? number_format($source->amount, 2) . ' ج.م' : '—' }}</td>
                                            <td class="px-3 py-2.5 text-[var(--color-text-muted)]">{{ $source->notes ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state icon="banknotes" title="لا توجد مصادر دخل" description="لم يتم تسجيل مصادر دخل لهذه الأسرة." />
                    @endif
                </x-ui.card>

                {{-- Income summary --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.card variant="stat" padding>
                        <p class="text-xs text-[var(--color-text-muted)] mb-1">إجمالي الدخل</p>
                        <p class="text-xl font-bold text-[var(--accent-600)]">{{ number_format($family->total_income, 2) }} ج.م</p>
                    </x-ui.card>
                    <x-ui.card variant="stat" padding>
                        <p class="text-xs text-[var(--color-text-muted)] mb-1">متوسط الدخل للفرد</p>
                        <p class="text-xl font-bold text-[var(--color-text-primary)]">{{ number_format($family->average_income_per_person, 2) }} ج.م</p>
                    </x-ui.card>
                </div>

                {{-- Burdens --}}
                @if($family->burdens->count() > 0)
                    <x-ui.card padding>
                        <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">الأعباء والالتزمات</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-[var(--color-border)]">
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">العبء</th>
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">القيمة</th>
                                        <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--color-border)]">
                                    @foreach($family->burdens as $burden)
                                        <tr class="hover:bg-[var(--color-bg-secondary)]/50 transition-colors">
                                            <td class="px-3 py-2.5 font-medium text-[var(--color-text-primary)]">{{ $burdenLabels[$burden->burden_type] ?? $burden->burden_type }}</td>
                                            <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $burden->amount ? number_format($burden->amount, 2) . ' ج.م' : '—' }}</td>
                                            <td class="px-3 py-2.5 text-[var(--color-text-muted)]">{{ $burden->notes ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                @endif
            </div>
        </x-ui.tab-panel>

        {{-- ── Tab: Resources ── --}}
        <x-ui.tab-panel name="resources">
            @php
                $resourceLabels = [
                    'land_owned_share'   => 'أراضٍ ملك - سهم',
                    'land_owned_qirat'   => 'أراضٍ ملك - قيراط',
                    'land_owned_feddan'  => 'أراضٍ ملك - فدان',
                    'land_rented_share'  => 'أراضٍ إيجار - سهم',
                    'land_rented_qirat'  => 'أراضٍ إيجار - قيراط',
                    'land_rented_feddan' => 'أراضٍ إيجار - فدان',
                    'cows'               => 'أبقار',
                    'buffalo'            => 'جاموس',
                    'sheep'              => 'أغنام',
                    'goats'              => 'ماعز',
                    'business_commercial'=> 'مشروع تجاري',
                    'business_industrial'=> 'مشروع صناعي',
                    'business_craft'     => 'مشروع حرفي',
                    'business_agricultural' => 'مشروع زراعي',
                ];
            @endphp
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">موارد الأسرة</h2>
                @if($family->resources->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)]">
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">المورد</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">القيمة / العدد</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--color-border)]">
                                @foreach($family->resources as $resource)
                                    <tr>
                                        <td class="px-3 py-2.5 font-medium text-[var(--color-text-primary)]">{{ $resourceLabels[$resource->resource_type] ?? $resource->resource_type }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $resource->quantity ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-[var(--color-text-muted)]">لا توجد موارد مسجلة</p>
                @endif
            </x-ui.card>
        </x-ui.tab-panel>

        {{-- ── Tab: Burdens ── --}}
        <x-ui.tab-panel name="burdens">
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">الأعباء والالتزمات</h2>
                @if($family->burdens->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-[var(--color-border)]">
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">العبء</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">القيمة</th>
                                    <th class="text-start px-3 py-2 text-xs font-semibold text-[var(--color-text-muted)]">ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--color-border)]">
                                @foreach($family->burdens as $burden)
                                    <tr>
                                        <td class="px-3 py-2.5 font-medium text-[var(--color-text-primary)]">{{ $burdenLabels[$burden->burden_type] ?? $burden->burden_type }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-secondary)]">{{ $burden->amount ? number_format($burden->amount, 2) . ' ج.م' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-[var(--color-text-muted)]">{{ $burden->notes ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-[var(--color-text-muted)]">لا توجد أعباء مسجلة</p>
                @endif
            </x-ui.card>
        </x-ui.tab-panel>

        {{-- ── Tab: Housing ── --}}
        <x-ui.tab-panel name="housing">
            @php $housing = $family->housing; @endphp
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">حالة السكن</h2>
                @if($housing)
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">نوع السكن</dt>
                            <dd class="text-sm text-[var(--color-text-primary)]">
                                {{ $housing->housing_type }}
                                @if($housing->housing_type === 'أخرى' && $housing->housing_type_other)
                                    ({{ $housing->housing_type_other }})
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">حالة الإقامة</dt>
                            <dd class="text-sm text-[var(--color-text-primary)]">{{ $housing->residence_status ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">عدد الطوابق</dt>
                            <dd class="text-sm text-[var(--color-text-primary)]">{{ $housing->floors_count ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">عدد الغرف</dt>
                            <dd class="text-sm text-[var(--color-text-primary)]">{{ $housing->rooms_count ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">نوع السقف</dt>
                            <dd class="text-sm text-[var(--color-text-primary)]">{{ $housing->roof_type ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-[var(--color-text-muted)] mb-1">المرافق</dt>
                            <dd class="text-sm text-[var(--color-text-primary)] flex flex-wrap gap-2">
                                <x-ui.badge variant="{{ $housing->has_water ? 'success' : 'neutral' }}" size="sm">مياه</x-ui.badge>
                                <x-ui.badge variant="{{ $housing->has_electricity ? 'success' : 'neutral' }}" size="sm">كهرباء</x-ui.badge>
                                <x-ui.badge variant="{{ $housing->has_sewage ? 'success' : 'neutral' }}" size="sm">صرف صحي</x-ui.badge>
                            </dd>
                        </div>
                    </dl>

                    @if($housing->finishing_description)
                        <div class="mb-3">
                            <p class="text-xs font-medium text-[var(--color-text-muted)] mb-1">وصف التشطيب</p>
                            <p class="text-sm text-[var(--color-text-primary)] leading-relaxed bg-[var(--color-bg-secondary)] rounded-lg p-3">{{ $housing->finishing_description }}</p>
                        </div>
                    @endif
                    @if($housing->electrical_appliances)
                        <div class="mb-3">
                            <p class="text-xs font-medium text-[var(--color-text-muted)] mb-1">الأجهزة الكهربائية</p>
                            <p class="text-sm text-[var(--color-text-primary)] leading-relaxed bg-[var(--color-bg-secondary)] rounded-lg p-3">{{ $housing->electrical_appliances }}</p>
                        </div>
                    @endif
                    @if($housing->home_furniture)
                        <div class="mb-3">
                            <p class="text-xs font-medium text-[var(--color-text-muted)] mb-1">أثاث المنزل</p>
                            <p class="text-sm text-[var(--color-text-primary)] leading-relaxed bg-[var(--color-bg-secondary)] rounded-lg p-3">{{ $housing->home_furniture }}</p>
                        </div>
                    @endif
                    @if($housing->other_equipment)
                        <div>
                            <p class="text-xs font-medium text-[var(--color-text-muted)] mb-1">معدات أخرى</p>
                            <p class="text-sm text-[var(--color-text-primary)] leading-relaxed bg-[var(--color-bg-secondary)] rounded-lg p-3">{{ $housing->other_equipment }}</p>
                        </div>
                    @endif
                @else
                    <x-ui.empty-state icon="home" title="لا توجد بيانات سكن" description="لم يتم تسجيل بيانات سكن لهذه الأسرة." />
                @endif
            </x-ui.card>
        </x-ui.tab-panel>
    </x-ui.tabs>

    {{-- ═══════════════════════════════════════════════════════ Status History ══ --}}
    @if($family->statusHistories->count() > 0)
        <x-ui.card padding>
            <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">سجل الحالات</h2>
            <div class="space-y-4">
                @foreach($family->statusHistories as $history)
                    @php
                        $toStatus = $history->to_status ? \App\Enums\FamilyStatus::tryFrom($history->to_status) : null;
                        $fromStatus = $history->from_status ? \App\Enums\FamilyStatus::tryFrom($history->from_status) : null;
                    @endphp
                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-3 h-3 rounded-full bg-[var(--accent-500)] mt-1.5"></div>
                            @if(!$loop->last)
                                <div class="w-0.5 h-full bg-[var(--color-border)] mt-1 min-h-[2rem]"></div>
                            @endif
                        </div>
                        <div class="pb-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($fromStatus)
                                    <x-ui.badge variant="{{ $fromStatus->variant() }}" size="sm">{{ $fromStatus->label() }}</x-ui.badge>
                                    <svg class="w-3 h-3 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                    </svg>
                                @endif
                                @if($toStatus)
                                    <x-ui.badge variant="{{ $toStatus->variant() }}" size="sm">{{ $toStatus->label() }}</x-ui.badge>
                                @endif
                            </div>
                            <p class="text-xs text-[var(--color-text-muted)] mt-1">
                                {{ $history->created_at?->format('Y/m/d H:i') }}
                                @if($history->changer)
                                    — {{ $history->changer->name }}
                                @endif
                                @if($history->notes)
                                    — {{ $history->notes }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif
</div>
