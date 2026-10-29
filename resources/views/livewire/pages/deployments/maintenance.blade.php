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
                variant="secondary"
                icon="arrow-path"
                wire:click="updatePackages"
                wire:loading.attr="disabled"
                wire:target="updatePackages"
                :loading="$isUpdatingPackages"
            >
                تحديث الحزم
            </x-ui.button>
            <x-ui.button
                :variant="$freshMode ? 'danger' : 'primary'"
                icon="circle-stack"
                wire:click="startMigrations"
                wire:loading.attr="disabled"
                wire:target="startMigrations,runMigrations,runFreshMigrations"
                :loading="$isRunningMigrations"
            >
                {{ $freshMode ? 'إعادة تهيئة (fresh + seed)' : 'تشغيل المايجريشن' }}
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if($errorMessage)
        <x-ui.alert variant="danger" :dismissible="false" class="whitespace-pre-line">
            {{ $errorMessage }}
        </x-ui.alert>
    @endif

    @if($statusMessage)
        <x-ui.alert variant="success" :dismissible="false" class="whitespace-pre-line">
            {{ $statusMessage }}
        </x-ui.alert>
    @endif

    @if($migrationOutput)
        <x-ui.card padding>
            <div class="space-y-3">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">ناتج المايجريشن</h2>
                <pre dir="ltr" class="text-xs leading-relaxed bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-[var(--radius-md)] p-4 overflow-x-auto whitespace-pre-wrap font-mono text-[var(--color-text-secondary)]">{{ $migrationOutput }}</pre>
            </div>
        </x-ui.card>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-ui.card padding class="h-full">
            <div class="flex h-full flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">تشغيل المايجريشن</h2>
                    @if($this->pendingMigrationsCount !== null)
                        <x-ui.badge :variant="$this->pendingMigrationsCount > 0 ? 'warning' : 'success'" dot size="sm">
                            {{ $this->pendingMigrationsCount > 0 ? $this->pendingMigrationsCount.' معلّق' : 'محدّث' }}
                        </x-ui.badge>
                    @endif
                </div>
                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                    ينفذ <code class="font-mono text-xs" dir="ltr">php artisan migrate --force</code>
                    داخل التطبيق نفسه — دون طرفية أو SSH أو <code class="font-mono text-xs" dir="ltr">exec()</code>،
                    لذلك يعمل على الاستضافة المشتركة المقفلة مثل Hostinger.
                </p>
                <ul class="space-y-2 text-sm text-[var(--color-text-secondary)]">
                    <li>ينشئ الجداول والأعمدة الناقصة فقط، ولا يحذف أي بيانات موجودة.</li>
                    <li>يُستخدم <code class="font-mono text-xs" dir="ltr">--force</code> لتجاوز تأكيد بيئة الإنتاج.</li>
                    <li>بعد رفع تحديث يحتوي ملفات هجرة جديدة، شغّل هذا الزر مرة واحدة.</li>
                </ul>

                <div class="pt-2 border-t border-[var(--color-border)]">
                    <x-ui.switch
                        wire:model.live="freshMode"
                        label="وضع إعادة التهيئة الكاملة (fresh + seed)"
                        size="md"
                    />
                    <p class="mt-2 text-xs text-[var(--color-text-muted)] leading-relaxed">
                        عند تفعيله يتحوّل نفس الزر إلى
                        <code class="font-mono text-xs" dir="ltr">migrate:fresh --seed</code>:
                        يحذف كل الجداول ثم يعيد إنشاءها ويشغّل البذور.
                    </p>
                </div>

                @if($freshMode)
                    <x-ui.alert variant="danger" :dismissible="false">
                        تحذير: الزر الآن سيحذف جميع الجداول والبيانات نهائيًا ثم يعيد تشغيل البذور.
                        البذور الحالية تتضمن بيانات عرض وحسابات افتراضية (admin / superadmin / agent بكلمة المرور password)،
                        لذلك لا تستخدمه على قاعدة بيانات إنتاج تحتوي بيانات حقيقية.
                    </x-ui.alert>
                @endif

                <div class="mt-auto flex justify-end pt-3">
                    <x-ui.button
                        :variant="$freshMode ? 'danger' : 'primary'"
                        size="sm"
                        icon="circle-stack"
                        wire:click="startMigrations"
                        wire:loading.attr="disabled"
                        wire:target="startMigrations,runMigrations,runFreshMigrations"
                        :loading="$isRunningMigrations"
                    >
                        {{ $freshMode ? 'إعادة تهيئة قاعدة البيانات' : 'تشغيل المايجريشن الآن' }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card padding class="h-full">
            <div class="flex h-full flex-col gap-3">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">الكاش</h2>
                <div class="flex flex-1 flex-col justify-center gap-3">
                    <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                        <strong>حذف الكاش:</strong> يمسح كاش الإطار (الإعدادات، المسارات، العروض، الأحداث) وكاش الأيقونات.
                        <strong>بناء الكاش:</strong> يعيد بناء ذلك الكاش جميعًا لتسريع الموقع.
                        الترتيب بعد النشر: حذف ← بناء.
                    </p>

                    <div class="rounded-[var(--radius-md)] border border-[var(--color-border)] bg-[var(--color-bg-secondary)] p-3 text-xs leading-relaxed text-[var(--color-text-secondary)]">
                        <p>
                            <span class="font-semibold text-[var(--color-text-primary)]">حذف الكاش:</span>
                            <code class="font-mono" dir="ltr">optimize:clear</code>
                            (مسح كاش الإطار كاملًا) ثم
                            <code class="font-mono" dir="ltr">icons:clear</code>
                            (مسح manifest الأيقونات).
                        </p>
                        <p class="mt-2">
                            <span class="font-semibold text-[var(--color-text-primary)]">بناء الكاش:</span>
                            <code class="font-mono" dir="ltr">config:cache</code> →
                            <code class="font-mono" dir="ltr">route:cache</code> →
                            <code class="font-mono" dir="ltr">view:cache</code> →
                            <code class="font-mono" dir="ltr">event:cache</code> →
                            <code class="font-mono" dir="ltr">icons:cache</code>
                        </p>
                    </div>
                </div>

                <div class="mt-auto grid grid-cols-2 gap-3 pt-3">
                    <x-ui.button
                        variant="secondary"
                        size="sm"
                        icon="trash"
                        class="w-full"
                        wire:click="clearCaches"
                        wire:loading.attr="disabled"
                        wire:target="clearCaches"
                        :loading="$isClearingCache"
                    >
                        حذف الكاش
                    </x-ui.button>

                    <x-ui.button
                        variant="secondary"
                        size="sm"
                        icon="bolt"
                        class="w-full"
                        wire:click="buildCaches"
                        wire:loading.attr="disabled"
                        wire:target="buildCaches"
                        :loading="$isBuildingCache"
                    >
                        بناء الكاش
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card padding class="h-full">
            <div class="flex h-full flex-col gap-3">
                <h2 class="text-lg font-semibold text-[var(--color-text-primary)]">تحديث الحزم</h2>
                <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">
                    يُنفّذ <code class="font-mono text-xs" dir="ltr">composer update</code> من جذر المشروع — خارج
                    <code class="font-mono text-xs" dir="ltr">public</code> — ثم يُنظّف الكاش تلقائيًا.
                    إن تعذّر التشغيل على الاستضافة، البديل مهمة Cron أو رفع مجلد
                    <code class="font-mono text-xs" dir="ltr">vendor</code> جاهزًا. لا تغلق الصفحة أثناء التنفيذ.
                </p>

                <div class="mt-auto flex justify-end pt-3">
                    <x-ui.button
                        variant="secondary"
                        size="sm"
                        icon="arrow-path"
                        wire:click="updatePackages"
                        wire:loading.attr="disabled"
                        wire:target="updatePackages"
                        :loading="$isUpdatingPackages"
                    >
                        تحديث الحزم الآن
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card padding class="h-full">
            <div class="flex h-full flex-col gap-3">
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
                <div class="mt-auto flex justify-end pt-3">
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

    <x-ui.confirm-modal
        name="confirm-fresh-migrations"
        title="تأكيد إعادة تهيئة قاعدة البيانات"
        message="سيتم حذف جميع الجداول والبيانات نهائيًا، ثم إعادة إنشاء البنية وتشغيل البذور (بيانات عرض وحسابات افتراضية). لا يمكن التراجع عن هذا الإجراء."
        confirm-text="حذف كل البيانات والمتابعة"
        cancel-text="إلغاء"
        variant="danger"
        action="$wire.runFreshMigrations()"
    />
</div>
