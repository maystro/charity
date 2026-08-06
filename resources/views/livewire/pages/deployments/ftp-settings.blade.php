<div class="space-y-6">
    <x-layout.page-header
        title="إعدادات النشر"
        subtitle="بيانات الاتصال بالسيرفر (cPanel / FTP) — تُحفظ مشفّرة وتُستخدم عند النشر التلقائي."
        :breadcrumbs="[
            ['label' => 'الإصدارات والنشر', 'route' => 'deployments.index'],
            ['label' => 'إعدادات النشر'],
        ]"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="server" wire:click="testConnection" :loading="$testing">
                <span wire:loading.remove wire:target="testConnection">اختبار الاتصال</span>
                <span wire:loading wire:target="testConnection">جارٍ الاتصال…</span>
            </x-ui.button>
            <x-ui.button variant="primary" icon="check" wire:click="save" :loading="$saving">
                <span wire:loading.remove wire:target="save">حفظ الإعدادات</span>
                <span wire:loading wire:target="save">جارٍ الحفظ…</span>
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.alert variant="info" :dismissible="false">
        زرار «نشر الآن» سيرفع كل الملفات المتغيرة إلى السيرفر عبر FTP ثم يشغّل الهجرات وتهيئة التطبيق تلقائيًا.
        كلمة المرور تُخزَّن مشفّرة في قاعدة البيانات ولا تظهر أبدًا بعد الحفظ.
    </x-ui.alert>

    @if($errors->any())
        <x-ui.alert variant="danger" :dismissible="true">
            اكتمل التحقق من البيانات: يوجد {{ $errors->count() }} {{ $errors->count() > 1 ? 'أخطاء' : 'خطأ' }} يجب تصحيحها قبل الحفظ أو الاختبار.
        </x-ui.alert>
    @endif

    @if($testResult)
        <x-ui.alert :variant="$testOk ? 'success' : 'danger'" :dismissible="true">
            {{ $testResult }}
        </x-ui.alert>
    @endif

    <x-ui.card padding>
        <div class="max-w-xl space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.input
                    label="السيرفر (Host)"
                    name="host"
                    wire:model="host"
                    placeholder="ftp.example.com"
                    icon="globe-alt"
                    :error="$errors->first('host')"
                    required
                />
                <x-ui.input
                    label="المنفذ (Port)"
                    name="port"
                    wire:model="port"
                    type="number"
                    placeholder="21"
                    icon="hashtag"
                    :error="$errors->first('port')"
                    required
                />
            </div>

            <x-ui.input
                label="اسم المستخدم"
                name="username"
                wire:model="username"
                placeholder="user@example.com"
                icon="user"
                :error="$errors->first('username')"
                required
            />

            <x-ui.input
                label="كلمة المرور"
                name="password"
                wire:model="password"
                type="password"
                placeholder="{{ $hasSavedPassword ? 'محفوظة — اتركها فارغة للإبقاء عليها' : 'كلمة المرور' }}"
                icon="key"
                :error="$errors->first('password')"
                :hint="$hasSavedPassword ? 'توجد كلمة مرور محفوظة. اترك الحقل فارغًا عند الحفظ لإبقائها.' : null"
            />

            <x-ui.input
                label="مجلد الرفع على السيرفر"
                name="rootPath"
                wire:model="rootPath"
                placeholder="/public_html"
                icon="folder"
                :error="$errors->first('rootPath')"
                hint="المسار داخل حساب الاستضافة الذي تُرفع إليه الملفات (مثل /public_html)."
            />
        </div>
    </x-ui.card>

    <x-ui.card padding>
        <h2 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">كيف يعمل النشر التلقائي؟</h2>
        <ol class="list-decimal list-inside space-y-1.5 text-sm text-[var(--color-text-secondary)]">
            <li>تضغط «نشر الآن» من صفحة الإصدار.</li>
            <li>يرفع النظام كل الملفات المتغيرة في الإصدار إلى السيرفر عبر FTP.</li>
            <li>يحذف الملفات المُعلَّمة كمحذوفة من السيرفر.</li>
            <li>يمسح كاش السيرفر (<span dir="ltr">bootstrap/cache</span> والفيوهات المترجمة) تلقائيًا حتى يظهر التحديث فورًا.</li>
            <li>يشغّل هجرات قاعدة البيانات ثم تهيئة التطبيق.</li>
            <li>يمكنك متابعة كل خطوة لحظة بلحظة من صفحة الإصدار.</li>
        </ol>
    </x-ui.card>
</div>
