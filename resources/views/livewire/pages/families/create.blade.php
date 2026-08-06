<div class="space-y-6" dir="rtl">

    {{-- ═══════════════════════════════════════════════════════ Page Header ═══ --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('families.index') }}" wire:navigate
                   class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <span class="text-[var(--color-text-muted)] text-sm">الأسر والحالات</span>
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">
                {{ $form->case_number ? 'تعديل بيانات الأسرة' : 'إضافة أسرة جديدة' }}
            </h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-0.5">تسجيل بيانات أسرة جديدة للمراجعة والاعتماد</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if($form->case_number)
                <x-ui.badge variant="secondary" size="md">{{ $form->case_number }}</x-ui.badge>
            @endif
            <x-ui.button variant="secondary" size="sm" type="button" wire:click="saveDraft" :loading="$submitting">
                حفظ مسودة
            </x-ui.button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ Stepper ════════ --}}
    <x-ui.card>
        <div class="p-4">
            <x-ui.stepper
                :steps="[
                    ['label' => 'البيانات الأساسية'],
                    ['label' => 'أفراد الأسرة'],
                    ['label' => 'الدخل والموارد'],
                    ['label' => 'الأعباء'],
                    ['label' => 'حالة السكن'],
                    ['label' => 'المساعدات المقترحة'],
                ]"
                :current="$currentStep"
            />
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Tab: Basic ═════ --}}
    @if($activeTab === 'basic')
        <x-ui.card padding>
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">البيانات الأساسية</h2>
            <div class="space-y-5">

                @if($form->case_number)
                    <x-ui.input
                        label="رقم الحالة"
                        name="case_number"
                        :value="$form->case_number"
                        disabled
                        hint="يتم إنشاؤه تلقائياً ولا يمكن تعديله"
                    />
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Case type --}}
                    <x-ui.select
                        label="نوع الحالة"
                        name="case_type"
                        wire:model.live="form.case_type"
                        required
                        placeholder="اختر نوع الحالة..."
                        :options="[
                            'يتيم'           => 'يتيم',
                            'أرملة'          => 'أرملة',
                            'مطلقة'          => 'مطلقة',
                            'غارم'           => 'غارم',
                            'ذوي احتياجات'  => 'ذوي احتياجات',
                            'مسن'            => 'مسن',
                        ]"
                        :error="$errors->first('form.case_type')"
                    />

                    {{-- Case name --}}
                    <x-ui.input
                        label="اسم الحالة"
                        name="case_name"
                        wire:model.live="form.case_name"
                        required
                        placeholder="مثال: أسرة محمد أحمد"
                        :error="$errors->first('form.case_name')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Community --}}
                    <x-ui.input
                        label="المنطقة / التجمع"
                        name="community"
                        wire:model.live="form.community"
                        required
                        placeholder="مثال: التجمع الخامس"
                        :error="$errors->first('form.community')"
                    />

                    {{-- Phone --}}
                    <x-ui.input
                        label="رقم الهاتف"
                        name="phone"
                        wire:model.live="form.phone"
                        required
                        placeholder="01x-xxxx-xxxx"
                        :error="$errors->first('form.phone')"
                    />
                </div>

                {{-- Detailed address --}}
                <x-ui.textarea
                    label="العنوان التفصيلي"
                    name="detailed_address"
                    wire:model="form.detailed_address"
                    rows="3"
                    placeholder="العنوان بالتفصيل..."
                    :error="$errors->first('form.detailed_address')"
                />

                {{-- Family type (radio) --}}
                <div>
                    <label class="text-sm font-medium text-[var(--color-text-primary)] block mb-2">
                        نوع الأسرة
                        <span class="text-[var(--color-danger-500)]">*</span>
                    </label>
                    <div class="flex items-center gap-6">
                        @foreach(['بسيطة' => 'بسيطة', 'مركبة' => 'مركبة'] as $value => $label)
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="family_type"
                                    value="{{ $value }}"
                                    wire:model="form.family_type"
                                    class="w-4 h-4 text-[var(--accent-500)] border-[var(--color-border)] focus:ring-[var(--accent-500)]/20 accent-[var(--accent-500)]"
                                />
                                <span class="text-sm text-[var(--color-text-primary)]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.family_type')
                        <p class="mt-1 text-xs text-[var(--color-danger-500)]">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Tab: Members ════ --}}
    @elseif($activeTab === 'members')
        <x-ui.card padding>
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">
                    أفراد الأسرة
                    <span class="text-sm font-normal text-[var(--color-text-muted)] mr-2">
                        ({{ $membersCount }} فرد)
                    </span>
                </h2>
                <x-ui.button variant="outline" size="sm" type="button" wire:click="addMember">
                    + إضافة فرد
                </x-ui.button>
            </div>

            @error('form.members')
                <x-ui.alert variant="danger" class="mb-4">{{ $message }}</x-ui.alert>
            @enderror

            @forelse($form->members as $index => $member)
                <div class="border border-[var(--color-border)] rounded-[var(--radius-lg)] p-4 mb-4"
                     wire:key="member-{{ $index }}">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-medium text-[var(--color-text-primary)]">الفرد رقم {{ $index + 1 }}</span>
                        <button type="button" wire:click="removeMember({{ $index }})"
                            class="text-[var(--color-danger-500)] hover:text-[var(--color-danger-600)] p-1 rounded transition-colors"
                            title="حذف الفرد">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <x-ui.input
                            label="الاسم"
                            name="members_{{ $index }}_name"
                            wire:model="form.members.{{ $index }}.name"
                            placeholder="الاسم الكامل"
                        />
                        <x-ui.input
                            label="الرقم القومي"
                            name="members_{{ $index }}_national_id"
                            wire:model="form.members.{{ $index }}.national_id"
                            placeholder="14 رقم"
                        />
                        <x-ui.select
                            label="صلة القرابة"
                            name="members_{{ $index }}_relationship"
                            wire:model="form.members.{{ $index }}.relationship"
                            placeholder="اختر..."
                            :options="[
                                'رب الأسرة' => 'رب الأسرة',
                                'زوجة'      => 'زوجة',
                                'ابن'       => 'ابن',
                                'ابنة'      => 'ابنة',
                                'أب'        => 'أب',
                                'أم'        => 'أم',
                                'أخ'        => 'أخ',
                                'أخت'       => 'أخت',
                            ]"
                        />
                        <x-ui.input
                            label="المهنة"
                            name="members_{{ $index }}_occupation"
                            wire:model="form.members.{{ $index }}.occupation"
                            placeholder="مثال: عامل"
                        />
                        <x-ui.input
                            type="number"
                            label="الدخل (ج.م)"
                            name="members_{{ $index }}_income"
                            wire:model="form.members.{{ $index }}.income"
                            min="0"
                            step="0.01"
                        />
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    icon="users"
                    title="لا يوجد أفراد"
                    description="أضف أفراد الأسرة من خلال زر الإضافة."
                />
            @endforelse

            @if(count($form->members) > 0)
                <div class="mt-4">
                    <x-ui.button variant="outline" type="button" wire:click="addMember">
                        + إضافة فرد
                    </x-ui.button>
                </div>
            @endif
        </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Tab: Income + Resources (merged) ══ --}}
    @elseif($activeTab === 'income_resources')
        <div class="space-y-6">

            {{-- Income Sources --}}
            <x-ui.card padding>
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">مصادر الدخل</h2>
                <p class="text-sm text-[var(--color-text-muted)] mb-4">حدد مصادر الدخل الفعالة وأدخل قيمة كل منها.</p>

                @php
                    $incomeSourceTypes = [
                        'government_salary'    => 'مرتب حكومي',
                        'private_salary'       => 'مرتب قطاع خاص',
                        'government_pension'   => 'معاش حكومي',
                        'insurance_pension'    => 'معاش تأميني',
                        'social_security'      => 'معاش ضمان',
                        'dignity_allowance'    => 'تكافل وكرامة',
                        'agricultural_land'    => 'أراضٍ زراعية',
                        'livestock'            => 'تربية مواشي',
                        'irregular_labor'      => 'عمالة غير منتظمة',
                        'own_business'         => 'مشروع ذاتي',
                        'charity_aid'          => 'إعانة جمعيات',
                        'other_income'         => 'أخرى',
                    ];
                @endphp

                <div class="space-y-3">
                    @foreach($incomeSourceTypes as $type => $label)
                        @php
                            $isActive = $form->incomeSources[$type]['is_active'] ?? false;
                            $amount = $form->incomeSources[$type]['amount'] ?? '';
                        @endphp
                        <div class="flex flex-col md:flex-row md:items-center gap-3 border border-[var(--color-border)] rounded-[var(--radius-md)] p-3"
                             wire:key="income-{{ $type }}">
                            <div class="md:w-56 shrink-0">
                                <x-ui.checkbox
                                    :label="$label"
                                    :name="'income_' . $type"
                                    wire:model.live="form.incomeSources.{{ $type }}.is_active"
                                />
                            </div>
                            <div class="flex-1">
                                <x-ui.input
                                    type="number"
                                    name="income_amount_{{ $type }}"
                                    wire:model.live="form.incomeSources.{{ $type }}.amount"
                                    placeholder="القيمة (ج.م)"
                                    min="0"
                                    step="0.01"
                                    size="sm"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Income summary --}}
                <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-[var(--color-bg-secondary)] rounded-[var(--radius-md)] p-4">
                        <p class="text-xs text-[var(--color-text-muted)] mb-1">إجمالي الدخل</p>
                        <p class="text-xl font-bold text-[var(--accent-600)]">{{ $totalIncome }} ج.م</p>
                    </div>
                    <div class="bg-[var(--color-bg-secondary)] rounded-[var(--radius-md)] p-4">
                        <p class="text-xs text-[var(--color-text-muted)] mb-1">متوسط الدخل للفرد</p>
                        <p class="text-xl font-bold text-[var(--color-text-primary)]">{{ $averageIncomePerPerson }} ج.م</p>
                    </div>
                </div>
            </x-ui.card>

            {{-- Agricultural Land --}}
            <x-ui.card padding>
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">الأراضي الزراعية</h2>

                {{-- Owned land --}}
                <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-4 mb-4">
                    <h3 class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">أراضٍ ملك</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <x-ui.input
                            type="number"
                            label="السهم"
                            name="land_owned_share"
                            wire:model.live="form.resources.land_owned_share.quantity"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                        <x-ui.input
                            type="number"
                            label="القيراط"
                            name="land_owned_qirat"
                            wire:model.live="form.resources.land_owned_qirat.quantity"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                        <x-ui.input
                            type="number"
                            label="الفدان"
                            name="land_owned_feddan"
                            wire:model.live="form.resources.land_owned_feddan.quantity"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                    </div>
                </div>

                {{-- Rented land --}}
                <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-4">
                    <h3 class="text-sm font-semibold text-[var(--color-text-primary)] mb-3">أراضٍ إيجار</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <x-ui.input
                            type="number"
                            label="السهم"
                            name="land_rented_share"
                            wire:model.live="form.resources.land_rented_share.quantity"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                        <x-ui.input
                            type="number"
                            label="القيراط"
                            name="land_rented_qirat"
                            wire:model.live="form.resources.land_rented_qirat.quantity"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                        <x-ui.input
                            type="number"
                            label="الفدان"
                            name="land_rented_feddan"
                            wire:model.live="form.resources.land_rented_feddan.quantity"
                            placeholder="0"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                    </div>
                </div>
            </x-ui.card>

            {{-- Livestock --}}
            <x-ui.card padding>
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">المواشي</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <x-ui.input
                        type="number"
                        label="عدد الأبقار"
                        name="cows"
                        wire:model.live="form.resources.cows.quantity"
                        placeholder="0"
                        min="0"
                        size="sm"
                    />
                    <x-ui.input
                        type="number"
                        label="عدد الجاموس"
                        name="buffalo"
                        wire:model.live="form.resources.buffalo.quantity"
                        placeholder="0"
                        min="0"
                        size="sm"
                    />
                    <x-ui.input
                        type="number"
                        label="عدد الأغنام"
                        name="sheep"
                        wire:model.live="form.resources.sheep.quantity"
                        placeholder="0"
                        min="0"
                        size="sm"
                    />
                    <x-ui.input
                        type="number"
                        label="عدد الماعز"
                        name="goats"
                        wire:model.live="form.resources.goats.quantity"
                        placeholder="0"
                        min="0"
                        size="sm"
                    />
                </div>
            </x-ui.card>

            {{-- Business Projects --}}
            <x-ui.card padding>
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">المشروعات</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-3">
                        <label class="flex items-center gap-3 mb-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="form.resources.business_commercial.is_active"
                                class="w-4 h-4 rounded border-[var(--color-border)] accent-[var(--accent-500)] cursor-pointer">
                            <span class="text-sm font-medium text-[var(--color-text-primary)]">مشروع تجاري</span>
                        </label>
                        <x-ui.input
                            type="number"
                            name="business_commercial_income"
                            wire:model.live="form.resources.business_commercial.quantity"
                            placeholder="الدخل / القيمة"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                    </div>
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-3">
                        <label class="flex items-center gap-3 mb-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="form.resources.business_industrial.is_active"
                                class="w-4 h-4 rounded border-[var(--color-border)] accent-[var(--accent-500)] cursor-pointer">
                            <span class="text-sm font-medium text-[var(--color-text-primary)]">مشروع صناعي</span>
                        </label>
                        <x-ui.input
                            type="number"
                            name="business_industrial_income"
                            wire:model.live="form.resources.business_industrial.quantity"
                            placeholder="الدخل / القيمة"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                    </div>
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-3">
                        <label class="flex items-center gap-3 mb-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="form.resources.business_craft.is_active"
                                class="w-4 h-4 rounded border-[var(--color-border)] accent-[var(--accent-500)] cursor-pointer">
                            <span class="text-sm font-medium text-[var(--color-text-primary)]">مشروع حرفي</span>
                        </label>
                        <x-ui.input
                            type="number"
                            name="business_craft_income"
                            wire:model.live="form.resources.business_craft.quantity"
                            placeholder="الدخل / القيمة"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                    </div>
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-3">
                        <label class="flex items-center gap-3 mb-2 cursor-pointer">
                            <input type="checkbox" wire:model.live="form.resources.business_agricultural.is_active"
                                class="w-4 h-4 rounded border-[var(--color-border)] accent-[var(--accent-500)] cursor-pointer">
                            <span class="text-sm font-medium text-[var(--color-text-primary)]">مشروع زراعي</span>
                        </label>
                        <x-ui.input
                            type="number"
                            name="business_agricultural_income"
                            wire:model.live="form.resources.business_agricultural.quantity"
                            placeholder="الدخل / القيمة"
                            min="0"
                            step="0.01"
                            size="sm"
                        />
                    </div>
                </div>
            </x-ui.card>
        </div>

    {{-- ═══════════════════════════════════════════════════════ Tab: Burdens ════ --}}
    @elseif($activeTab === 'burdens')
        <x-ui.card padding>
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">الأعباء والالتزمات</h2>

            @php
                $burdenTypes = [
                    'loans'        => 'قروض',
                    'education'    => 'تعليم أبناء',
                    'medical'      => 'علاج دوري',
                    'surgery'      => 'عمليات جراحية',
                    'other_burden' => 'أخرى',
                ];
            @endphp

            <div class="space-y-4">
                @foreach($burdenTypes as $type => $label)
                    @php
                        $amount = $form->burdens[$type]['amount'] ?? '';
                        $notes = $form->burdens[$type]['notes'] ?? '';
                    @endphp
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-4"
                         wire:key="burden-{{ $type }}">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-sm font-medium text-[var(--color-text-primary)]">{{ $label }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <x-ui.input
                                type="number"
                                label="القيمة (ج.م)"
                                name="burden_amount_{{ $type }}"
                                wire:model="form.burdens.{{ $type }}.amount"
                                placeholder="0"
                                min="0"
                                step="0.01"
                                size="sm"
                            />
                            <div class="md:col-span-2">
                                <x-ui.input
                                    label="ملاحظات"
                                    name="burden_notes_{{ $type }}"
                                    wire:model="form.burdens.{{ $type }}.notes"
                                    placeholder="تفاصيل إضافية..."
                                    size="sm"
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Tab: Housing ════ --}}
    @elseif($activeTab === 'housing')
        <x-ui.card padding>
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">حالة السكن</h2>
            <div class="space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Housing type --}}
                    <x-ui.select
                        label="نوع السكن"
                        name="housing_type"
                        wire:model.live="form.housing_type"
                        placeholder="اختر..."
                        :options="[
                            'منزل' => 'منزل',
                            'شقة'  => 'شقة',
                            'أخرى' => 'أخرى',
                        ]"
                        :error="$errors->first('form.housing_type')"
                    />

                    {{-- Housing type other --}}
                    @if($form->housing_type === 'أخرى')
                        <x-ui.input
                            label="نوع السكن الآخر"
                            name="housing_type_other"
                            wire:model="form.housing_type_other"
                            placeholder="حدد نوع السكن"
                            :error="$errors->first('form.housing_type_other')"
                        />
                    @endif

                    {{-- Residence status --}}
                    <x-ui.select
                        label="حالة الإقامة"
                        name="residence_status"
                        wire:model="form.residence_status"
                        placeholder="اختر..."
                        :options="[
                            'ملك'    => 'ملك',
                            'إيجار'  => 'إيجار',
                            'لدى الغير' => 'لدى الغير',
                        ]"
                        :error="$errors->first('form.residence_status')"
                    />

                    {{-- Roof type --}}
                    <x-ui.input
                        label="نوع السقف"
                        name="roof_type"
                        wire:model="form.roof_type"
                        placeholder="مثال: خرسانة / صاج"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Floors count --}}
                    <x-ui.input
                        type="number"
                        label="عدد الطوابق"
                        name="floors_count"
                        wire:model="form.floors_count"
                        min="0"
                    />
                    {{-- Rooms count --}}
                    <x-ui.input
                        type="number"
                        label="عدد الغرف"
                        name="rooms_count"
                        wire:model="form.rooms_count"
                        min="0"
                    />
                </div>

                {{-- Utilities --}}
                <div>
                    <label class="text-sm font-medium text-[var(--color-text-primary)] block mb-3">المرافق والخدمات</label>
                    <div class="flex flex-wrap items-center gap-6">
                        <x-ui.checkbox
                            label="مياه"
                            name="has_water"
                            wire:model="form.has_water"
                        />
                        <x-ui.checkbox
                            label="كهرباء"
                            name="has_electricity"
                            wire:model="form.has_electricity"
                        />
                        <x-ui.checkbox
                            label="صرف صحي"
                            name="has_sewage"
                            wire:model="form.has_sewage"
                        />
                    </div>
                </div>

                {{-- Text areas --}}
                <x-ui.textarea
                    label="وصف التشطيب"
                    name="finishing_description"
                    wire:model="form.finishing_description"
                    rows="3"
                    placeholder="حالة التشطيب الداخلي..."
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.textarea
                        label="الأجهزة الكهربائية"
                        name="electrical_appliances"
                        wire:model="form.electrical_appliances"
                        rows="3"
                        placeholder="ال أجهزة المتوفرة..."
                    />
                    <x-ui.textarea
                        label="أثاث المنزل"
                        name="home_furniture"
                        wire:model="form.home_furniture"
                        rows="3"
                        placeholder="الأثاث المتوفر..."
                    />
                </div>

                <x-ui.textarea
                    label="معدات أخرى"
                    name="other_equipment"
                    wire:model="form.other_equipment"
                    rows="2"
                    placeholder="أي معدات أخرى..."
                />
            </div>
        </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════ Tab: Aid (المساعدات المقترحة) ════════ --}}
    @elseif($activeTab === 'aid')
        <div class="space-y-6">
            <x-ui.card padding>
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">المساعدات المقترحة</h2>
                <p class="text-sm text-[var(--color-text-muted)] mb-5">حدد استحقاق الأسرة لكل نوع من المساعدات المقترحة مع ذكر الأسباب.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--color-border)]">
                                <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase w-1/4">نوع المساعدة</th>
                                <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase w-1/4">الاستحقاق</th>
                                <th class="text-start px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] uppercase w-1/2">الأسباب</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach(\App\Enums\AidType::cases() as $aidType)
                                @php
                                    $key = $aidType->value;
                                    $eligible = $form->aids[$key]['eligible'] ?? false;
                                    $reasons = $form->aids[$key]['reasons'] ?? '';
                                @endphp
                                <tr wire:key="aid-{{ $key }}">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-[var(--color-text-primary)]">{{ $aidType->label() }}</span>
                                        </div>
                                        <p class="text-xs text-[var(--color-text-muted)] mt-0.5">{{ $aidType->description() }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox"
                                                wire:model.live="form.aids.{{ $key }}.eligible"
                                                class="w-4 h-4 rounded border-[var(--color-border)] accent-[var(--accent-500)] cursor-pointer"
                                            />
                                            <span class="text-sm {{ $eligible ? 'text-[var(--color-success-600)] font-medium' : 'text-[var(--color-text-muted)]' }}">{{ $eligible ? 'نعم' : 'لا' }}</span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-ui.textarea
                                            name="aid_reasons_{{ $key }}"
                                            wire:model="form.aids.{{ $key }}.reasons"
                                            rows="1"
                                            placeholder="أسباب الاستحقاق أو عدمه..."
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════ Navigation Bar ═ --}}
    <div class="flex items-center justify-between gap-4 pt-2">
        <x-ui.button
            variant="secondary"
            type="button"
            wire:click="previousTab"
            :disabled="$activeTab === 'basic'"
        >
            السابق ←
        </x-ui.button>
        <div class="flex items-center gap-3">
            <x-ui.button variant="secondary" type="button" wire:click="saveDraft" :loading="$submitting">
                حفظ كمسودة
            </x-ui.button>
            @if($activeTab !== 'aid')
                <x-ui.button variant="primary" type="button" wire:click="nextTab">
                    التالي ←
                </x-ui.button>
            @else
                <x-ui.button variant="primary" type="button" wire:click="confirmSubmit" :loading="$submitting">
                    إرسال للمراجعة
                </x-ui.button>
            @endif
        </div>
    </div>

    {{-- Cancel link --}}
    <div class="text-center">
        <a href="{{ route('families.index') }}" wire:navigate
           class="text-sm text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] transition-colors px-3 py-1 rounded hover:bg-[var(--color-danger-50)]/20">
            إلغاء والعودة لقائمة الأسر
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════════════ Confirm Modal ═══ --}}
    @if($showSubmitConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
             x-data x-on:keydown.escape.window="$wire.showSubmitConfirm = false">
            <div class="bg-white rounded-[var(--radius-xl)] shadow-2xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-warning-50)] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[var(--color-warning-500)]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-[var(--color-text-primary)] text-lg">تأكيد إرسال البيانات</h3>
                        <p class="text-sm text-[var(--color-text-secondary)] mt-1 leading-relaxed">
                            بعد إرسال البيانات للمراجعة لن تتمكن من تعديلها إلا إذا أعادتها الإدارة للاستكمال.
                            <br>هل تريد المتابعة؟
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="secondary" type="button" wire:click="$set('showSubmitConfirm', false)">
                        إلغاء
                    </x-ui.button>
                    <x-ui.button variant="primary" type="button" wire:click="submit" :loading="$submitting">
                        نعم، إرسال للمراجعة
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

</div>
