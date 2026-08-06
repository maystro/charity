<div class="space-y-6">
    <x-layout.page-header
        title="الصيانة"
        subtitle="أدوات سريعة لتنظيف الكاش وتنفيذ صيانة أساسية من لوحة الإدارة التقنية"
    >
        <x-slot:actions>
            <x-ui.button
                variant="secondary"
                icon="trash"
                wire:click="clearCaches"
                wire:loading.attr="disabled"
                wire:target="clearCaches"
                :loading="$isClearingCache"
            >
                حذف الكاش
            </x-ui.button>
            <x-ui.button
                variant="primary"
                icon="arrow-path"
                wire:click="updatePackages"
                wire:loading.attr="disabled"
                wire:target="updatePackages"
                :loading="$isUpdatingPackages"
            >
                تحديث الحزم
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if($errorMessage)
        <x-ui.alert variant="danger" :dismissible="false">
            {{ $errorMessage }}
        </x-ui.alert>
    @endif

    @if($statusMessage)
        <x-ui.alert variant="success" :dismissible="false">
            {{ $statusMessage }}
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-ui.card padding>
            <div class="space-y-3">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">حذف الكاش</h2>
                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                    ينفذ هذا الإجراء <code class="font-mono text-xs">optimize:clear</code> لمسح كاش Laravel والملفات المؤقتة المرتبطة بالتجهيز.
                </p>
                <ul class="space-y-2 text-sm text-[var(--color-text-secondary)]">
                    <li>مسح كاش التطبيق.</li>
                    <li>مسح كاش الإعدادات والمسارات والعروض.</li>
                    <li>إعادة تجهيز البيئة من جديد دون لمس البيانات.</li>
                </ul>
            </div>
        </x-ui.card>

        <x-ui.card padding>
            <div class="space-y-3">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">تحديث الحزم</h2>
                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                    يحاول تنفيذ <code class="font-mono text-xs">composer update</code> من داخل المشروع. إذا كانت البيئة لا تسمح بذلك أو ظهرت تعارضات في الاعتماديات فسيظهر الخطأ هنا.
                </p>
                <ul class="space-y-2 text-sm text-[var(--color-text-secondary)]">
                    <li>مناسب لتنفيذ التحديثات عندما تكون الصلاحيات وأدوات Composer متاحة.</li>
                    <li>قد يستغرق وقتًا أطول من حذف الكاش.</li>
                    <li>يُفضّل مراجعة الناتج قبل الاعتماد عليه في الإنتاج.</li>
                </ul>
            </div>
        </x-ui.card>
    </div>
</div>
