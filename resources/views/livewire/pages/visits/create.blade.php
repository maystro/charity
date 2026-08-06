@php
$title = $isEditing ? 'تعديل الزيارة' : 'إضافة زيارة';
@endphp

<div class="space-y-6">
    <x-layout.page-header :title="$title" subtitle="جدولة زيارة ميدانية جديدة للأسرة.">
        <x-slot:actions>
            <a href="{{ route('visits.index') }}" wire:navigate>
                <x-ui.button variant="ghost" icon="arrow-right">العودة للقائمة</x-ui.button>
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    <form wire:submit="save" class="space-y-6">
        {{-- بيانات الزيارة الأساسية --}}
        <x-ui.card>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.select
                    label="الأسرة"
                    name="family_id"
                    wire:model.live="family_id"
                    :options="collect($families)->pluck('label', 'id')->all()"
                    placeholder="اختر الأسرة"
                />
                <x-ui.select
                    label="نوع الزيارة"
                    name="visit_type"
                    wire:model="visit_type"
                    :options="$visitTypeOptions"
                />
                <x-ui.select
                    label="الأولوية"
                    name="priority"
                    wire:model="priority"
                    :options="['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'مرتفعة', 'critical' => 'عاجلة جداً']"
                />
                <x-ui.select
                    label="الحالة"
                    name="status"
                    wire:model="status"
                    :options="$visitStatusOptions"
                />
                <x-ui.textarea
                    label="الغرض من الزيارة"
                    name="purpose"
                    wire:model="purpose"
                    rows="2"
                    class="md:col-span-2"
                />
                <x-ui.select
                    label="البحث الاجتماعي (اختياري)"
                    name="research_id"
                    wire:model="research_id"
                    :options="collect($researches)->pluck('label', 'id')->all()"
                    placeholder="اختياري"
                />
                <x-ui.select
                    label="طلب المساعدة (اختياري)"
                    name="aid_request_id"
                    wire:model="aid_request_id"
                    :options="collect($aidRequests)->pluck('label', 'id')->all()"
                    placeholder="اختياري"
                />
            </div>
        </x-ui.card>

        {{-- الباحث والمندوب --}}
        <x-ui.card>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.select
                    label="الباحث"
                    name="researcher_id"
                    wire:model="researcher_id"
                    :options="collect($fieldworkers)->pluck('label', 'id')->all()"
                    placeholder="اختر الباحث"
                />
                <x-ui.select
                    label="المندوب"
                    name="representative_id"
                    wire:model="representative_id"
                    :options="collect($fieldworkers)->pluck('label', 'id')->all()"
                    placeholder="اختر المندوب"
                />
            </div>
        </x-ui.card>

        {{-- الموعد والمدة --}}
        <x-ui.card>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input
                    label="موعد الزيارة"
                    name="scheduled_at"
                    type="datetime-local"
                    wire:model="scheduled_at"
                />
                <x-ui.input
                    label="المدة المتوقعة (بالدقائق)"
                    name="duration_minutes"
                    type="number"
                    wire:model="duration_minutes"
                    placeholder="مثال: 60"
                />
                <x-ui.textarea
                    label="العنوان (مأخوذ من بيانات الأسرة)"
                    name="address_snapshot"
                    wire:model="address_snapshot"
                    rows="2"
                    class="md:col-span-2"
                />
            </div>
        </x-ui.card>

        {{-- ملاحظات --}}
        <x-ui.card>
            <x-ui.textarea
                label="ملاحظات"
                name="notes"
                wire:model="notes"
                rows="4"
                placeholder="تعليمات، ملاحظات إضافية..."
            />
        </x-ui.card>

        {{-- أزرار --}}
        <div class="flex justify-end gap-2">
            <a href="{{ route('visits.index') }}" wire:navigate>
                <x-ui.button variant="ghost">إلغاء</x-ui.button>
            </a>
            <x-ui.button variant="primary" type="submit" icon="check">
                {{ $isEditing ? 'تحديث الزيارة' : 'حفظ الزيارة' }}
            </x-ui.button>
        </div>
    </form>
</div>
