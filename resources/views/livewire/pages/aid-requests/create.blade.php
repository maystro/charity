@php use App\Enums\AidType; @endphp

<div class="space-y-6" dir="rtl">

    {{-- ══════════════════════════════════════════════════ Page Header ══════════ --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('aid-requests.index') }}" wire:navigate
                   class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <span class="text-[var(--color-text-muted)] text-sm">طلبات المساعدة</span>
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">
                @if($aidRequestId) تعديل طلب مساعدة @else إضافة طلب مساعدة @endif
            </h1>
            <p class="text-sm text-[var(--color-text-muted)] mt-0.5">
                يتم حفظ النموذج لحين الاعتماد من الإدارة
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if($aidRequestId)
                <x-ui.badge variant="warning">مسودة</x-ui.badge>
                <span class="text-xs text-[var(--color-text-muted)]">
                    رقم الطلب: {{ \App\Models\AidRequest::find($aidRequestId)?->request_number }}
                </span>
            @endif
            <x-ui.button variant="secondary" size="sm" type="button" wire:click="saveDraft" :loading="$submitting">
                حفظ المسودة
            </x-ui.button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════ نموذج أساسي ══════════ --}}
    <x-ui.card padding>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- اختيار الأسرة المعتمدة (Searchable Combobox) --}}
            <div class="md:col-span-2">
                <x-ui.combobox
                    label="الأسرة المستفيدة (من الأسر المعتمدة)"
                    name="family_id"
                    wire:model.live="family_id"
                    required
                    placeholder="ابحث عن أسرة بالاسم أو الكود..."
                    searchPlaceholder="ابحث بالاسم أو كود الأسرة..."
                    :options="collect($families)->map(fn ($f) => ['value' => $f['id'], 'label' => $f['name']])->values()->toArray()"
                    :error="$errors->first('family_id')"
                    hint="يتم عرض الأسر ذات حالة معتمدة فقط — ابدأ الكتابة للبحث."
                />
            </div>

            <!-- شريط معلومات الأسرة -->
            @if($selectedFamily)
                <div class="md:col-span-2 bg-[var(--color-bg-secondary)]/40 border border-[var(--color-border)] rounded-[var(--radius-md)] p-4 flex flex-wrap items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-[var(--color-text-muted)]">كود الأسرة:</span>
                        <span class="font-medium text-[var(--color-text-primary)]">{{ $selectedFamily->case_number }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[var(--color-text-muted)]">رب الأسرة:</span>
                        <span class="font-medium text-[var(--color-text-primary)]">{{ $selectedFamily->case_name }}</span>
                    </div>
                    @if($selectedFamily->branch ?? null)
                        <div class="flex items-center gap-2">
                            <span class="text-[var(--color-text-muted)]">الفرع:</span>
                            <span class="font-medium text-[var(--color-text-primary)]">{{ $selectedFamily->branch?->name ?? '—' }}</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- العنوان العام للطلب --}}
            <div class="md:col-span-2">
                <x-ui.input
                    label="العنوان العام للطلب"
                    name="title"
                    wire:model="title"
                    placeholder="مثال: طلب مساعدة عاجلة لأسرة محمد"
                    required
                    :error="$errors->first('title')"
                />
            </div>

            {{-- ملاحظات داخلية --}}
            <div class="md:col-span-2">
                <x-ui.textarea
                    label="ملاحظات داخلية (اختياري)"
                    name="internal_notes"
                    wire:model="internal_notes"
                    placeholder="ملاحظات تظهر للمسؤولين فقط..."
                    rows="2"
                />
            </div>
        </div>
    </x-ui.card>

    {{-- ══════════════════════════════════════════════════ كروت أنواع المساعدات ══ --}}
    @if($selectedFamily)
        <x-ui.card padding>
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">المساعدات المتاحة</h2>
                    <p class="text-sm text-[var(--color-text-muted)] mt-0.5">
                        اضغط «إضافة خدمة» لإضافة طلب جديد للأسرة
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.badge variant="info" size="sm">{{ count($items) }} طلبات مضافة</x-ui.badge>
                    @if(! $selectedAidType)
                        <x-ui.button variant="primary" size="md" type="button" wire:click="openAddItem" icon="plus">
                            إضافة خدمة
                        </x-ui.button>
                    @endif
                </div>
            </div>

            @if($selectedAidType)
                {{-- ═════════════════ النموذج الفرعي لإضافة بند ═══════════════ --}}
                <div class="border border-[var(--accent-500)]/30 rounded-[var(--radius-lg)] bg-[var(--accent-50)]/20 p-5 space-y-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-[var(--color-text-primary)] flex items-center gap-2">
                            <span class="w-9 h-9 rounded-full bg-[var(--accent-500)]/15 flex items-center justify-center text-[var(--accent-500)]">
                                <x-heroicon-s-plus class="w-5 h-5" />
                            </span>
                            إضافة طلب مساعدة جديد
                        </h3>
                        <x-ui.button variant="ghost" size="sm" type="button" wire:click="cancelAddItem" icon="x-mark">إلغاء</x-ui.button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- نوع الطلب --}}
                        <x-ui.select
                            label="نوع الطلب"
                            name="draft.aid_type"
                            wire:model="draft.aid_type"
                            required
                            placeholder="اختر نوع الطلب..."
                            :error="$errors->first('draft.aid_type')"
                            :options="collect($aidTypes)->pluck('label', 'value')->toArray()"
                        />

                        {{-- درجة الأولوية --}}
                        <x-ui.select
                            label="درجة الأولوية"
                            name="draft.priority"
                            wire:model="draft.priority"
                            required
                            :options="[
                                'عادية'     => 'عادية',
                                'متوسطة'    => 'متوسطة',
                                'مرتفعة'    => 'مرتفعة',
                                'عاجلة جداً' => 'عاجلة جداً',
                            ]"
                            :error="$errors->first('draft.priority')"
                        />
                    </div>

                    {{-- العنوان المختصر + القيمة المالية على صف واحد --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <x-ui.input
                            label="عنوان مختصر للطلب"
                            name="draft.need_title"
                            wire:model="draft.need_title"
                            required
                            placeholder="مثال: اشتراء دواء السكري"
                            :error="$errors->first('draft.need_title')"
                        />

                        <x-ui.input
                            label="القيمة المالية (ج.م)"
                            name="draft.unit_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            wire:model="draft.unit_cost"
                            required
                            placeholder="0.00"
                            suffix="ج.م"
                            :error="$errors->first('draft.unit_cost')"
                            hint="اكتب القيمة المالية المطلوبة لهذا البند."
                        />
                    </div>

                    {{-- وصف الاحتياج --}}
                    <x-ui.textarea
                        label="وصف الاحتياج"
                        name="draft.need_description"
                        wire:model="draft.need_description"
                        required
                        rows="3"
                        placeholder="اشرح طبيعة الاحتياج بدقة..."
                        :error="$errors->first('draft.need_description')"
                    />

                    {{-- مفتاح المساعدة الدورية --}}
                    <div class="flex items-center gap-3 bg-white border border-[var(--color-border)] rounded-[var(--radius-md)] p-3">
                        <x-ui.switch
                            name="draft.is_recurring"
                            wire:model.live="draft.is_recurring"
                        />
                        <div>
                            <p class="text-sm font-medium text-[var(--color-text-primary)]">مساعدة دورية</p>
                            <p class="text-xs text-[var(--color-text-muted)]">فعّل هذا الخيار إذا كانت المساعدة تتكرر بانتظام</p>
                        </div>
                    </div>

                    @if($draft['is_recurring'])
                        {{-- الفارق الزمني + تاريخ بدء التنفيذ --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-white border border-[var(--color-border)] rounded-[var(--radius-md)] p-4">
                            <x-ui.input
                                label="الفارق الزمني بين مرات التنفيذ (بالأيام)"
                                name="draft.recurrence_interval_days"
                                type="number"
                                wire:model="draft.recurrence_interval_days"
                                required
                                placeholder="30"
                                :error="$errors->first('draft.recurrence_interval_days')"
                                hint="مثال: 30 للمساعدة الشهرية، 7 للأسبوعية."
                            />
                            <x-ui.input
                                label="تاريخ بدء التنفيذ"
                                name="draft.execution_start_date"
                                type="date"
                                wire:model="draft.execution_start_date"
                                required
                                :error="$errors->first('draft.execution_start_date')"
                            />
                        </div>
                    @endif

                    {{-- مرفقات خاصة بالطلب --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-[var(--color-text-primary)] flex items-center gap-2">
                                <x-heroicon-o-paper-clip class="w-4 h-4 text-[var(--color-text-muted)]" />
                                مرفقات / مستندات خاصة بالطلب
                            </h4>
                            <x-ui.button variant="secondary" size="sm" type="button" wire:click="addDraftAttachment" icon="plus">
                                إضافة مرفق
                            </x-ui.button>
                        </div>

                        @if(count($draftAttachments) > 0)
                            <div class="space-y-3">
                                @foreach($draftAttachments as $i => $attachment)
                                    <div class="flex items-center gap-3 bg-white border border-[var(--color-border)] rounded-[var(--radius-md)] p-3">
                                        <div class="flex-1">
                                            <input
                                                type="file"
                                                wire:model="draftAttachments.{{ $i }}.file"
                                                accept="image/*,.pdf,.doc,.docx"
                                                class="block w-full text-sm text-[var(--color-text-secondary)] file:me-3 file:py-2 file:px-3 file:rounded-[var(--radius-sm)] file:border-0 file:bg-[var(--accent-500)]/10 file:text-[var(--accent-500)] file:font-medium"
                                            />
                                            @if(isset($attachment['file']) && is_object($attachment['file']))
                                                <p class="text-xs text-[var(--color-text-muted)] mt-1">
                                                    {{ $attachment['file']->getClientOriginalName() }}
                                                </p>
                                            @endif
                                        </div>
                                        <button
                                            wire:click="removeDraftAttachment({{ $i }})"
                                            type="button"
                                            class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors"
                                            title="إزالة"
                                        >
                                            <x-heroicon-o-trash class="w-4.5 h-4.5" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-[var(--color-text-muted)] italic">لا توجد مرفقات. يمكن إضافتها اختيارياً.</p>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2 border-t border-[var(--color-border)]"></div>
                    <x-ui.button variant="primary" type="button" wire:click="saveItem" icon="plus">
                        إضافة هذه المساعدة لقائمة الطلبات
                    </x-ui.button>
                </div>
            @else
                @if(empty($aidTypes))
                    <div class="text-center py-10 text-[var(--color-text-muted)]">
                        لا توجد أنواع مساعدات متاحة لهذه الأسرة.
                    </div>
                @else
                    <div class="text-center py-8 text-[var(--color-text-muted)] text-sm">
                        اضغط زر «إضافة خدمة» بالأعلى لإدراج طلب مساعدة جديد لهذه الأسرة.
                    </div>
                @endif
            @endif
        </x-ui.card>
    @else
        <x-ui.alert variant="info" title="اختر أسرة معتمدة أولاً">
            يجب اختيار أسرة من قائمة الأسر المعتمدة لعرض أنواع المساعدات المتاحة وإضافة الطلبات.
        </x-ui.alert>
    @endif

    {{-- ══════════════════════════════════════════════════ قائمة الطلبات المضافة ══ --}}
    @if(count($items) > 0)
        <x-ui.card padding>
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-4">الطلبات المضافة ({{ count($items) }})</h2>

            <div class="space-y-3">
                @foreach($items as $i => $item)
                    @php
                        $aidType = App\Enums\AidType::tryFrom($item['aid_type'] ?? '');
                        $priorityVariant = match($item['priority'] ?? 'عادية') {
                            'عاجلة جداً' => 'danger',
                            'مرتفعة'    => 'warning',
                            'متوسطة'    => 'info',
                            default      => 'neutral',
                        };
                    @endphp
                    <div class="border border-[var(--color-border)] rounded-[var(--radius-md)] p-4 hover:shadow-xs transition-shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="w-10 h-10 rounded-full bg-[var(--accent-500)]/15 flex items-center justify-center text-[var(--accent-500)] shrink-0">
                                    <x-dynamic-component :component="'heroicon-s-' . ($aidType?->icon() ?? 'sparkles')" class="w-5 h-5" />
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-semibold text-[var(--color-text-primary)]">{{ $item['title'] }}</h3>
                                        <x-ui.badge :variant="$priorityVariant" size="sm">{{ $item['priority'] }}</x-ui.badge>
                                        @if(! empty($item['is_recurring']))
                                            <x-ui.badge variant="info" size="sm" dot>دورية</x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="text-sm text-[var(--color-text-secondary)] mt-1 line-clamp-2">
                                        {{ $item['need_description'] }}
                                    </p>
                                    <div class="mt-2 text-sm text-[var(--color-text-primary)]">
                                        <span class="text-[var(--color-text-muted)]">القيمة المالية:</span>
                                        <strong>{{ number_format((float) ($item['unit_cost'] ?? 0), 2) }} ج.م</strong>
                                    </div>
                                    @if(! empty($item['is_recurring']) && (! empty($item['recurrence_interval_days']) || ! empty($item['execution_start_date'])))
                                        <div class="mt-2 flex items-center gap-4 text-xs text-[var(--color-text-muted)]">
                                            @if(! empty($item['recurrence_interval_days']))
                                                <span>الفارق الزمني: كل <strong>{{ $item['recurrence_interval_days'] }}</strong> يوم</span>
                                            @endif
                                            @if(! empty($item['execution_start_date']))
                                                <span>يبدأ من: <strong>{{ $item['execution_start_date'] }}</strong></span>
                                            @endif
                                        </div>
                                    @endif
                                    @if(! empty($item['attachments']))
                                        <div class="mt-2 text-xs text-[var(--color-text-muted)]">
                                            <x-heroicon-o-paper-clip class="w-3.5 h-3.5 inline" /> {{ count($item['attachments']) }} مرفق
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <button
                                wire:click="removeItem({{ $i }})"
                                type="button"
                                class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors shrink-0"
                                title="إزالة"
                            >
                                <x-heroicon-o-trash class="w-4.5 h-4.5" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ══════════════════════════════════════════════════ إرسال للمراجعة ═══ --}}
            <div class="mt-6 border-t border-[var(--color-border)] pt-5 flex flex-col md:flex-row md:items-end md:justify-end gap-4">
                <div class="flex items-center gap-2 me-auto">
                    <x-ui.checkbox
                        name="acknowledged"
                        wire:model.live="acknowledged"
                        label="أقر أن البيانات صحيحة وأرسل الطلب للمراجعة"
                    />
                </div>
                @error('acknowledged')
                    <p class="text-xs text-[var(--color-danger-500)]">{{ $message }}</p>
                @enderror

                <x-ui.button variant="primary" type="button" wire:click="confirmSubmit" :disabled="!$acknowledged">
                    إرسال للمراجعة
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- ══════════════════════════════════════════════════ تأكيد الإرسال ═══════ --}}
    <x-ui.modal name="submit-confirm" title="تأكيد الإرسال للمراجعة" closeable>
        <div class="space-y-3">
            <p class="text-sm text-[var(--color-text-secondary)]">
                هل أنت متأكد من إرسال الطلب للمراجعة؟
            </p>
            <ul class="text-sm text-[var(--color-text-muted)] list-disc ps-5 space-y-1">
                <li>سيتم إرسال {{ count($items) }} طلب مساعدة للمراجعة.</li>
                <li>سيتم تحويل الحالة إلى «مقدمة» لحين اعتماد الإدارة.</li>
                <li>لن يمكنك تعديل الطلب بعد الإرسال إلا إذا طُلب استكماله.</li>
            </ul>
        </div>
        <div class="flex items-center justify-end gap-3 mt-6">
            <x-ui.button variant="secondary" size="sm" x-on:click="$dispatch('close-modal', 'submit-confirm')">تراجع</x-ui.button>
            <x-ui.button variant="primary" size="sm" x-on:click="$dispatch('close-modal', 'submit-confirm'); $wire.submit()" :loading="$submitting">تأكيد الإرسال</x-ui.button>
        </div>
    </x-ui.modal>
</div>
