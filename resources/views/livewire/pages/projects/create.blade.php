<div class="space-y-6">
    <x-layout.page-header
        title="إضافة مشروع جديد"
        subtitle="أدخل بيانات المشروع وأضف مراحله لتُحسب التكلفة الإجمالية تلقائياً"
        :breadcrumbs="[['label' => 'المشروعات', 'route' => 'projects.index'], ['label' => 'إضافة مشروع']]"
    />

    <form wire:submit="save" class="space-y-6">
        {{-- بيانات المشروع الأساسية --}}
        <x-ui.card padding>
            <div class="space-y-4">
                <h3 class="text-base font-semibold text-[var(--color-text-primary)]">بيانات المشروع</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-ui.input
                            label="اسم المشروع"
                            name="name"
                            placeholder="مثال: مشروع إفطار صائم"
                            wire:model="name"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">المحافظة</label>
                        <x-ui.select name="governorate" wire:model.live="governorate">
                            <option value="">— اختر المحافظة —</option>
                            <option value="على مستوى كل المحافظات">على مستوى كل المحافظات</option>
                            @foreach($governorates as $gov)
                                <option value="{{ $gov }}">{{ $gov }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">حالة المشروع</label>
                        <x-ui.select name="status" wire:model.live="status">
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div>
                        <x-ui.date-input
                            label="تاريخ البداية"
                            name="start_date"
                            wire:model="start_date"
                        />
                    </div>
                    <div>
                        <x-ui.date-input
                            label="تاريخ النهاية"
                            name="end_date"
                            wire:model="end_date"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.textarea
                            label="وصف المشروع"
                            name="description"
                            rows="3"
                            placeholder="وصف موجز لأهداف المشروع ونطاقه..."
                            wire:model="description"
                        />
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- كارت مراحل إنشاء المشروع --}}
        <x-ui.card padding>
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-[var(--color-text-primary)]">مراحل إنشاء المشروع</h3>
                        <p class="text-xs text-[var(--color-text-muted)] mt-0.5">أضف مرحلة لكل خطوة من خطوات تنفيذ المشروع مع تكلفتها التقديرية — يُحسب الإجمالي تلقائياً.</p>
                    </div>
                    <x-ui.button variant="secondary" size="sm" type="button" wire:click="addPhase" icon="plus">
                        إضافة مرحلة
                    </x-ui.button>
                </div>

                <div class="space-y-3">
                    @foreach($phases as $i => $phase)
                        <div class="rounded-[var(--radius-md)] border border-[var(--color-border)] bg-[var(--color-bg-secondary)]/40 p-4">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-xs font-semibold text-[var(--color-text-muted)] uppercase">المرحلة {{ $i + 1 }}</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="p-1 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-bg-secondary)] disabled:opacity-30" wire:click="movePhaseUp({{ $i }})" @disabled($i === 0) aria-label="تحريك لأعلى">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" class="p-1 rounded-md text-[var(--color-text-muted)] hover:bg-[var(--color-bg-secondary)] disabled:opacity-30" wire:click="movePhaseDown({{ $i }})" @disabled($i === count($phases) - 1) aria-label="تحريك لأسفل">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button type="button" class="p-1 rounded-md text-[var(--color-danger-500)] hover:bg-red-50" wire:click="removePhase({{ $i }})" aria-label="حذف المرحلة">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <x-ui.input
                                        label="اسم المرحلة"
                                        name="phases[{{ $i }}][name]"
                                        placeholder="مثال: مرحلة التجهيز"
                                        wire:model="phases.{{ $i }}.name"
                                        required
                                    />
                                </div>
                                <div>
                                    <x-ui.input
                                        label="التكلفة (ج.م)"
                                        name="phases[{{ $i }}][cost]"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        wire:model.live.debounce.300ms="phases.{{ $i }}.cost"
                                        required
                                    />
                                </div>
                                <div class="md:col-span-3">
                                    <x-ui.textarea
                                        label="وصف المرحلة (اختياري)"
                                        name="phases[{{ $i }}][description]"
                                        rows="2"
                                        placeholder="وصف موجز للمرحلة..."
                                        wire:model="phases.{{ $i }}.description"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- الإجمالي المحسوب تلقائياً --}}
                <div class="flex items-center justify-between gap-2 rounded-[var(--radius-md)] bg-[var(--accent-50)] border border-[var(--accent-200)] px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-[var(--accent-600)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10l-3-3m0 0l-3 3m3-3v6"/></svg>
                        <span class="text-sm font-medium text-[var(--accent-700)]">المبلغ الإجمالي المطلوب</span>
                    </div>
                    <span class="text-2xl font-bold font-mono text-[var(--accent-700)]">
                        {{ number_format($totalAmount, 2) }} <span class="text-sm font-normal">ج.م</span>
                    </span>
                </div>
            </div>
        </x-ui.card>

        {{-- Action buttons --}}
        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>إلغاء</x-ui.button>
            <x-ui.button variant="primary" icon="check" type="submit" wire:loading.attr="disabled">حفظ المشروع</x-ui.button>
        </div>
    </form>
</div>
