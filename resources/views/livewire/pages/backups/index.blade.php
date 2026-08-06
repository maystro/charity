<div class="space-y-6">
    <x-layout.page-header title="النسخ الاحتياطية" subtitle="نسخ احتياطية لقاعدة بيانات الموقع — تُنشأ يدويًا أو تلقائيًا يوميًا.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="create" :loading="$creating">
                نسخة احتياطية جديدة
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat label="إجمالي النسخ" :number="$this->backups->count()" icon="archive-box" variant="primary" />
        <x-ui.stat label="آخر نسخة" :number="$this->latestLabel" icon="clock" variant="info" />
        <x-ui.stat label="الاحتفاظ (أحدث نسخ)" :number="$this->keepCount" icon="shield-check" variant="success" />
        <x-ui.stat label="النسخ التلقائي اليومي" :number="$this->scheduleTime" icon="calendar-days" variant="warning" />
    </div>

    <x-ui.card padding>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">قائمة النسخ الاحتياطية</h2>
            <span class="text-sm text-[var(--color-text-muted)]">{{ $this->backups->count() }} نسخة</span>
        </div>

        @if ($this->backups->isEmpty())
            <x-ui.empty-state
                icon="archive-box"
                title="لا توجد نسخ احتياطية بعد"
                description="أنشئ نسخة احتياطية جديدة بالضغط على الزر بالأعلى، أو انتظر النسخة التلقائية اليومية."
            />
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-[var(--color-border)] text-[var(--color-text-muted)]">
                            <th class="py-3 pr-4 font-medium">اسم الملف</th>
                            <th class="py-3 pr-4 font-medium">التاريخ</th>
                            <th class="py-3 pr-4 font-medium">الحجم</th>
                            <th class="py-3 pr-4 font-medium">المصدر</th>
                            <th class="py-3 pr-4 font-medium">الحالة</th>
                            <th class="py-3 pr-4 font-medium">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-border)]">
                        @foreach ($this->backups as $backup)
                            <tr class="hover:bg-[var(--color-bg-secondary)]/60">
                                <td class="py-3 pr-4 font-mono text-xs text-[var(--color-text-primary)]" dir="ltr">
                                    {{ $backup->filename }}
                                </td>
                                <td class="py-3 pr-4 text-[var(--color-text-secondary)]">{{ $backup->created_at->translatedFormat('Y-m-d H:i') }}</td>
                                <td class="py-3 pr-4 text-[var(--color-text-secondary)]">{{ $this->formatSize($backup->size_bytes) }}</td>
                                <td class="py-3 pr-4">
                                    @if ($backup->isSystem())
                                        <x-ui.badge variant="info">تلقائي</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="primary">يدوي — {{ $backup->creator?->name ?? '—' }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">
                                    @php
                                        $statusConfig = [
                                            \App\Enums\DatabaseBackupStatus::Completed->value => ['variant' => 'success', 'label' => 'مكتملة'],
                                            \App\Enums\DatabaseBackupStatus::Failed->value => ['variant' => 'danger', 'label' => 'فاشلة'],
                                            \App\Enums\DatabaseBackupStatus::Pending->value => ['variant' => 'warning', 'label' => 'قيد الإنشاء'],
                                        ];
                                        $cfg = $statusConfig[$backup->status->value] ?? ['variant' => 'neutral', 'label' => $backup->status->value];
                                    @endphp
                                    <x-ui.badge :variant="$cfg['variant']" dot>{{ $cfg['label'] }}</x-ui.badge>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex items-center gap-2">
                                        @if ($backup->status === \App\Enums\DatabaseBackupStatus::Completed)
                                            <x-ui.button
                                                variant="secondary"
                                                size="sm"
                                                icon="arrow-down-tray"
                                                wire:click="download({{ $backup->id }})"
                                            >
                                                تنزيل
                                            </x-ui.button>

                                            @if ($this->restoreSupported)
                                                <x-ui.button
                                                    variant="outline"
                                                    size="sm"
                                                    icon="arrow-uturn-left"
                                                    wire:click="openRestoreModal({{ $backup->id }})"
                                                >
                                                    استعادة
                                                </x-ui.button>
                                            @endif
                                        @endif

                                        <x-ui.button
                                            variant="danger"
                                            size="sm"
                                            icon="trash"
                                            wire:click="delete({{ $backup->id }})"
                                            wire:confirm="هل أنت متأكد من حذف هذه النسخة الاحتياطية؟ لا يمكن التراجع."
                                        >
                                            حذف
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    @if ($this->restoreSupported)
        <x-ui.modal name="restore-modal" size="md">
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)]">استعادة نسخة احتياطية</h3>

                @if ($this->backupToRestore)
                    <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                        سيتم استبدال قاعدة البيانات الحالية بالكامل بالنسخة:
                        <span class="font-mono font-semibold text-[var(--color-danger-500)]" dir="ltr">{{ $this->backupToRestore->filename }}</span>
                    </p>

                    <div class="rounded-lg border border-[var(--color-danger-500)]/30 bg-[var(--color-danger-50)] p-3 text-sm text-[var(--color-danger-500)]">
                        تحذير: هذه العملية ستستبدل قاعدة البيانات الحالية بالكامل ببيانات النسخة المحددة. اكتب اسم الملف بالضبط للمتابعة.
                    </div>

                    <x-ui.input
                        label="اكتب اسم الملف للموافقة"
                        name="confirmFilename"
                        placeholder="backup-....sqlite"
                        dir="ltr"
                        wire:model.live="confirmFilename"
                        :error="$errors->first('confirmFilename')"
                    />

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <x-ui.button variant="ghost" x-on:click="open = false">
                            إلغاء
                        </x-ui.button>
                        <x-ui.button
                            variant="danger"
                            icon="arrow-uturn-left"
                            wire:click="restore"
                            :disabled="$confirmFilename !== $this->backupToRestore->filename"
                        >
                            استعادة الآن
                        </x-ui.button>
                    </div>
                @else
                    <p class="text-sm text-[var(--color-text-muted)]">لم يتم تحديد نسخة.</p>
                @endif
            </div>
        </x-ui.modal>
    @endif
</div>
