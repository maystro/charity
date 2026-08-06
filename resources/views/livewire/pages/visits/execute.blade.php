@php
$status = $this->visitStatus;
$tabs = ['بيانات الزيارة', 'الحضور والموقع', 'الملاحظات والنتائج', 'التوصيات والإغلاق'];
@endphp

<div class="space-y-6">
    <x-layout.page-header title="تنفيذ الزيارة" subtitle="{{ $visit->visit_number }}">
        <x-slot:actions>
            <a href="{{ route('visits.show', $visit) }}" wire:navigate>
                <x-ui.button variant="ghost" icon="arrow-right">العودة للتفاصيل</x-ui.button>
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Stepper --}}
    <x-ui.stepper :steps="$tabs" :current="$currentTab" />

    {{-- Tab 0: Visit Data (read-only) --}}
    @if($currentTab === 0)
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">بيانات الزيارة</div>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الأسرة</dt>
                    <dd class="text-sm font-medium">{{ $visit->family?->case_name }} ({{ $visit->family?->case_number }})</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">النوع</dt>
                    <dd class="text-sm">{{ \App\Enums\VisitType::tryFrom($visit->visit_type)?->label() ?? $visit->visit_type }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الموعد المخطط</dt>
                    <dd class="text-sm">{{ $visit->scheduled_at?->format('Y/m/d H:i') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">الباحث</dt>
                    <dd class="text-sm">{{ $visit->researcher?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">المندوب</dt>
                    <dd class="text-sm">{{ $visit->representative?->name ?? '—' }}</dd>
                </div>
                @if($visit->purpose)
                    <div>
                        <dt class="mb-1 text-sm text-[var(--color-text-muted)]">الغرض</dt>
                        <dd class="text-sm">{{ $visit->purpose }}</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-sm text-[var(--color-text-muted)]">العنوان</dt>
                    <dd class="text-sm">{{ $visit->address_snapshot ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>
    @endif

    {{-- Tab 1: Attendance & Location --}}
    @if($currentTab === 1)
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">الحضور والموقع</div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input label="تاريخ ووقت البدء الفعلي" name="actual_started_at" type="datetime-local" wire:model="actual_started_at" />
                <x-ui.input label="تاريخ ووقت الانتهاء الفعلي" name="actual_completed_at" type="datetime-local" wire:model="actual_completed_at" />
                <x-ui.input label="اسم الشخص الذي تمت مقابلته" name="contacted_person" wire:model="contacted_person" placeholder="الاسم..." />
                <x-ui.input label="صفته / صلته بالأسرة" name="contacted_person_relation" wire:model="contacted_person_relation" placeholder="رب الأسرة / الزوجة / الابن..." />
                <div class="md:col-span-2">
                    <x-ui.switch label="تم التحقق من مطابقة الموقع" name="location_verified" wire:model="location_verified" />
                </div>
                <div class="md:col-span-2">
                    <x-ui.switch label="تمت الزيارة بنجاح" name="visit_completed" wire:model="visit_completed" />
                </div>
                @if(!$visit_completed)
                    <div class="md:col-span-2">
                        <x-ui.textarea label="سبب عدم تنفيذ الزيارة" name="not_completed_reason" wire:model="not_completed_reason" rows="2" placeholder="سبب عدم إكمال الزيارة..." />
                    </div>
                @endif
            </div>
        </x-ui.card>
    @endif

    {{-- Tab 2: Notes & Results --}}
    @if($currentTab === 2)
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">الملاحظات والنتائج</div>
            <div class="space-y-4">
                <x-ui.textarea label="ملخص نتائج الزيارة" name="outcome_summary" wire:model="outcome_summary" rows="5" placeholder="وصف ما تمت ملاحظته خلال الزيارة، حالة الأسرة، صحة البيانات..." />
                <x-ui.textarea label="احتياجات جديدة تم اكتشافها" name="new_needs" wire:model="new_needs" rows="3" placeholder="احتياجات جديدة ظهرت خلال الزيارة..." />
                <x-ui.select label="تقييم درجة الاستعجال" name="urgency_assessment" wire:model="urgency_assessment" :options="['' => '—', 'normal' => 'طبيعي', 'urgent' => 'عاجل', 'critical' => 'حرج']" />
            </div>
        </x-ui.card>
    @endif

    {{-- Tab 3: Recommendations & Closure --}}
    @if($currentTab === 3)
        <x-ui.card>
            <div class="mb-4 text-sm font-bold text-[var(--color-text-muted)]">التوصيات والإغلاق</div>
            <div class="space-y-4">
                <x-ui.textarea label="التوصيات" name="recommendations" wire:model="recommendations" rows="4" placeholder="الإجراءات المقترحة بعد الزيارة: لا إجراء، بحث جديد، طلب مساعدة، زيارة أخرى، إحالة..." />
                <x-ui.input label="موعد المتابعة القادمة" name="next_follow_up_at" type="datetime-local" wire:model="next_follow_up_at" />
            </div>
        </x-ui.card>
    @endif

    {{-- Navigation & Actions --}}
    <div class="flex justify-between">
        <div class="flex gap-2">
            @if($currentTab > 0)
                <x-ui.button variant="ghost" wire:click="prevTab" icon="arrow-right">السابق</x-ui.button>
            @endif
            @if($currentTab < 3)
                <x-ui.button variant="outline" wire:click="nextTab" icon="arrow-left">التالي</x-ui.button>
            @endif
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="ghost" wire:click="saveDraft" icon="document-text">حفظ كمسودة</x-ui.button>
            @if($currentTab === 3)
                <x-ui.button variant="primary" wire:click="complete" icon="check-circle">إكمال الزيارة</x-ui.button>
            @endif
        </div>
    </div>
</div>
