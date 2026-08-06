# 🚀 خطة تنفيذ: نظام الإصدارات والنشر

> لوحة إدارة للمستخدمين ذوي دور `admin` لتوثيق إصدارات التطبيق، ومتابعة عمليات النشر لكل بيئة. لا يُنفّذ أي أمر نشر من طلب HTTP مباشرة.

## 1. نطاق النظام

يتكون النظام من جزأين منفصلين:

1. إدارة الإصدارات: إنشاء الإصدار وتوثيق التغييرات واعتماده.
2. عمليات النشر: تشغيل نشر إصدار معتمد إلى بيئة محددة، في Job منفصل، مع تسجيل الحالة والمخرجات.

الإصدار `published` يعني أن الإصدار معتمد للنشر، ولا يعني أن النشر نجح في كل البيئات.

## 2. الصلاحيات والمسارات

المشروع الحالي يستخدم دور `admin` وليس `superadmin`. لذلك تكون صفحات النظام متاحة للأدمن فقط، عبر سياسة أو Middleware مخصص، مع إبقاء `EnsureRouteAccess` ضمن مجموعة المصادقة الحالية.

المسارات المقترحة:

```php
Route::middleware(['auth', EnsureRouteAccess::class, EnsureAdmin::class])
    ->prefix('deployments')
    ->name('deployments.')
    ->group(function () {
        Route::get('/', Deployments\Index::class)->name('index');
        Route::get('/create', Deployments\CreateRelease::class)->name('create');
        Route::get('/{release}', Deployments\ShowRelease::class)->name('show');
    });
```

لا نستخدم `POST` إلى مكوّن Livewire. يطلب المستخدم النشر من `ShowRelease`، وتستدعي المكوّنات `DeploymentService` الذي ينشئ Deployment ويدفع `RunDeploymentJob` إلى الطابور.

## 3. قاعدة البيانات

### جدول `releases`

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | `bigIncrements` | المفتاح الأساسي |
| `version` | `string(20)` | فريد، مثل `v1.2.0` |
| `title` | `string(255)` | مطلوب |
| `description` | `text nullable` | وصف الإصدار |
| `status` | `string(30)` | يتم تحويله إلى `ReleaseStatus` |
| `source_revision` | `string(100) nullable` | Git SHA أو مرجع المصدر |
| `released_at` | `timestamp nullable` | وقت اعتماد الإصدار |
| `created_by` | `foreignId` | إلى `users`, مع `restrictOnDelete` |
| timestamps | — | — |

الفهارس والقيود: unique على `version`، وفهرس على `status` و`released_at`.

### جدول `release_changes`

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | `bigIncrements` | — |
| `release_id` | `foreignId` | `cascadeOnDelete` |
| `type` | `string(30)` | يتم تحويله إلى `ReleaseChangeType` |
| `file_path` | `string(500)` | مسار وصفي فقط، لا يُنفّذ كأمر |
| `description` | `text` | شرح التغيير |
| timestamps | — | — |

### جدول `deployments`

| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | `bigIncrements` | — |
| `release_id` | `foreignId` | `restrictOnDelete` بعد وجود Deployment |
| `environment` | `string(30)` | قيمة من `DeploymentEnvironment` |
| `status` | `string(30)` | قيمة من `DeploymentStatus` |
| `started_at` | `timestamp nullable` | — |
| `completed_at` | `timestamp nullable` | — |
| `source_revision` | `string(100) nullable` | النسخة التي نُشرت فعليًا |
| `failure_reason` | `text nullable` | ملخص آمن للفشل |
| `output_log` | `longText nullable` | دون أسرار أو متغيرات بيئة |
| `created_by` | `foreignId` | إلى `users`, مع `restrictOnDelete` |
| `rolled_back_at` | `timestamp nullable` | — |
| timestamps | — | — |

الفهارس: `release_id`, `environment`, `status`, و`created_at`. يمنع النظام تشغيل Deployment آخر للبيئة نفسها إذا كان هناك واحد بحالة `pending` أو `in_progress`.

لا نستخدم `enum` في قاعدة البيانات؛ نستخدم `string` مع PHP Enums لتسهيل الاختبارات وتغيير القيم مستقبلًا.

## 4. النماذج والـ Enums

### Enums

- `ReleaseStatus`: `Draft`, `Published`, `RolledBack`.
- `ReleaseChangeType`: `Added`, `Modified`, `Fixed`, `Removed`.
- `DeploymentEnvironment`: `Testing`, `Staging`, `Production`.
- `DeploymentStatus`: `Pending`, `InProgress`, `Completed`, `Failed`, `RolledBack`.

### Models

`Release`:

- العلاقات: `changes`, `deployments`, `creator`.
- casts: `status`, `released_at`.
- scopes للحالات.
- تغيير الحالة عبر Service وليس عبر تحديثات مباشرة من Blade.

`ReleaseChange`:

- العلاقة `release`.
- cast لـ `type`.

`Deployment`:

- العلاقات `release` و`creator`.
- casts للتواريخ والحالات والبيئة.
- methods لحساب المدة وتغيير الحالة بشكل منضبط.

## 5. الخدمات والـ Job

### `ReleaseService`

- `create(array $data, array $changes): Release` داخل `DB::transaction()`.
- `publish(Release $release): void` مع منع اعتماد إصدار غير صالح.
- `rollBack(Release $release): void` مع تسجيل وقت التراجع.
- الحذف مسموح للمسودة فقط، ولا يُحذف إصدار له Deployment ناجح؛ يستخدم الأرشفة لاحقًا عند الحاجة.

### `DeploymentService`

- `queue(Release $release, DeploymentEnvironment $environment, User $user): Deployment`.
- التحقق من أن الإصدار `published` وأن البيئة مسموحة.
- منع النشر المتزامن للبيئة نفسها باستخدام lock وقيد تطبيقي.
- إنشاء سجل `pending` ثم دفع `RunDeploymentJob`.
- `markAsCompleted`, `markAsFailed`, و`rollback` مع تحديث السجل بأمان.

### `RunDeploymentJob`

- Job قابل لإعادة المحاولة مع `tries`, `timeout`, و`backoff` محددة.
- يستخدم قائمة أوامر ثابتة ومراجعة مسبقًا عبر Symfony Process.
- يحدد `cwd` وبيئة التشغيل من إعدادات التطبيق، ولا يقبل أوامر أو مسارات من المستخدم.
- يعمل فقط داخل قائمة ملفات ومجلدات مسموحة ومحددة مسبقًا في إعدادات الخادم؛ لا يعتمد على مسار يرسله المستخدم.
- يجب تعريف نطاق مستقل لكل بيئة، مثل `deployment.environments.production.allowed_paths`، ويُرفض أي مسار خارج النطاق قبل بدء الـ Job.
- لا يُسمح باستخدام مسارات مطلقة أو `..` أو الروابط الرمزية التي تخرج عن مجلد المشروع.
- تكون أوامر كل بيئة وقائمة الملفات القابلة للتعديل أو البناء allowlist ثابتة في إعدادات التطبيق، وتُراجع قبل تفعيل production.
- لا يتم تنفيذ `git pull` من واجهة التطبيق؛ يفضّل أن تكون نسخة المصدر جاهزة عبر CI/CD، وأن يقتصر النظام على تشغيل خطوات النشر الآمنة.
- لا تُطبع الأسرار في `output_log`، ويكون حجم السجل محدودًا أو يُنقل إلى تخزين مخصص.
- عند الفشل يُحفظ `failure_reason` وتتحول الحالة إلى `failed`.

مثال تصوري للإعدادات:

```php
'environments' => [
    'staging' => [
        'allowed_paths' => [
            'app', 'config', 'database/migrations', 'resources', 'routes', 'config', 'lang','composer.json'
        ],
        'commands' => ['migrate', 'cache'],
    ],
    'production' => [
        'allowed_paths' => [
            'app', 'config', 'database/migrations', 'resources', 'routes','config', 'lang','composer.json'
        ],
        'commands' => ['migrate', 'cache'],
    ],
],
```

القائمة السابقة مثال وليست قيمة نهائية؛ تُحدد بعد مراجعة البنية التحتية. ويجب استثناء `.env`, مفاتيح التطبيق، ملفات الأسرار، وملفات النظام من أي عملية يطلقها النظام.

الأوامر المحتملة بعد اعتماد البنية التحتية:

```text
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

يجب تحديد هل `npm run build` يتم في CI/CD أم على خادم التطبيق، وعدم افتراض وجود Node.js في الإنتاج.

## 6. مكونات Livewire

### `App\Livewire\Deployments\Index`

- عرض الإصدارات مع pagination وفلتر الحالة.
- eager load لعدد التغييرات وعمليات النشر لتجنب N+1.
- حذف المسودات فقط بعد authorization.

### `App\Livewire\Deployments\CreateRelease`

- إنشاء الإصدار والتغييرات ديناميكيًا.
- قواعد التحقق:
  - `version`: `required|string|max:20|unique:releases,version`.
  - `title`: `required|string|max:255`.
  - `description`: `nullable|string`.
  - `changes`: `required|array|min:1`.
  - كل تغيير يتطلب `type`, `file_path`, و`description`.
- إنشاء الإصدار عبر `ReleaseService` فقط.

### `App\Livewire\Deployments\ShowRelease`

- عرض الإصدار مع `changes`, `deployments`, و`creator`.
- اعتماد الإصدار، التراجع، وفتح تأكيد النشر.
- اختيار البيئة ثم استدعاء `DeploymentService::queue()`.
- عرض حالة النشر وتحديثها دوريًا عند الحاجة، دون تشغيل الأوامر داخل المكوّن.

لا حاجة إلى `RunDeployment` كمكوّن مستقل في البداية؛ يمكن تنفيذ مودال اختيار البيئة داخل `ShowRelease`. إذا أصبح معقدًا، يستخرج لاحقًا كمكوّن عرض فقط.

## 7. الملاحة والإشعارات

- إضافة قسم داخل `App\Support\Navigation.php` بوضوح `visibility => admin`.
- عدم إضافة صلاحية للمستخدمين العاديين في `permissionOptions()` إلا إذا تقرر لاحقًا دعم صلاحية مستقلة.
- استخدام نظام الإشعارات الموجود في المشروع، مع توحيد payload قبل إضافة رسائل جديدة.
- استخدام `wire:confirm` للإجراءات الحساسة، مع authorization داخل action أيضًا؛ التأكيد في الواجهة ليس حماية.

## 8. مؤشر تقدم النشر والتحديث الحي

يجب أن يظهر للمستخدم مؤشر تقدم حقيقي أثناء النشر، دون إبقاء طلب HTTP مفتوحًا ودون تجميد الصفحة.

### التصميم المعتمد

- يبدأ النشر عبر Livewire action سريع ينشئ سجل `Deployment` بحالة `pending` ويدفع `RunDeploymentJob` إلى الطابور ثم يعود فورًا.
- يتولى Job تنفيذ خطوات النشر بشكل منفصل عن دورة طلب المتصفح.
- يسجل Job تقدم كل خطوة في جدول `deployment_steps` بدل تخمين النسبة من الوقت المنقضي.
- يحتوي كل سجل خطوة على الأقل على:
  - `deployment_id`
  - `key`
  - `label`
  - `status`: `pending/in_progress/completed/failed/skipped`
  - `started_at`, `completed_at`
  - `output` آمن ومحدود الحجم
  - `sort_order`
- يحسب التطبيق النسبة من عدد الخطوات المكتملة والـ skipped من إجمالي الخطوات، مع اعتبار الفشل حالة نهائية لا نسبة نجاح.
- يعرض `ShowRelease` النسبة والخطوة الحالية وسجل الخطوات ورسالة الفشل إن وجدت.

### طريقة تحديث الواجهة

- يبدأ Livewire polling متوقفًا، ثم يعمل كل 2–3 ثوانٍ أثناء وجود Deployment بحالة `pending` أو `in_progress`.
- يتوقف polling تلقائيًا عند `completed`, `failed`, أو `rolled_back`.
- لا يستخدم polling تنفيذ الأوامر ولا يعيد تشغيل الـ Job؛ هو قراءة لحالة محفوظة فقط.
- يمكن استبدال polling لاحقًا بـ SSE أو WebSockets إذا احتاج النظام تحديثًا لحظيًا لعدد كبير من المستخدمين، دون تغيير دورة النشر نفسها.
- لا يُعتمد على Ajax يدوي أو على إبقاء الطلب الأصلي مفتوحًا؛ Livewire ينفذ طلبات القراءة القصيرة تلقائيًا.

### التعامل مع المدة والأخطاء

- كل خطوة Process لها timeout مستقل، والـ Job له timeout أكبر من مجموع الحدود المتوقعة.
- يستخدم الطابور `tries`, `backoff`, و`WithoutOverlapping` أو lock مناسبًا للبيئة.
- إذا انقطع المتصفح أو أغلق المستخدم الصفحة يستمر Job، ويمكن العودة لاحقًا لرؤية الحالة من قاعدة البيانات.
- إذا توقف Worker دون تحديث الحالة، توجد آلية مراقبة/cleanup تحول السجل العالق إلى `failed` بعد مدة محددة مع رسالة واضحة.
- يظهر للمستخدم أن النسبة تقديرية فقط إذا كانت هناك خطوة لا يمكن قياس تقدمها داخليًا؛ أما خطوات النظام فتُعرض بناءً على حالتها الفعلية.

### معايير قبول مؤشر التقدم

- بدء النشر لا يتجاوز زمن طلب عادي ولا ينتظر انتهاء الأوامر.
- الصفحة تبقى قابلة للتفاعل أثناء النشر.
- تتغير حالة الخطوات فعليًا من قاعدة البيانات، وليس عبر عداد JavaScript وهمي.
- تظهر حالات `pending`, `in_progress`, `completed`, `failed`, و`rolled_back` بوضوح.
- تحديث الصفحة أو إغلاقها ثم فتحها مجددًا يعرض آخر حالة صحيحة.
- لا ينتج عن polling خطأ `request timeout` ولا تتراكم طلبات متزامنة.

## 9. الاختبارات

كل مرحلة يجب أن تحتوي على اختبار PHPUnit مناسب:

- migrations والقيود الأساسية.
- إنشاء Release مع Changes داخل transaction.
- uniqueness للإصدار والتحقق من الحقول.
- منع المستخدم العادي وfieldworker من الوصول.
- السماح للأدمن فقط.
- منع اعتماد أو نشر Draft.
- منع Deployment متزامن للبيئة نفسها.
- إنشاء Deployment بحالة `pending` ودفع Job.
- نجاح وفشل Job مع تحديث السجل.
- rollback وتسجيل المنفذ والوقت.
- عدم تشغيل أوامر حقيقية في الاختبارات؛ استخدام fake/mock للـ Process والـ Queue.
- إنشاء خطوات النشر بالترتيب الصحيح وتحديثها مع كل مرحلة.
- تحقق Livewire من بدء polling وإيقافه عند الحالة النهائية.
- استمرار ظهور الحالة الصحيحة بعد إعادة تحميل الصفحة أو انقطاع المتصفح.
- اختبارات Livewire للتدفقات الأساسية والرسائل وحالات الصلاحيات.

## 10. مراحل التنفيذ

### المرحلة 1 — التوافق والبنية

- [ ] تثبيت قرار استخدام دور `admin`.
- [ ] تصميم Middleware/Policy للأدمن.
- [ ] إنشاء Enums وmigrations والفهارس والقيود.
- [ ] إنشاء Models وFactories.

### المرحلة 2 — إدارة الإصدارات

- [ ] إنشاء `ReleaseService`.
- [ ] إنشاء مكونات Index/Create/Show.
- [ ] إضافة المسارات والملاحة والترجمات.
- [ ] اختبار الإنشاء والاعتماد والتراجع والحذف الآمن.

### المرحلة 3 — سجل النشر دون تنفيذ أوامر

- [ ] إنشاء `DeploymentService`.
- [ ] إنشاء سجل pending وتدفق الحالات.
- [ ] إضافة اختيار البيئة وعرض سجل العمليات.
- [ ] اختبار التزامن والصلاحيات.

### المرحلة 4 — التنفيذ غير المتزامن

- [ ] تحديد البنية التحتية ومكان تنفيذ الأوامر.
- [ ] إنشاء جدول `deployment_steps` وEnums الخاصة بحالات الخطوات.
- [ ] إنشاء `RunDeploymentJob` وProcess runner مقيد.
- [ ] تسجيل تقدم كل خطوة وتحديث النسبة من الحالة الفعلية.
- [ ] إضافة timeout وretry وlogging آمن.
- [ ] تعريف allowlist للملفات والمجلدات والأوامر لكل بيئة.
- [ ] اختبار رفض المسارات المطلقة و`..` والروابط الرمزية الخارجة عن النطاق.
- [ ] اختبار fake للـ Process والـ Queue.

### المرحلة 5 — مؤشر التقدم

- [ ] إضافة polling قصير وآمن داخل `ShowRelease`.
- [ ] عرض الشريط والخطوة الحالية وسجل الخطوات ورسائل الفشل.
- [ ] إيقاف polling تلقائيًا عند الحالة النهائية.
- [ ] اختبار إغلاق الصفحة وإعادة فتحها أثناء النشر.

### المرحلة 6 — التشغيل التجريبي

- [ ] تجربة على testing ثم staging.
- [ ] التحقق من rollback وخطة الاستعادة.
- [ ] مراجعة السجلات والصلاحيات.
- [ ] عدم تفعيل production قبل اعتماد قائمة الأوامر ومكان تشغيلها.

## 11. معايير القبول

- لا يستطيع غير الأدمن الوصول إلى أي صفحة أو action في النظام.
- لا يمكن نشر إصدار غير `published`.
- لا ينفذ أي أمر نشر داخل دورة طلب HTTP.
- لكل Deployment سجل واضح للحالة والبيئة والمنفذ والوقت والنتيجة.
- لا تتسرب الأسرار إلى السجلات.
- لا يمكن لعملية النشر التعامل مع ملف أو مجلد خارج allowlist الخاصة بالبيئة.
- لا يمكن تمرير أوامر أو مسارات مخصصة من واجهة المستخدم إلى Process.
- يظهر مؤشر تقدم مبني على حالة خطوات حقيقية، ولا يعتمد على طلب HTTP طويل أو عداد وهمي.
- لا تتجمد الصفحة ولا ينتهي الطلب بسبب طول عملية النشر.
- جميع الاختبارات المتعلقة بالميزة ناجحة، مع تشغيل Pint لأي ملفات PHP معدلة.
