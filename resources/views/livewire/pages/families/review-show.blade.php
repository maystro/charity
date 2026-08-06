<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════ Page Header ═══ --}}
    <x-layout.page-header
        title="مراجعة الحالة: {{ $family->case_name }}"
        :breadcrumbs="[
            ['label' => 'حالات تحت المراجعة', 'route' => 'families.index', 'query' => ['status' => 'under_review']],
            ['label' => $family->case_number],
        ]"
    />

    {{-- ═══════════════════════════════════════════════════════ Status Bar ═════ --}}
    <x-ui.card padding>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">رقم الحالة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->case_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">اسم الحالة</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->case_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">تاريخ الإرسال</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->submitted_at?->format('Y/m/d') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-[var(--color-text-muted)] mb-1">مقدم البيانات</p>
                    <p class="font-semibold text-[var(--color-text-primary)]">{{ $family->submitter?->name ?? '—' }}</p>
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
            'basic'     => 'البيانات الأساسية',
            'members'   => 'أفراد الأسرة',
            'income'    => 'حالة الدخل',
            'resources' => 'موارد الأسرة',
            'burdens'   => 'الأعباء',
            'housing'   => 'حالة السكن',
            'aid'       => 'المساعدات المقترحة',
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
                @php
                    $incomeLabels = [
                        'government_salary'  => 'مرتب حكومي',
                        'private_salary'     => 'مرتب قطاع خاص',
                        'government_pension' => 'معاش حكومي',
                        'insurance_pension'  => 'معاش تأميني',
                        'social_security'    => 'معاش ضمان',
                        'dignity_allowance'  => 'تكافل وكرامة',
                        'agricultural_land'  => 'أراضٍ زراعية',
                        'livestock'          => 'تربية مواشي',
                        'irregular_labor'    => 'عمالة غير منتظمة',
                        'own_business'       => 'مشروع ذاتي',
                        'charity_aid'        => 'إعانة جمعيات',
                        'other_income'       => 'أخرى',
                    ];
                    $burdenLabels = [
                        'loans'        => 'قروض',
                        'education'    => 'تعليم أبناء',
                        'medical'      => 'علاج دوري',
                        'surgery'      => 'عمليات جراحية',
                        'other_burden' => 'أخرى',
                    ];
                @endphp
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

        {{-- ── Tab: Aid (المساعدات المقترحة) ── --}}
        <x-ui.tab-panel name="aid">
            <x-ui.card padding>
                <h2 class="text-base font-semibold text-[var(--color-text-primary)] mb-4">المساعدات المقترحة</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-border)]">
                                <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">نوع المساعدة</th>
                                <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الاستحقاق</th>
                                <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase">الأسباب</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach(\App\Enums\AidType::cases() as $aidType)
                                @php
                                    $aidData = $family->aids[$aidType->value] ?? null;
                                    $eligible = $aidData['eligible'] ?? false;
                                    $reasons = $aidData['reasons'] ?? '';
                                @endphp
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-[var(--color-text-primary)]">{{ $aidType->label() }}</span>
                                        <p class="text-xs text-[var(--color-text-muted)] mt-0.5">{{ $aidType->description() }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($eligible)
                                            <x-ui.badge variant="success" size="sm">نعم</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral" size="sm">لا</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[var(--color-text-secondary)]">
                                        {{ $reasons ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </x-ui.tab-panel>
    </x-ui.tabs>

    {{-- ═══════════════════════════════════════════════════════ Actions ════════ --}}
    <x-ui.card padding>
        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="danger" type="button" wire:click="$set('showRejectModal', true)">
                رفض
            </x-ui.button>
            <x-ui.button variant="secondary" type="button" wire:click="$set('showReturnModal', true)">
                إعادة للاستكمال
            </x-ui.button>
            <x-ui.button variant="success" type="button" wire:click="$set('showApproveModal', true)">
                اعتماد
            </x-ui.button>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Approve Modal ══ --}}
    @if($showApproveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             x-data x-on:keydown.escape.window="$wire.showApproveModal = false">
            <div class="bg-white rounded-[var(--radius-xl)] shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-success-50)] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[var(--color-success-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[var(--color-text-primary)] text-lg">تأكيد الاعتماد</h3>
                        <p class="text-sm text-[var(--color-text-secondary)] mt-1 leading-relaxed">
                            سيتم اعتماد هذه الأسرة وإضافتها إلى قائمة الأسر والحالات المعتمدة.
                        </p>
                    </div>
                </div>
                <x-ui.textarea
                    label="ملاحظات المراجعة (اختياري)"
                    name="review_notes"
                    wire:model="reviewNotes"
                    rows="3"
                    placeholder="ملاحظات اختيارية..."
                />
                @if($family->submitter)
                    <p class="text-sm text-[var(--color-text-muted)]">
                        الباحث: <span class="font-medium text-[var(--color-text-primary)]">{{ $family->submitter->name }}</span>
                    </p>
                @endif
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="secondary" type="button" wire:click="$set('showApproveModal', false)">
                        إلغاء
                    </x-ui.button>
                    <x-ui.button variant="success" type="button" wire:click="approve">
                        نعم، اعتماد
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ Return Modal ════ --}}
    @if($showReturnModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             x-data x-on:keydown.escape.window="$wire.showReturnModal = false">
            <div class="bg-white rounded-[var(--radius-xl)] shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-warning-50)] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[var(--color-warning-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[var(--color-text-primary)] text-lg">إعادة للاستكمال</h3>
                        <p class="text-sm text-[var(--color-text-secondary)] mt-1 leading-relaxed">
                            سيتم إعادة الحالة لمقدم البيانات لاستكمال النواقص. الرجاء تحديد السبب.
                        </p>
                    </div>
                </div>
                <x-ui.textarea
                    label="سبب الإعادة (مطلوب)"
                    name="return_reason"
                    wire:model="returnReason"
                    rows="3"
                    required
                    placeholder="حدد النواقص المطلوب استكمالها..."
                />
                @error('returnReason')
                    <p class="text-xs text-[var(--color-danger-500)]">{{ $message }}</p>
                @enderror
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="secondary" type="button" wire:click="$set('showReturnModal', false)">
                        إلغاء
                    </x-ui.button>
                    <x-ui.button variant="secondary" type="button" wire:click="returnForCompletion">
                        إعادة للاستكمال
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ Reject Modal ════ --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             x-data x-on:keydown.escape.window="$wire.showRejectModal = false">
            <div class="bg-white rounded-[var(--radius-xl)] shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-danger-50)] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[var(--color-danger-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[var(--color-text-primary)] text-lg">تأكيد الرفض</h3>
                        <p class="text-sm text-[var(--color-text-secondary)] mt-1 leading-relaxed">
                            سيتم رفض هذه الحالة نهائياً. الرجاء تحديد سبب الرفض.
                        </p>
                    </div>
                </div>
                <x-ui.textarea
                    label="سبب الرفض (مطلوب)"
                    name="rejection_reason"
                    wire:model="rejectionReason"
                    rows="3"
                    required
                    placeholder="حدد سبب الرفض..."
                />
                @error('rejectionReason')
                    <p class="text-xs text-[var(--color-danger-500)]">{{ $message }}</p>
                @enderror
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="secondary" type="button" wire:click="$set('showRejectModal', false)">
                        إلغاء
                    </x-ui.button>
                    <x-ui.button variant="danger" type="button" wire:click="reject">
                        نعم، رفض
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

</div>
