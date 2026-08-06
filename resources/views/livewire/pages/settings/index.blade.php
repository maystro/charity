<div class="space-y-6">
    {{-- Page Header --}}
    <x-layout.page-header
        title="إعدادات النظام"
        subtitle="إدارة الإعدادات العامة للنظام"
    />

    {{-- Families Settings --}}
    <x-ui.card padding>
        <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-5">إعدادات الأسر والتقييم</h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4 border border-[var(--color-border)] rounded-[var(--radius-md)] p-4">
                <div>
                    <p class="font-medium text-[var(--color-text-primary)]">فترة إعادة التقييم</p>
                    <p class="text-sm text-[var(--color-text-muted)] mt-1">عدد الشهور التي بعدها يجب إعادة تقييم الأسرة المعتمدة</p>
                </div>
                <div class="w-32 shrink-0">
                    <x-ui.input
                        type="number"
                        name="reassessment_interval_months"
                        wire:model="reassessmentIntervalMonths"
                        min="1"
                        max="24"
                        size="sm"
                    />
                </div>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-end">
            <x-ui.button variant="primary" type="button" wire:click="save" :loading="$wire->submitting ?? false">
                حفظ الإعدادات
            </x-ui.button>
        </div>
    </x-ui.card>

    @if(auth()->user()?->isAdmin())
        <x-ui.card padding>
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">نموذج البيانات التجريبية</h2>
                    <p class="text-sm text-[var(--color-text-muted)] leading-relaxed">
                        إنشاء بيانات عرض مترابطة: 10 أسر، 10 طلبات مساعدة، 10 مشروعات، 10 متبرعين، و10 مندوبين/باحثين.
                        يمكنك حذفها لاحقًا لإفراغ بيانات العرض من القاعدة.
                    </p>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                <button
                    type="button"
                    wire:click="seedDemoData"
                    wire:loading.attr="disabled"
                    wire:target="seedDemoData"
                    class="inline-flex items-center justify-center gap-2 rounded-[var(--radius-md)] bg-[var(--accent-500)] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-[var(--accent-600)] disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-[var(--accent-500)]"
                >
                    <span wire:loading.remove wire:target="seedDemoData">إنشاء نموذج البيانات</span>
                    <span wire:loading wire:target="seedDemoData">جارٍ الإنشاء...</span>
                </button>

                <button
                    type="button"
                    wire:click="deleteDemoData"
                    wire:confirm="سيتم حذف جميع بيانات العرض التجريبية. هل تريد المتابعة؟"
                    wire:loading.attr="disabled"
                    wire:target="deleteDemoData"
                    class="inline-flex items-center justify-center gap-2 rounded-[var(--radius-md)] border border-[var(--color-danger-200)] bg-[var(--color-danger-50)] px-5 py-2.5 text-sm font-medium text-[var(--color-danger-700)] transition-colors hover:bg-[var(--color-danger-100)] disabled:cursor-not-allowed disabled:opacity-60"
                >
                    حذف نموذج البيانات
                </button>
            </div>
        </x-ui.card>
    @endif
</div>
