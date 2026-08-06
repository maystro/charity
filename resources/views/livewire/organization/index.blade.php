<div class="space-y-6">
    <x-layout.page-header
        title="بيانات المؤسسة"
        subtitle="حدد الاسم الظاهر في الشريط الجانبي، والاسم التعريفي، وشعار المؤسسة"
        :breadcrumbs="[['label' => 'إدارة النظام', 'route' => 'settings.index'], ['label' => 'بيانات المؤسسة']]"
    />

    <form wire:submit="save" class="space-y-6">
        <x-ui.card padding>
            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">معلومات الهوية المؤسسية</h2>
                    <p class="text-sm text-[var(--color-text-muted)] mt-1">هذه البيانات تظهر أعلى الشريط الجانبي قبل “لوحة التحكم”.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="lg:col-span-2">
                        <x-ui.input
                            label="اسم المؤسسة"
                            name="organizationName"
                            wire:model="organizationName"
                            required
                            placeholder="مثال: جمعية الرحمة الخيرية"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <x-ui.input
                            label="الاسم التعريفي"
                            name="organizationTagline"
                            wire:model="organizationTagline"
                            required
                            placeholder="مثال: العمل الخيري والتنمية المجتمعية"
                        />
                    </div>

                    <div class="lg:col-span-2 space-y-3">
                        <label class="block text-sm font-medium text-[var(--color-text-primary)]">شعار المؤسسة</label>

                        <div class="grid grid-cols-1 lg:grid-cols-[1fr_260px] gap-4 items-start">
                            <div class="space-y-2">
                                <input
                                    type="file"
                                    accept="image/*"
                                    wire:model="logo"
                                    class="block w-full text-sm text-[var(--color-text-secondary)] file:mr-4 file:rounded-[var(--radius-md)] file:border-0 file:bg-[var(--accent-50)] file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-[var(--accent-700)] hover:file:bg-[var(--accent-100)]"
                                />
                                <p class="text-xs text-[var(--color-text-muted)]">
                                    ارفع صورة بصيغة PNG أو JPG. سيظهر الشعار في منتصف رأس القائمة الجانبية.
                                </p>
                            </div>

                            <div class="rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-[var(--color-bg-secondary)]/40 p-4">
                                <div class="flex flex-col items-center text-center gap-3">
                                    <div class="w-20 h-20 rounded-2xl overflow-hidden border border-[var(--color-border)] bg-white flex items-center justify-center">
                                        @if($logo)
                                            <img src="{{ $logo->temporaryUrl() }}" alt="معاينة الشعار" class="w-full h-full object-contain p-2" />
                                        @elseif($currentLogoUrl)
                                            <img src="{{ $currentLogoUrl }}" alt="شعار المؤسسة الحالي" class="w-full h-full object-contain p-2" />
                                        @else
                                            <x-heroicon-o-building-office-2 class="w-9 h-9 text-[var(--accent-500)]" />
                                        @endif
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs text-[var(--color-text-muted)]">{{ $organizationTagline ?: 'الاسم التعريفي' }}</p>
                                        <p class="text-sm font-semibold text-[var(--color-text-primary)]">{{ $organizationName ?: 'اسم المؤسسة' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>إلغاء</x-ui.button>
            <x-ui.button variant="primary" icon="check" type="submit" wire:loading.attr="disabled">حفظ بيانات المؤسسة</x-ui.button>
        </div>
    </form>
</div>
