<div class="space-y-6">
    <x-layout.page-header
        title="إصدار جديد"
        subtitle="إنشاء إصدار جديد وتوثيق التغييرات التي تمت"
    />

    <form wire:submit="save">
        {{-- Release Info --}}
        <x-ui.card padding>
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">معلومات الإصدار</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-ui.input
                        label="رقم الإصدار"
                        name="version"
                        placeholder="مثال: v1.4.0"
                        wire:model="version"
                        dir="ltr"
                    />
                    @error('version')
                        <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <x-ui.input
                        label="العنوان"
                        name="title"
                        placeholder="عنوان الإصدار"
                        wire:model="title"
                    />
                    @error('title')
                        <p class="text-sm text-[var(--color-danger-500)] mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-4">
                <x-ui.textarea
                    label="الوصف"
                    name="description"
                    placeholder="وصف تفصيلي للإصدار (اختياري)"
                    wire:model="description"
                    rows="3"
                />
            </div>
        </x-ui.card>

        {{-- Changes --}}
        <x-ui.card padding>
            <div class="flex items-center justify-between gap-3 mb-5 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">التغييرات</h2>
                    <p class="text-sm text-[var(--color-text-muted)] mt-1">قم بتوثيق الملفات التي تم تغييرها في هذا الإصدار</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.button variant="outline" size="sm" icon="sparkles" type="button" wire:click="importChanges" :loading="$importing">
                        استيراد التغييرات تلقائيًا
                    </x-ui.button>
                </div>
            </div>

            <div class="flex items-start gap-2 text-sm text-[var(--color-text-muted)] bg-[var(--color-bg-secondary)] rounded-[var(--radius-md)] p-3 mb-4">
                <x-heroicon-o-light-bulb class="w-4 h-4 mt-0.5 shrink-0 text-[var(--accent-500)]" />
                <p>زر «استيراد التغييرات تلقائيًا» يقارن الملفات الحالية بآخر إصدار محفوظ ويكتشف التغييرات: <strong>إضافة</strong> (ملف جديد)، <strong>تعديل</strong> (محتوى مختلف)، <strong>حذف</strong> (ملف أُزيل). تظهر النتائج في جدول للعرض فقط، ويمكنك حذف أي صف غير مرغوب قبل الحفظ.</p>
            </div>

            @if($importNotice)
                <div class="flex items-start gap-2 text-sm rounded-[var(--radius-md)] p-3 mb-4 border {{ $importNoticeType === 'warning' ? 'bg-[var(--color-warning-50)] text-[var(--color-warning-600)] border-[var(--color-warning-200)]' : ($importNoticeType === 'error' ? 'bg-[var(--color-danger-50)] text-[var(--color-danger-600)] border-[var(--color-danger-200)]' : 'bg-[var(--color-info-50)] text-[var(--color-info-600)] border-[var(--color-info-200)]') }}" wire:key="import-notice">
                    <x-heroicon-o-information-circle class="w-4 h-4 mt-0.5 shrink-0" />
                    <p>{{ $importNotice }}</p>
                </div>
            @endif

            @error('changes')
                <p class="text-sm text-[var(--color-danger-500)] mb-4">{{ $message }}</p>
            @enderror

            @if(empty($changes))
                <x-ui.empty-state
                    icon="sparkles"
                    title="لا توجد تغييرات بعد"
                    description="اضغط زر «استيراد التغييرات تلقائيًا» بالأعلى لاكتشاف الملفات المغيَّرة وملء الجدول تلقائيًا."
                />
            @else
                <div class="overflow-x-auto rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-white">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-[var(--color-bg-secondary)]/50 text-[var(--color-text-muted)]">
                                <th scope="col" class="py-3 px-3 text-start text-xs font-semibold uppercase tracking-wider w-12">#</th>
                                <th scope="col" class="py-3 px-3 text-start text-xs font-semibold uppercase tracking-wider w-40">نوع التغيير</th>
                                <th scope="col" class="py-3 px-3 text-start text-xs font-semibold uppercase tracking-wider">مسار الملف</th>
                                <th scope="col" class="py-3 px-3 text-start text-xs font-semibold uppercase tracking-wider w-72">الوصف</th>
                                <th scope="col" class="py-3 px-3 text-center text-xs font-semibold uppercase tracking-wider w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--color-border)]">
                            @foreach($changes as $index => $change)
                                <tr class="hover:bg-[var(--color-bg-secondary)]/40 transition-colors align-middle" wire:key="change-{{ $index }}">
                                    <td class="py-3 px-3 text-[var(--color-text-muted)]">{{ $index + 1 }}</td>
                                    <td class="py-3 px-3">
                                        @php
                                            $typeConfig = [
                                                'added' => ['variant' => 'success', 'icon' => 'plus-circle', 'label' => 'إضافة'],
                                                'modified' => ['variant' => 'info', 'icon' => 'pencil-square', 'label' => 'تعديل'],
                                                'fixed' => ['variant' => 'warning', 'icon' => 'check-circle', 'label' => 'إصلاح'],
                                                'updated' => ['variant' => 'info', 'icon' => 'arrow-path', 'label' => 'تحديث'],
                                                'removed' => ['variant' => 'danger', 'icon' => 'trash', 'label' => 'حذف'],
                                            ];
                                            $tc = $typeConfig[$change['type']] ?? $typeConfig['modified'];
                                        @endphp
                                        <x-ui.badge :variant="$tc['variant']" size="sm">{{ $tc['label'] }}</x-ui.badge>
                                    </td>
                                    <td class="py-3 px-3">
                                        <code class="text-xs font-mono text-[var(--color-text-primary)] bg-[var(--color-bg-secondary)] px-2 py-1 rounded-[var(--radius-sm)]" dir="ltr">
                                            {{ $change['file_path'] }}
                                        </code>
                                    </td>
                                    <td class="py-3 px-3 text-[var(--color-text-secondary)]">
                                        {{ $change['description'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <button
                                            type="button"
                                            wire:click="removeChange({{ $index }})"
                                            class="p-1.5 rounded-[var(--radius-sm)] text-[var(--color-text-muted)] hover:text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors"
                                            title="حذف هذا التغيير"
                                        >
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" href="{{ route('deployments.index') }}" wire:navigate>
                إلغاء
            </x-ui.button>
            <x-ui.button variant="primary" type="submit" icon="check" :loading="$wire->submitting ?? false">
                حفظ الإصدار
            </x-ui.button>
        </div>
    </form>
</div>
