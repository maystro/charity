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
                variant="secondary"
                icon="link"
                wire:click="createStorageLink"
                wire:loading.attr="disabled"
                wire:target="createStorageLink"
                :loading="$isLinkingStorage"
            >
                إنشاء رابط التخزين
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

        <x-ui.card padding>
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">رابط التخزين</h2>
                    <x-ui.badge :variant="$storageLinkExists ? 'success' : 'danger'" dot size="sm">
                        {{ $storageLinkExists ? 'الرابط موجود' : 'الرابط غير موجود' }}
                    </x-ui.badge>
                </div>
                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                    تُعرض الملفات المرفوعة (الشعار، صور المندوبين، المرفقات...) تلقائيًا عبر مسار
                    <code class="font-mono text-xs" dir="ltr">/media</code>
                    الذي يقدّمه النظام مباشرة من
                    <code class="font-mono text-xs" dir="ltr">storage/app/public</code>
                    — دون الحاجة لأي رابط رمزي أو أوامر نظام (مناسب للاستضافة المشتركة المقفلة مثل Hostinger).
                </p>
                <ul class="space-y-2 text-sm text-[var(--color-text-secondary)]">
                    <li>هذا الزر اختياري: يحسّن الأداء فقط بجعل الملفات تُقدَّم مباشرة من مجلد public.</li>
                    <li>لا يحذف أي بيانات أو ملفات موجودة.</li>
                    <li>على الاستضافات التي تمنع symlink تبقى الملفات تعمل عبر /media دون هذا الرابط.</li>
                </ul>
                <div class="pt-1">
                    <x-ui.button
                        variant="secondary"
                        size="sm"
                        icon="link"
                        wire:click="createStorageLink"
                        wire:loading.attr="disabled"
                        wire:target="createStorageLink"
                        :loading="$isLinkingStorage"
                    >
                        {{ $storageLinkExists ? 'إعادة إنشاء الرابط' : 'إنشاء الرابط' }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
