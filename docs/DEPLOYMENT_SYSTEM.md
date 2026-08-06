# 🚀 نظام الإصدارات والنشر — التوثيق الشامل النهائي

> **الإصدار**: v1.0 — **الحالة**: مُنفّذ بالكامل ومُتحقق منه (أغسطس 2026)
> **الغرض**: توثيق كامل لنظام «الإصدارات والنشر» كما نُفّذ في مشروع منصة «الخيرية»، مع أحدث التوصيات والنتائج، ليكون **مرجعًا جاهزًا للتنفيذ المباشر في أي مشروع Laravel آخر**.

---

## جدول المحتويات

1. [المشكلة والهدف](#1-المشكلة-والهدف)
2. [الفكرة الأساسية (دون Git)](#2-الفكرة-الأساسية-دون-git)
3. [البنية العامة](#3-البنية-العامة)
4. [قاعدة البيانات](#4-قاعدة-البيانات)
5. [الطبقات والملفات بالتفصيل](#5-الطبقات-والملفات-بالتفصيل)
6. [فصل السوبر أدمن (منطقة تقنية مستقلة)](#6-فصل-السوبر-أدمن)
7. [إعدادات config/deployment.php](#7-إعدادات-configdeploymentphp)
8. [التدفق الكامل (Flow)](#8-التدفق-الكامل-flow)
9. [النتائج والتحقق](#9-النتائج-والتحقق)
10. [الدرس المستفادة والتوصيات النهائية](#10-الدروس-المستفادة-والتوصيات-النهائية)
11. [خطوات التنفيذ في مشروع جديد (Checklist)](#11-خطوات-التنفيذ-في-مشروع-جديد-checklist)
12. [معلومات الخادم والرفع](#12-معلومات-الخادم-والرفع)
13. [مخاطر محذرة وملاحظات مهمة](#13-مخاطر-محذرة-وملاحظات-مهمة)

---

## 1. المشكلة والهدف

### المشكلة (بكلمات المستخدم)
> «اني لما اعدل حاجة في الكود او احدث حاجة او اضيف ميزة — بدل ما افضل ادور على الملفات المحدثة واخذ وقت في رفع الملفات كلها كل مرة بعمل فيها تحديث!!!»

- المشروع **ليس Git** — لا توجد طريقة لتتبع التغييرات أو معرفة الملفات المعدّلة.
- الرفع إلى استضافة مشتركة (cPanel) يتم يدويًا عبر FTP.
- في كل تحديث كان يضيع وقت طويل في البحث عن الملفات المحدثة ورفعها يدويًا.

### الحل المطوّر
نظام متكامل بجزأين:

| الجزء | الوظيفة |
|---|---|
| **إدارة الإصدارات** | إنشاء إصدار (Release) وتوثيق التغييرات معه، مع **كشف تلقائي** للملفات المتغيرة. |
| **حزمة الرفع ZIP** | توليد ملف ZIP واحد يحتوي كل الملفات المتغيرة — يُرفع مرة واحدة إلى cPanel. |
| **عمليات النشر** | تشغيل نشر إصدار معتمد إلى بيئة (اختباري/تجريبي/إنتاجي) في **Job منفصل** مع تسجيل الخطوات والمخرجات. |

### الأهداف الأمنية
- لا يُنفّذ أي أمر نشر من طلب HTTP مباشرة — دائمًا عبر Queue.
- فقط أوامر من قائمة بيضاء (allowlist) تصل إلى طبقة العمليات.
- الفصل الكامل للسوبر أدمن (منطقة تقنية مستقلة) عن مدير المشروع الخيري.

---

## 2. الفكرة الأساسية (دون Git)

بما أن المشروع ليس Git، بُنيت آلية **Snapshot المقارنة**:

```
┌─────────────────────────────────────────────────────────┐
│  عند إنشاء إصدار (Release):                              │
│  نأخذ لقطة كاملة للمشروع: { file_path => md5(content) } │
│  تُحفظ في عمود file_snapshot (JSON) في جدول releases     │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  عند إنشاء الإصدار التالي:                               │
│  1) نمسح المشروع الحالي (نفس الأسلوب)                    │
│  2) نقارن اللقطة السابقة بالحالية:                       │
│     - ملف جديد          → added                          │
│     - hash مختلف        → modified                       │
│     - موجود سابقًا فقط  → removed                        │
│  3) النتيجة تملأ نموذج التغييرات تلقائيًا (زر استيراد)    │
└─────────────────────────────────────────────────────────┘
```

### الاستثناءات الافتراضية (`ProjectSnapshot::DEFAULT_EXCLUDES`)
```
.git, .idea, .vscode, node_modules, vendor, storage,
bootstrap/cache, .env, .env.example, .env.testing,
.DS_Store, Thumbs.db,
database/database.sqlite, database/database.sqlite-journal
```

> ⚠️ **ملاحظة مهمة**: `public/build` **مضمّن** في اللقطات (قرار المستخدم)، لأن ملفات Vite تُبنى محليًا ويجب رفعها للخادم. كشف ملفات `public/build` الجديدة يعمل تلقائيًا.

---

## 3. البنية العامة

### الأدوار (Roles)
| الدور | القيمة | الوصول |
|---|---|---|
| `admin` | `admin` | لوحة الخيرية كاملة — **بدون** منطقة النشر |
| `super_admin` | `super_admin` | **فقط** منطقة «الإدارة التقنية» (الإصدارات والنشر) |
| `fieldworker` | `fieldworker` | العامل الميداني |
| `user` | `user` | مستخدم عادي |

### المسارات (Routes)
```php
// bootstrap/app.php
$middleware->alias([
    'admin' => EnsureAdmin::class,
    'super_admin' => EnsureSuperAdmin::class,
]);

// routes/web.php — منطقة النشر (سوبر أدمن فقط)
Route::middleware('super_admin')
    ->prefix('superadmin-dashboard')
    ->name('deployments.')
    ->group(function () {
        Route::get('/', Deployments\Index::class)->name('index');
        Route::get('/create', Deployments\CreateRelease::class)->name('create');
        Route::get('/{release}', Deployments\ShowRelease::class)->name('show');
    });
```

| المسار | الاسم | الصفحة |
|---|---|---|
| `/superadmin-dashboard` | `deployments.index` | قائمة الإصدارات |
| `/superadmin-dashboard/create` | `deployments.create` | إنشاء إصدار جديد |
| `/superadmin-dashboard/{release}` | `deployments.show` | تفاصيل الإصدار + النشر + حزمة ZIP |

### الحماية بثلاث طبقات (للأدمن العادي لا يصل)
1. **Middleware** `super_admin` → `EnsureSuperAdmin` (abort 403 لمن ليس سوبر أدمن).
2. **Navigation::canAccessRoute** → تُمنع مسارات النشر من شريط الأدمن العادي، ومن أي توجيه.
3. **مكوّنات Livewire نفسها**: `abort_unless(auth()->user()?->isSuperAdmin(), 403);` في `mount()`.

### مكوّنات Livewire (نظام MFC — Class-based)
| المكوّن | الملف | الوظيفة |
|---|---|---|
| `Deployments\Index` | `app/Livewire/Deployments/Index.php` | قائمة الإصدارات + حالات النشر |
| `Deployments\CreateRelease` | `app/Livewire/Deployments/CreateRelease.php` | إنشاء إصدار + استيراد التغييرات تلقائيًا |
| `Deployments\ShowRelease` | `app/Livewire/Deployments/ShowRelease.php` | التفاصيل + نشر + حزمة ZIP |

### العروض (Views)
```
resources/views/livewire/pages/deployments/
├── index.blade.php   → قائمة الإصدارات
├── create.blade.php  → نموذج الإنشاء + زر «استيراد التغييرات تلقائيًا»
└── show.blade.php    → التفاصيل + خطوات النشر + زر «تجهيز حزمة الرفع»
```

---

## 4. قاعدة البيانات

### جدول `releases`
| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `version` | string(20) | **فريد** مثل `v1.0.0` |
| `title` | string(255) | مطلوب |
| `description` | text nullable | |
| `status` | string(30) | default `draft` → `ReleaseStatus` |
| `source_revision` | string(100) nullable | مرجع المصدر |
| `file_snapshot` | **json nullable** | 🆕 لقطة الملفات (Phase 7) |
| `released_at` | timestamp nullable | وقت الاعتماد |
| `created_by` | foreignId → users | `restrictOnDelete` |
| timestamps | — | — |

فهارس: `unique(version)`, `index(status)`, `index(released_at)`

### جدول `release_changes`
| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `release_id` | foreignId → releases | `cascadeOnDelete` |
| `type` | string(30) | → `ReleaseChangeType` (added/modified/fixed/removed) |
| `file_path` | string(500) | مسار وصفي فقط |
| `description` | text | |
| timestamps | — | — |

### جدول `deployments`
| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `release_id` | foreignId → releases | `restrictOnDelete` |
| `environment` | string(30) | → `DeploymentEnvironment` |
| `status` | string(30) | default `pending` → `DeploymentStatus` |
| `started_at` / `completed_at` | timestamp nullable | |
| `source_revision` | string(100) nullable | |
| `failure_reason` | text nullable | |
| `output_log` | longText nullable | |
| `created_by` | foreignId → users | `restrictOnDelete` |
| `rolled_back_at` | timestamp nullable | |
| timestamps | — | — |

فهارس: `index(release_id)`, `index(environment)`, `index(status)`, `index(created_at)`

### جدول `deployment_steps`
| العمود | النوع | الملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `deployment_id` | foreignId → deployments | `cascadeOnDelete` |
| `key` | string(50) | مفتاح الأمر (من config) |
| `label` | string(255) | وصف عربي |
| `status` | string(30) | default `pending` → `DeploymentStepStatus` |
| `started_at` / `completed_at` | timestamp nullable | |
| `output` | longText nullable | |
| `sort_order` | unsignedInteger default 0 | |
| timestamps | — | — |

---

## 5. الطبقات والملفات بالتفصيل

### التعدادات (Enums)
```
app/Enums/
├── ReleaseStatus.php           → draft / published / rolled_back
├── ReleaseChangeType.php       → added / modified / fixed / removed
├── DeploymentEnvironment.php   → testing / staging / production
├── DeploymentStatus.php        → pending / in_progress / completed / failed / rolled_back
└── DeploymentStepStatus.php    → pending / in_progress / completed / failed / skipped
```
كل Enum له `label(): string` بالعربية (وأيقونة لبعضها).

### `ProjectSnapshot` — `app/Support/Deployment/ProjectSnapshot.php`
- `scan(?string $root = null, array $excludes = self::DEFAULT_EXCLUDES): array` — يبني `[relative_path => md5]`.
  - يستخدم `RecursiveDirectoryIterator` + `SKIP_DOTS` (لا `File` facade — ليعمل في اختبارات الوحدة النقية).
- `changesSince(array $previous, array $current): array` — يقارن لقطتين ويعيد قائمة تغييرات مرتبة أبجديًا.
- `DEFAULT_EXCLUDES` + `SKIP_FILENAMES` (`.DS_Store`, `Thumbs.db`).

### `ReleaseService` — `app/Services/Deployment/ReleaseService.php`
- `create(array $data, array $changes): Release` — في `DB::transaction`: ينشئ الإصدار + التغييرات + **يخزّن اللقطة تلقائيًا** عبر `forceFill(['file_snapshot' => (new ProjectSnapshot())->scan()])`.
- `detectChanges(?Release $since = null): array` — يكتشف التغييرات منذ آخر إصدار لديه لقطة.
- `publish(Release $release): void` — يعتمد الإصدار (مسودة فقط).
- `rollBack(Release $release): void`.

### `UploadPackageService` — `app/Services/Deployment/UploadPackageService.php`
- `build(Release $release): array{path, filename, count, missing, removed}` — يبني ZIP واحدًا.
- **اسم الملف**: `release-{id}-{safeVersion}-{Ymd-His-u}.zip` — **`-u` (ميكروثانية) يمنع تصادم نفس الثانية**.
- الملفات المحذوفة (removed) لا توجد محليًا → تُسجّل في `REMOVED_FILES.txt` داخل الحزمة.
- إذا كان عدد الإدخالات 0 → ملف `NOTE.txt` بديل (ZipArchive لا ينشئ ملفًا بلا إدخالات).
- `clearOldPackages(Release)` — يحذف حزم الإصدار القديمة.
- التحقق من المسار: يرفض `''`، `..`، المسارات المطلقة، خارج `base_path()`، وغير الملفات → `$missing`.

### `DeploymentService` — `app/Services/Deployment/DeploymentService.php`
- `queue(Release $release, DeploymentEnvironment $environment, User $user): Deployment`
  - يرفض الإصدار غير المنشور / البيئة غير المسموحة / نشر نشط على نفس البيئة.
  - ينشئ سجل Deployment في معاملة، ثم **يدفع الجوب بعد الالتزام** (لن يرى الجوب سجلًا قابلًا للتراجع).
- `markAsCompleted`, `markAsFailed` (مع `failure_reason`), إلخ.

### `DeploymentProcessRunner` — `app/Services/Deployment/DeploymentProcessRunner.php`
- `run(string $commandKey, ?string $path = null, int $timeout = 120): ProcessResult`
  - **فقط** مفاتيح من `config('deployment.commands')` — المدخلات الخام لا تصل إلى Process أبدًا.
  - `Symfony Process` + `setTimeout` + `setIdleTimeout`.
  - **تنظيف المخرجات**: يحجب الأنماط السرية (`APP_KEY`, `DB_PASSWORD`, `*_SECRET`, `*_TOKEN`, `*PASSWORD`) ويلخّصها إلى 8192 حرفًا.

### `DeploymentPathGuard` — `app/Support/Deployment/DeploymentPathGuard.php`
- `validate(string $path, ?array $allowedPaths = null): string`
  - يرفض المسارات المطلقة، `..`, `./`, والأماكن خارج `config('deployment.allowed_paths')`.
  - يتحقق أن المسار يُحلّ داخل دليل المشروع (ضد الروابط الرمزية الهاربة).

### `ProcessResult` — `app/Services/Deployment/ProcessResult.php`
- يحمل `successful: bool`, `output: string`, `exitCode: int`.

### `RunDeploymentJob` — `app/Jobs/RunDeploymentJob.php`
- `ShouldQueue` مع `SerializesModels`.
- **إعدادات من config**: `tries` (1), `timeout` (600), `backoff` (30).
- **WithoutOverlapping** على `deployment:{environment}` — يمنع تنفيذ نفس البيئة مرتين متوازيتين.
- يحدّث الحالة إلى `InProgress`, ينشئ `DeploymentStep` لكل أمر، يشغّلها عبر `DeploymentProcessRunner`.
- عند فشل خطوة → النشر `failed` مع `failure_reason` و`output_log`.

### `CleanupStaleDeployments` — `app/Console/Commands/CleanupStaleDeployments.php`
- الأمر: `app:cleanup-stale-deployments` — يُجدول كل 5 دقائق (`routes/console.php`).
- يحوّل عمليات `pending`/`in_progress` الأقدم من `config('deployment.stale_after_minutes')` (30 دقيقة) إلى `failed`، ويعلّم الخطوات المتبقية `skipped`.

---

## 6. فصل السوبر أدمن

### القرار (بموافقة المستخدم)
- **حساب منفصل**: سوبر أدمن بحساب مستقل تمامًا (وليس دورًا داخل حساب الأدمن).
- **التصميم**: نفس التصميم المرئي (`layouts.app`).
- **النطاق**: فقط «الإصدارات والنشر» ينتقل للسوبر أدمن؛ المستخدمون/الإعدادات/المنظمة تبقى مع أدمن الخيرية.

### التغييرات المنفذة
| الملف | التغيير |
|---|---|
| `app/Models/User.php` | `ROLE_SUPER_ADMIN = 'super_admin'` + `isSuperAdmin()` + `scopeSuperAdmins()` |
| `database/factories/UserFactory.php` | حالة `superAdmin(): static` |
| `app/Http/Middleware/EnsureSuperAdmin.php` | 🆕 يسمح فقط للسوبر أدمن (غير ذلك 403) |
| `bootstrap/app.php` | alias `super_admin` |
| `routes/web.php` | مجموعة مسارات `superadmin-dashboard` |
| `app/Support/Navigation.php` | مجموعة `super_admin` («الإدارة التقنية») |
| `resources/views/livewire/pages/login.blade.php` | توجيه بعد الدخول: سوبر أدمن → `deployments.index`، غيره → `dashboard` |
| `resources/views/livewire/pages/dashboard.blade.php` | السوبر أدمن يُحوَّل فورًا إلى `deployments.index` |
| `resources/views/layouts/app.blade.php` | إحصائيات الخيرية الثلاث مخفية عن السوبر أدمن |
| `database/seeders/DatabaseSeeder.php` | حساب `superadmin` (كلمة المرور `password`) |

### حساب السوبر أدمن (Seeder)
```
username:  superadmin
email:     superadmin@charity.org
password:  password   ← ⚠️ غيّرها فورًا في الإنتاج
name:      السوبر أدمن التقني
role:      super_admin
```

### شريط التنقل للسوبر أدمن
- يرى **فقط** مجموعة «الإدارة التقنية» (الإصدارات والنشر).
- لا يرى لوحة الخيرية ولا إحصائياتها ولا أي عناصر إدارية أخرى.

---

## 7. إعدادات config/deployment.php

```php
return [
    'environments' => [
        'testing' => ['label' => 'اختباري', 'commands' => ['migrate', 'cache']],
        'staging' => ['label' => 'تجريبي', 'commands' => ['migrate', 'cache']],
        // إنتاجي: allowlist معتمد من المالك (2026-08-04): migrate + cache فقط.
        // Queue Worker يعمل على جهاز Herd المحلي (php artisan queue:work).
        'production' => ['label' => 'إنتاجي', 'commands' => ['migrate', 'cache']],
    ],

    'allowed_paths' => [
        'app', 'config', 'database/migrations', 'database/seeders',
        'lang', 'resources', 'routes', 'tests',
    ],

    'commands' => [
        'install'       => 'composer install --no-dev --optimize-autoloader',
        'migrate'       => 'php artisan migrate --force',
        'cache'         => 'php artisan optimize',
        'config-cache'  => 'php artisan config:cache',
        'route-cache'   => 'php artisan route:cache',
        'view-cache'    => 'php artisan view:cache',
        'event-cache'   => 'php artisan event:cache',
        'build'         => 'npm run build',
    ],

    'step_labels' => [ /* وصف عربي لكل مفتاح */ ],

    'job' => [
        'tries' => 1,
        'timeout' => 600,       // يجب أن يتجاوز مجموع مهلات الخطوات
        'backoff' => 30,
        'step_timeout' => 120,
    ],

    'stale_after_minutes' => 30,
];
```

> 🔐 **قاعدة ذهبية**: في بيئة الإنتاج، لا تُفعّل إلا `migrate` و`cache`. لا `config-cache` منفردًا إلا بعد مراجعة، ولا `build`/`install` على الخادم المشترك ما لم يلزم.

---

## 8. التدفق الكامل (Flow)

### أ) إنشاء الإصدار
```
السوبر أدمن → /superadmin-dashboard/create
      │
      ▼
CreateRelease::importChanges()  ← زر «استيراد التغييرات تلقائيًا»
      │  ReleaseService::detectChanges()
      │  = مقارنة لقطة آخر إصدار بالحالة الحالية للمشروع
      ▼
تملأ النموذج: كل تغيير {type, file_path, description}
      │
      ▼
save() → ReleaseService::create()
   في معاملة: release + changes + file_snapshot (لقطة كاملة)
```

### ب) اعتماد الإصدار
```
ShowRelease::publish() → ReleaseService::publish()
  draft → published + released_at = now()
```

### ج) النشر (Job خلفي)
```
ShowRelease::deploy() → DeploymentService::queue()
  1) تحقق: منشور؟ بيئة مسموحة؟ لا نشر نشط؟
  2) إنشاء Deployment (pending)
  3) RunDeploymentJob::dispatch (بعد التزام المعاملة)
        │
        ▼ Queue Worker (php artisan queue:work)
        ├── status → in_progress + started_at
        ├── لكل أمر في config: ينشئ DeploymentStep
        │     └── DeploymentProcessRunner::run(key)
        │           └── Process + PathGuard + sanitize output
        ├── نجاح الكل → completed + completed_at
        └── أي فشل → failed + failure_reason + output_log
```

### د) حزمة الرفع ZIP
```
ShowRelease::prepareUploadPackage() → UploadPackageService::build()
  لكل تغيير:
    - removed → يضاف اسمه إلى REMOVED_FILES.txt داخل الحزمة
    - غير موجود محليًا → missing (تحذير في الواجهة)
    - موجود → zip->addFile(absolute, relative)
  ثم: response()->download()  ← يعمل داخل أكشن Livewire
```

### هـ) تنظيف العالقة
```
Schedule::command('app:cleanup-stale-deployments')->everyFiveMinutes()
  → أي نشر pending/in_progress أقدم من 30 دقيقة → failed
```

---

## 9. النتائج والتحقق

### الاختبارات
- **233/233 اختبارًا (659 تأكيدًا) ناجحة** — كامل المجموعة خضراء.
- **66/66 اختبارًا** لنظام النشر تحديدًا + **4/4** لـ `ProjectSnapshotTest` (وحدة).
- ملفات الاختبار (تحت `tests/Feature/Deployments/`):
  - `DeploymentAccessTest.php` — صلاحيات الوصول (سوبر أدمن يصل، أدمن/مستخدم/عامل ميداني ممنوعون 403، ضيف يُحوَّل لتسجيل الدخول).
  - `DeploymentReleaseTest.php` — إنشاء الإصدارات، التحقق، النشر، منع التزامن، الفلترة.
  - `DeploymentServiceTest.php` + `DeploymentProgressTest.php` — منطق الخدمة وتقدم الخطوات.
  - `RunDeploymentJobTest.php` + `CleanupStaleDeploymentsTest.php` — الجوب والتنظيف.
  - `ReleaseSnapshotImportTest.php` + `UploadPackageServiceTest.php` — اللقطات وحزمة ZIP.
  - `DeploymentPathGuardTest.php` + `DeploymentProcessRunnerTest.php` — الحماية والمشغّل.
- **Pint نظيف**: `vendor/bin/pint --dirty --format agent`.
- **قاعدة الاختبار**: `DB_DATABASE=:memory:` في phpunit.xml → الاختبارات لا تمس قاعدة البيانات الحقيقية. نشر Queue في الاختبارات عبر `Bus::fake()` (لأن QUEUE_CONNECTION=database في phpunit).

> 🐛 **درس مهم**: كل ملف اختبار PHPUnit يجب أن يحتوي **فئة واحدة فقط** تحمل اسم الملف نفسه. كانت فئة `DeploymentAccessTest` (8 اختبارات أمان) موجودة داخل `DeploymentReleaseTest.php` فكانت **لا تُشغَّل أبدًا** — اكتُشف ذلك عند كتابة هذا التوثيق وفُصلت إلى ملفها المستقل، فارتفع عدد الاختبارات من 225 إلى 233. تحقق دائمًا أن `php artisan test` يشغّل العدد الذي تتوقعه.

### التحقق في المتصفح (Live)
- دخول السوبر أدمن → توجيه إلى `/superadmin-dashboard` ✓
- شريط التنقل يعرض «الإدارة التقنية» فقط ✓
- روابط `/create` و`/{release}` تعمل ✓
- دخول أدمن الخيرية → لوحة الخيرية بدون أي عنصر نشر ✓
- محاولة وصول الأدمن المباشرة للمسارات → **403** ✓
- زر الاستيراد يملأ صفوف التغييرات ✓
- تنزيل حزمة ZIP يعمل ✓
- كشف الملفات التلقائي يعمل ✓

### تجارب النشر الحقيقية (المرحلة 6)
- نُفذت عمليات نشر فعلية على البيئات **testing / staging / production** بنجاح (مع Queue Worker محلي).
- ملاحظة: البيانات الحالية للـ DB استُعيدت عدة مرات — انظر قسم المخاطر.

---

## 10. الدروس المستفادة والتوصيات النهائية

### 🔴 توصيات أمنية إلزامية
1. **تغيير كلمة مرور السوبر أدمن** فورًا في أي بيئة إنتاج (`password` افتراضية).
2. **قائمة الأوامر البيضاء فقط** — لا تصل أي مدخلات مستخدم إلى `Process`.
3. **حماية ثلاثية** للمنطقة التقنية: Middleware + Navigation::canAccessRoute + abort_unless في mount.
4. **لا تشغّل `migrate:fresh` أو `db:wipe`** في الإنتاج — أدى ذلك إلى مسح قاعدة البيانات 4 مرات أثناء التطوير.
5. **تغيير `APP_KEY`** و`SESSION_DRIVER=database` محسوب.
6. **`withoutOverlapping`** على مستوى البيئة يمنع النشر المتوازي لنفس البيئة.

### 🟡 دروس تقنية
1. **ZipArchive لا ينشئ ملفًا بلا إدخالات** → إذا كانت قائمة التغييرات فارغة، أنشئ `NOTE.txt` بديلًا.
2. **تصادم أسماء ZIP في نفس الثانية** → أضف `-u` (ميكروثانية) إلى تنسيق الوقت في اسم الملف.
3. **اختبارات الوحدة النقية لا تستخدم `File` facade** («facade root not set») → استخدم `RecursiveDirectoryIterator` + `SKIP_DOTS` في `ProjectSnapshot`.
4. **لا تظلل `$path` في الحلقات** داخل `UploadPackageService` — أخطاء مرجعية خفية.
5. **`response()->download()` يعمل داخل أكشن Livewire** — لا حاجة لمسارات تنزيل منفصلة.
6. **دفع الجوب بعد التزام المعاملة** — حتى لا يرى الجوب سجلًا قابلًا للتراجع.
7. **لا `Bus::fake()` مع `QUEUE_CONNECTION=sync`** — الاختبارات تستخدم `database` في phpunit و `Bus::fake()`.
8. **`public/build` مضمّن** (قرار المستخدم) — لأنه يُبنى محليًا ويجب رفعه.
9. **`db:seed` يعيد الحسابات** عبر `updateOrCreate` — أسرع استرداد بعد أي مسح.
10. **`logout` مسار POST فقط** — GET يؤدي إلى 405.
11. **فئة واحدة لكل ملف اختبار** — PHPUnit يشغّل الفئة التي تطابق اسم الملف فقط؛ أي فئة إضافية داخل نفس الملف **لا تُشغَّل أبدًا** بصمت. (هكذا ضاعت 8 اختبارات أمان حتى اكتُشفت أثناء التوثيق.)

### 🟢 توصيات للتنفيذ في مشاريع أخرى
- خذ اللقطة عند **كل** إصدار (وليس فقط عند الاقتضاء) — يكفل دقة الكشف التالي.
- اجعل `stale_after_minutes` أكبر من مجموع مهلات الخطوات (30 > 2×120 ثانية).
- ابدأ ببيئة `testing` فقط ثم فعّل `staging` ثم `production` بعد مراجعة الأوامر.
- شغّل Queue Worker واحدًا محليًا (أو عبر Supervisor في الإنتاج).
- احتفظ بـ«إصدار الأساس» (baseline) الأول — هو مرجع المقارنة الأول.

---

## 11. خطوات التنفيذ في مشروع جديد (Checklist)

### المرحلة 1 — الأساس
- [ ] `config/deployment.php` (environments + allowed_paths + commands + job + stale).
- [ ] التعدادات الخمسة: `ReleaseStatus`, `ReleaseChangeType`, `DeploymentEnvironment`, `DeploymentStatus`, `DeploymentStepStatus` (كلها مع `label()`).
- [ ] الهجرات: `releases` (+`file_snapshot` JSON) + `release_changes` + `deployments` + `deployment_steps`.

### المرحلة 2 — النماذج والعلاقات
- [ ] `Release` (hasMany changes, hasMany deployments, creator) + `ReleaseChange` + `Deployment` (steps, release, creator) + `DeploymentStep`.
- [ ] Factories للنماذج الأربعة (مع حالات للاختبار).

### المرحلة 3 — الخدمات
- [ ] `ProjectSnapshot` (scan + changesSince + excludes).
- [ ] `ReleaseService` (create + detectChanges + publish + rollBack).
- [ ] `UploadPackageService` (ZIP + REMOVED_FILES + NOTE.txt + -u microseconds).
- [ ] `DeploymentService` (queue + markAsCompleted + markAsFailed).
- [ ] `DeploymentProcessRunner` + `ProcessResult` + `DeploymentPathGuard`.

### المرحلة 4 — الجوب والجدولة
- [ ] `RunDeploymentJob` (tries/timeout/backoff من config + WithoutOverlapping).
- [ ] `CleanupStaleDeployments` command + جدولة كل 5 دقائق في `routes/console.php`.
- [ ] شغّل `php artisan queue:table` + `php artisan migrate` + `QUEUE_CONNECTION=database`.

### المرحلة 5 — الدور والحماية
- [ ] `ROLE_SUPER_ADMIN` + `isSuperAdmin()` + `scopeSuperAdmins()` في `User`.
- [ ] `EnsureSuperAdmin` middleware + alias في `bootstrap/app.php`.
- [ ] `UserFactory::superAdmin()` state.
- [ ] `Navigation` — مجموعة `super_admin` + `visibleGroupsFor` + `canAccessRoute`.
- [ ] Seeder: حساب سوبر أدمن (`updateOrCreate`).

### المرحلة 6 — الواجهة (Livewire)
- [ ] `Index`, `CreateRelease`, `ShowRelease` مكوّنات MFC مع `#[Layout('layouts.app')]` و`abort_unless` في mount.
- [ ] العروض الثلاثة (index / create / show) بنفس تصميم التطبيق الحالي.
- [ ] زر «استيراد التغييرات تلقائيًا» + زر «تجهيز حزمة الرفع».
- [ ] توجيه الدخول: سوبر أدمن → `deployments.index`.
- [ ] المسارات: `Route::middleware('super_admin')->prefix('superadmin-dashboard')`.

### المرحلة 7 — الاختبارات
- [ ] `DeploymentAccessTest` (فئة **مستقلة في ملف باسمها** — صلاحيات السوبر أدمن + منع الآخرين).
- [ ] `DeploymentReleaseTest` (إنشاء/نشر/تراجع + الفلترة).
- [ ] `DeploymentServiceTest` + `DeploymentProgressTest` (تقدم الخطوات).
- [ ] `RunDeploymentJobTest` (Bus::fake + بيئات).
- [ ] `CleanupStaleDeploymentsTest` (المهل).
- [ ] `ReleaseSnapshotImportTest` (الكشف التلقائي + الاستثناءات).
- [ ] `UploadPackageServiceTest` (ZIP + removed + missing + NOTE.txt).
- [ ] `DeploymentPathGuardTest` + `DeploymentProcessRunnerTest` (الحماية).
- [ ] `ProjectSnapshotTest` (وحدة — بدون File facade).
- [ ] شغّل: `php artisan test --compact` + `vendor/bin/pint --dirty --format agent`.

---

## 12. معلومات الخادم والرفع

### الهدف الحالي (الاستضافة المشتركة cPanel)
```
النطاق الفرعي:  charity.phantom-tech.site
دليل الوثائق:  public_html/charity-app/public
فك الضغط عند:  public_html/charity-app/
```
- الرفع عبر **FTP يدوي**: نزّل حزمة ZIP من صفحة الإصدار → ارفعها → فك الضغط في `public_html/charity-app/`.
- حذف الملفات المحذوفة: من قائمة `REMOVED_FILES.txt` داخل الحزمة.
- **Queue Worker** يعمل على جهاز Herd المحلي: `php artisan queue:work` (PID في وقت التحقق: 13436).

---

## 13. مخاطر محذرة وملاحظات مهمة

1. ⚠️ **لا تشغّل `migrate:fresh` أو `db:wipe`** — مسحت قاعدة البيانات 4 مرات أثناء التطوير. استخدم `db:seed` للاسترداد (يعيد الحسابات عبر `updateOrCreate`).
2. ⚠️ **احفظ إصدار الأساس (baseline)** — اللقطة الأولى هي مرجع المقارنة؛ بدونها لن يكتشف النظام تغييرات في الإصدار التالي.
3. ⚠️ **غيّر كلمة مرور السوبر أدمن** في أي بيئة إنتاج.
4. ⚠️ **Worker محلي**: عمليات النشر في الإنتاج تعتمد على Queue Worker على جهاز التطوير — تأكد أنه يعمل قبل أي نشر.
5. ⚠️ **لا تُعدّل `config/deployment.php`** (خاصة `commands` و`allowed_paths`) دون مراجعة — أي أمر مضاف يصبح قابلاً للتنفيذ من الواجهة.
6. ℹ️ عند تعديل الواجهة ثم عدم ظهور التغيير: شغّل `npm run build` أو `npm run dev` (Vite).
7. ℹ️ المسارات القديمة `/deployments` و`/super-admin/deployments` لم تعد موجودة — 404 (المسار الجديد `superadmin-dashboard`).
