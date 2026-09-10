# 🛠️ الأدوات التقنية للسوبر أدمن — توثيق جاهز لإعادة الاستخدام

> **الإصدار**: v1.0 — **الحالة**: مُنفّذ ومُتحقق منه في مشروع «الخيرية» (أغسطس 2026)
> **الغرض**: توثيق كامل للوظائف الثلاث (النسخ الاحتياطية، الصيانة، النشر الذكي) كمرجع جاهز لنسخها ونقلها إلى **أي مشروع Laravel جديد** بكفاءة من أول مرة.

---

## جدول المحتويات

1. [نظرة عامة](#1-نظرة-عامة)
2. [النسخ الاحتياطية (Backups)](#2-النسخ-الاحتياطية-backups)
3. [الصيانة (Maintenance)](#3-الصيانة-maintenance)
4. [النشر الذكي (Smart Deployment)](#4-النشر-الذكي-smart-deployment)
5. [الاعتماديات المشتركة (UI والنظام)](#5-الاعتماديات-المشتركة-ui-والنظام)
6. [Checklist النقل إلى مشروع جديد](#6-checklist-النقل-إلى-مشروع-جديد)
7. [الدروس المستفادة والمزالق](#7-الدروس-المستفادة-والمزالق)

---

## 1. نظرة عامة

ثلاث صفحات داخل **منطقة السوبر أدمن التقنية** (`/superadmin-dashboard`)، كلها خلف middleware `super_admin` وتستخدم نفس نظام التصميم (`x-ui.*`):

| الصفحة | المسار | اسم الراوت | Livewire Component | الوظيفة |
|---|---|---|---|---|
| النسخ الاحتياطية | `/superadmin-dashboard/backups` | `backups.index` | `App\Livewire\Backups\Index` | نسخ/تنزيل/استعادة قاعدة البيانات |
| الصيانة | `/superadmin-dashboard/maintenance` | `deployments.maintenance` | `App\Livewire\Deployments\Maintenance` | كاش + رابط تخزين + تحديث حزم |
| النشر الذكي | `/superadmin-dashboard/smart-deployment` | `deployments.smart-deployment` | `App\Livewire\Deployments\SmartDeployment` | رفع الملفات المتغيرة فقط عبر HTTP |

**المبدأ الأمني المشترك**: ثلاث طبقات حماية لجميع الصفحات:
1. Middleware `super_admin` في الراوتر (`EnsureSuperAdmin`).
2. منع الرابط من الظهور للمستخدمين غير المصرّح لهم في `app/Support/Navigation.php`.
3. `abort_unless(auth()->user()?->isSuperAdmin(), 403);` داخل `mount()` لكل مكوّن.

---

## 2. النسخ الاحتياطية (Backups)

### 2.1 الملفات

```
app/Livewire/Backups/Index.php                    → مكوّن الصفحة (MFC)
resources/views/livewire/pages/backups/index.blade.php → العرض
app/Services/Backup/DatabaseBackupService.php     → الخدمة (إنشاء/استعادة/تنزيل/حذف/تنظيف)
app/Models/DatabaseBackup.php                     → الموديل
app/Enums/DatabaseBackupStatus.php                → pending / completed / failed
app/Console/Commands/CreateDatabaseBackup.php     → أمر `app:database-backup` (للمجدول الزمني)
database/migrations/2026_08_04_200000_create_database_backups_table.php
config/backup.php                                 → الإعدادات
```

### 2.2 جدول `database_backups`

| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `filename` | string(255) | مثل `backup-20260807-033000-123456.sqlite` |
| `size_bytes` | unsignedBigInteger | |
| `status` | string(30) | → `DatabaseBackupStatus` |
| `failure_reason` | text nullable | |
| `created_by` | foreignId nullable → users | `null` = تلقائي (من المجدول) |
| timestamps | — | فهارس: `status`, `created_at` |

### 2.3 الخدمة `DatabaseBackupService`

- `directory()` → `storage/app/{config('backup.directory')}` أي `storage/app/database-backups`.
- `create(?User $user = null): DatabaseBackup` — يكتب الملف ثم يسجّل الصف ثم يستدعي `prune()`.
- `restore(DatabaseBackup)` — **SQLite فقط** حاليًا (`restoreSupported()`).
- `download()` → `BinaryFileResponse`.
- `delete()` — يحذف الملف والصف.
- `prune(int $keep)` — يحذف الأقدم تلقائيًا بعد الوصول للحد.

**الاستراتيجية لكل محرك**:

| المحرك | طريقة الإنشاء | الاستعادة |
|---|---|---|
| SQLite | `VACUUM INTO 'path'` (DB::statement) — لقطة متسقة أثناء الاستخدام | نسخ لملف مؤقت + `rename` ذرّي فوق الملف الحي |
| MySQL | `mysqldump` عبر `Symfony Process` مع `MYSQL_PWD` في env (أبدًا في الأمر) | غير مدعومة من الواجهة |
| PostgreSQL | `pg_dump` عبر `Process` مع `PGPASSWORD` | غير مدعومة من الواجهة |

**تفاصيل SQLite restore** (مهمة):
```php
$temp = dirname($database).'/database.restore-'.now()->format('YmdHisu').'.sqlite';
copy($source, $temp);
DB::disconnect();
rename($temp, $database);   // ذرّي على نفس نظام الملفات
DB::reconnect();
```

### 2.4 الموديل `DatabaseBackup`

- `creator(): BelongsTo` — منشئ النسخة.
- `isSystem(): bool` — `created_by === null` (أي تلقائية).

### 2.5 أمر المجدول `app:database-backup`

```php
#[Signature('app:database-backup')]
#[Description('إنشاء نسخة احتياطية من قاعدة البيانات')]
```

مسجّل في `routes/console.php`:
```php
Schedule::command('app:database-backup')
    ->dailyAt(config('backup.schedule_time', '03:00'))
    ->description('نسخة احتياطية يومية لقاعدة البيانات');
```

> ⚠️ على الاستضافة المشتركة يجب تشغيل المجدول عبر **Cron حقيقي** على السيرفر:
> `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
> (أو `php artisan schedule:work` محليًا مع Herd).

### 2.6 إعدادات `config/backup.php`

```php
'keep' => (int) env('DATABASE_BACKUP_KEEP', 5),   // عدد النسخ المحتفظ بها
'directory' => 'database-backups',                 // داخل storage/app
'schedule_time' => '03:00',                        // توقيت النسخة التلقائية
```

### 2.7 واجهة المستخدم (index.blade.php)

- زر علوي «نسخة احتياطية جديدة» (`wire:click="create"` مع `:loading="$creating"`).
- 4 بطاقات إحصائية: إجمالي النسخ، آخر نسخة، الاحتفاظ، التوقيت التلقائي.
- جدول: اسم الملف، التاريخ، الحجم، المصدر (تلقائي/يدوي + الاسم)، الحالة (شارة)، إجراءات.
- لكل نسخة مكتملة: **تنزيل** + **استعادة** (إذا كان SQLite) + **حذف** (مع `wire:confirm`).
- نافذة `restore-modal`: تعرض اسم الملف، تحذير أحمر، حقل «اكتب اسم الملف بالضبط» للموافقة، وزر الاستعادة **معطّل حتى يتطابق الاسم** (`:disabled="$confirmFilename !== $this->backupToRestore->filename"`).

---

## 3. الصيانة (Maintenance)

### 3.1 الملفات

```
app/Livewire/Deployments/Maintenance.php                 → المكوّن
resources/views/livewire/pages/deployments/maintenance.blade.php → العرض
```

لا يحتاج جداول ولا خدمات — يستخدم `Artisan` و`Process` مباشرة.

### 3.2 الوظائف الثلاث

| الزر | الطريقة | ما يفعل | ملاحظات |
|---|---|---|---|
| حذف الكاش | `clearCaches()` | `Artisan::call('optimize:clear')` | لا يمس البيانات |
| إنشاء رابط التخزين | `createStorageLink()` | ينشئ symlink يدويًا بـ `symlink()` **بدون** `exec()` | اختياري — الملفات تعمل عبر `/media` بدونه |
| تحديث الحزم | `updatePackages()` | `Process::path(base_path())->timeout(900)->run('composer update --no-interaction --no-progress --prefer-dist')` | قد يطول |

### 3.3 أبرز النقاط التقنية

1. **`storageLinkExists`** تُحسب في `mount()` عبر `is_link(public_path('storage'))` وتُعرض كشارة (موجود/غير موجود).
2. **لا تستخدم `Artisan::call('storage:link')`** — على الاستضافة المشتركة (Hostinger/LiteSpeed) يستدعي `exec('ln -s ...')` وهي **معطّلة**. بديلها:
   ```php
   if (! function_exists('symlink')) { throw new RuntimeException('...'); }
   if (! @symlink($target, $link)) { throw new RuntimeException('...'); }
   ```
   مع رسالة واضحة أن الزر اختياري لأن `/media` يعمل بدونه.
3. **`updatePackages`** يرمي خطأ `Process` بقراءة `errorOutput()`، والـ timeout 900 ثانية.

### 3.4 واجهة المستخدم

- 3 أزرار علوية مع `wire:loading.attr="disabled"` + `wire:target` + `:loading`.
- رسائل `errorMessage` / `statusMessage` كـ `x-ui.alert`.
- 3 بطاقات شرح لكل أداة (مع `code` للتعليمات وشارة حالة الرابط).

---

## 4. النشر الذكي (Smart Deployment)

### 4.1 الفكرة

رفع **الملفات المتغيرة فقط** (لا كل المشروع) من خلال **مانيفست محلي** يسجل ما نُشر آخر مرة:

```
scanLocalFiles(): [relative_path => md5]   ← كل ملف مسموح في المشروع
        │
        ├── local mode  → قارن مع storage/app/deployment_manifest.json
        └── server mode → قارن مع manifest حقيقي من deployer.php على السيرفر
        │
        ▼
diff(previous, current) → added / modified / removed
        │
        ▼
نشر: ZIP للملفات المتغيرة → POST إلى deployer.php?action=deploy → نجاح → تحديث المانيفست
```

### 4.2 الملفات الكاملة

```
app/Livewire/Deployments/SmartDeployment.php                  → المكوّن (الصفحة + إدارة المسارات)
resources/views/livewire/pages/deployments/smart-deployment.blade.php → العرض
app/Services/Deployment/SmartDeploymentService.php            → الفحص/المقارنة/الرفع/المانيفست/السجل
app/Support/Deployment/ProjectSnapshot.php                    → مسح الملفات + الاستثناءات + المقارنة
app/Support/Deployment/DeploymentPaths.php                    → قائمة المسارات المسموحة (من DB أو config)
app/Support/Deployment/DeploymentPathGuard.php                → حارس المسارات (أمن: مطلق/.. /symlink)
app/Models/DeploymentAllowedPath.php                          → صف مسار مسموح (من الواجهة)
app/Models/SmartDeployment.php                                → سجل عملية نشر
public/deployer.php                                           → سكربت السيرفر (يُنشر مع المشروع)
database/migrations/2026_08_05_000000_create_deployment_allowed_paths_table.php
database/migrations/2026_08_06_000001_create_smart_deployments_table.php
config/deployment.php → قسم 'smart'
```

### 4.3 الجداول

**`deployment_allowed_paths`** (المسارات المسموحة المُدارة من الواجهة):
| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `path` | string **unique** | مسار نسبي من جذر المشروع |
| timestamps | — | |

> إذا كان الجدول فارغًا → fallback إلى `config('deployment.allowed_paths')` (الافتراضيات).

**`smart_deployments`** (سجل عمليات النشر):
| العمود | النوع | ملاحظات |
|---|---|---|
| `id` | bigIncrements | PK |
| `user_id` | foreignId nullable → users | nullOnDelete |
| `mode` | enum `local`/`server` | |
| `status` | enum `pending`/`deploying`/`success`/`failed` | |
| `files_count` | integer | |
| `total_size` | bigInteger (بايت) | |
| `files_list` | text (JSON) | الملفات المنشورة |
| `notes` | text nullable | |
| `server_response` | longText nullable | |
| `started_at` / `completed_at` | timestamp nullable | |

### 4.4 إعدادات `config/deployment.php` → قسم `smart`

```php
'smart' => [
    'server_url' => env('DEPLOY_SERVER_URL', ''),       // رابط deployer.php على السيرفر
    'secret_key' => env('DEPLOY_SECRET_KEY', ''),
    'manifest_path' => storage_path('app/deployment_manifest.json'),

    // المسارات المضمّنة في الفحص
    'include' => ['app', 'config', 'database/migrations', 'database/seeders', 'lang',
                  'public', 'resources', 'routes', 'bootstrap', 'composer.json',
                  'composer.lock', 'package.json', 'vite.config.js', 'artisan'],

    // مستثنى داخل المسارات المضمّنة
    'exclude_within' => ['public/storage', 'public/hot', 'bootstrap/cache',
                         'public/build/.vite', 'node_modules', 'vendor', 'storage'],
],
```

**المتغيرات المطلوبة في `.env`** (أضِفها إلى `.env.example` في أي مشروع جديد):
```
DEPLOY_SERVER_URL=https://example.com/deployer.php
DEPLOY_SECRET_KEY=secret-key-matches-deployer.php
DATABASE_BACKUP_KEEP=5
```

### 4.5 `deployer.php` (سكربت السيرفر)

مستقل تمامًا عن Laravel (لا يحتاج vendor). ثلاث أفعال عبر POST:

| action | المطلوب | الاستجابة |
|---|---|---|
| `status` | — | `{success, php, root}` (لا يتطلب secret) |
| `get_manifest` | `secret` | `{success, files: {path: md5}}` |
| `deploy` | `secret` + `archive` (ملف ZIP) | فك الضغط + إنشاء المجلدات + مسح الكاش → `{success, files}` |

نقاط حاسمة في `deployer.php`:
- **لا `exec()` ولا `symlink()`** — متوافق مع الاستضافة المشتركة المقفلة.
- `hash_equals(DEPLOY_SECRET, $secret)` لمقارنة المفتاح بأمان.
- `path_is_included()` يطابق نفس قواعد `include`/`exclude_within` المحلية — **يجب إبقاؤهما متطابقين**.
- يستبعد `basename === '.gitignore'`.
- يرفض أسماء الملفات المحتوية على `..`.
- `extract_archive` يكتب عبر `fopen/fwrite` (لا `ZipArchive::extractTo` — ليتحكم في المسارات).

### 4.6 الخدمة `SmartDeploymentService`

| الطريقة | الوظيفة |
|---|---|
| `scanLocalFiles()` | فحص محلي → `[path => md5]` حسب config |
| `shouldIncludeFile()` | يستبعد `.gitignore` + `exclude_within` |
| `totalSize(array $paths)` | الحجم الكلي بالبايت |
| `loadManifest()` / `saveManifest()` / `resetManifest()` | المانيفست JSON في `storage/app` |
| `getLocalChanges()` | فرق محلي ↔ مانيفست |
| `getServerChanges()` | فرق محلي ↔ سيرفر (`getServerManifest()`) |
| `fetchHttpManifest()` | POST `get_manifest` مع **timeout 45s / connect 10s** ورسائل خطأ عربية |
| `deploy($changes, $onProgress)` | ZIP → POST → تحديث مانيفست؛ وضع محلي فقط إذا كان `server_url` فارغًا |
| `startRecord` / `completeRecord` / `failRecord` | سجل `smart_deployments` |
| `getRecentDeployments()` | آخر 10 عمليات |

**توقيت الرفع (مهم جدًا للاستضافة المشتركة)**:
```php
Http::connectTimeout(10)->timeout(45)->attach('archive', $content, 'deployment.zip')->post(...)
```
السبب: خادم الويب (nginx/تحويلة) يقطع الاتصال بعد ~60 ثانية، فإذا طالت العملية تُعرض صفحة 504 سوداء. بوضع 45 ثانية ينهي PHP قبل القطع ويلتقط الخطأ برسالة عربية ودية.

**وضع local-only**: إذا كان `DEPLOY_SERVER_URL` فارغًا، يعمل النشر محليًا فقط (تحديث المانيفست دون رفع) — مفيد للتطوير والاختبار.

### 4.7 المكوّن `SmartDeployment` (خصائص + دوال أساسية)

- خصائص: `mode` (مفعّل `#[Url]`)، `isScanning`، `isDeploying`، `uploadProgress`، `addedFiles`/`modifiedFiles`/`deletedFiles`، `totalSize`، `errorMessage`/`successMessage`، `isServerConfigured`، وإدارة المسارات: `allowedSelected`/`allowedSearch`/`allowedSaving`.
- `mount()` → `refreshSetupState()` + `loadAllowedSelection()` (يرفع المسارات المحفوظة العميقة إلى جذرها).
- `scanChanges()` — فحص محلي؛ `scanServerChanges()` — مقارنة مع السيرفر.
- `startDeployment()` — يبدأ النشر مع تقدم مُبثّث عبر `$this->stream(to: 'progress', ...)`.
- `resetManifest()` — حذف المانيفست (كل الملفات ستظهر كجديدة).
- `sortedFiles()` (computed) — قائمة موحدة مرتبة: deleted ← modified ← added.
- إدارة المسارات: `allowedEntries()` / `allowedDisabledEntries()` (المستثنى دائمًا) / `allowedSelectedCount()` / `selectAllPaths()` / `clearAllPaths()` / `saveAllowedPaths()`.

**`saveAllowedPaths()`** يقوم بـ:
1. التحقق أن كل مسار محدد موجود فعلًا في الجذر.
2. إسقاط أي عنصر يقع **تحت** مجلد محدد (`isUnderSelectedDir`) — اختيار المجلد يغطي ما بداخله.
3. `DeploymentAllowedPath::query()->delete()` ثم إعادة إنشاء الصفوف.
4. إشعار «تم حفظ X مسار مسموح.»

### 4.8 واجهة المستخدم (smart-deployment.blade.php)

- **أزرار علوية**: فحص محلي، مقارنة مع السيرفر، مسارات النشر، نشر التغييرات (يظهر عند وجود تغييرات).
- **نافذة حالة الاتصال** (Alpine `deployConnectModal`): 3 مراحل — connecting → waiting (25 ث) → error، تُفتح عبر `@click="start('deploy'|'compare', $wire)"` وتلتقط أخطاء fetch.
- **نافذة مسارات النشر** (`x-ui.modal name="deploy-paths"`): بحث، تحديد الكل/إلغاء الكل، قائمة عناصر مع خانات (العناصر المستبعدة معطّلة بـ `opacity-50`)، زر «حفظ».
- بطاقات إحصائيات، شريط تقدم مباشر، معاينة الملفات + سجل النشر الذكي.

> ⚠️ **مزلق Alpine مهم**: لفتح نافذة عبر `x-ui.modal` استخدم:
> `@click="$dispatch('open-modal', 'deploy-paths')"` — الوسيط الثاني **يصبح** `event.detail` مباشرة.
> لا تكتب `{ detail: 'deploy-paths' }` — عندها سيكون detail كائنًا ولن يتطابق مع فحص المكوّن `$event.detail === 'name'` ولن تُفتح النافذة بصمت.

---

## 5. الاعتماديات المشتركة (UI والنظام)

### مكوّنات Blade المستخدمة (`x-ui.*`)
| المكوّن | الاستخدام |
|---|---|
| `x-layout.page-header` | رأس الصفحة + `x-slot:actions` للأزرار |
| `x-ui.button` | أزرار (variants: primary/secondary/ghost/outline/danger/success) |
| `x-ui.card` | بطاقات المحتوى |
| `x-ui.stat` | بطاقات الإحصائيات |
| `x-ui.alert` | رسائل نجاح/خطأ/تحذير |
| `x-ui.modal` | النوافذ المنبثقة (`open-modal`/`close-modal` أحداث window) |
| `x-ui.badge` | شارات الحالة |
| `x-ui.empty-state` | حالة الفراغ |
| `x-ui.input` | حقول الإدخال |

### الأشياء المطلوبة في أي مشروع جديد
1. **`auth` + `super_admin` middleware** و`isSuperAdmin()` على User.
2. **`app/Support/Navigation.php`** — إدخالات الشريط الجانبي (اختيارية العرض).
3. **`x-ui.*` components** أو ما يعادلها.
4. **Livewire 3/4** + Tailwind + Alpine.

---

## 6. Checklist النقل إلى مشروع جديد

> بترتيب التنفيذ الموصى به:

### أ. النسخ الاحتياطية
- [ ] انسخ: `config/backup.php`
- [ ] انسخ: migration `create_database_backups_table.php`
- [ ] انسخ: `app/Enums/DatabaseBackupStatus.php`
- [ ] انسخ: `app/Models/DatabaseBackup.php` (+ factory)
- [ ] انسخ: `app/Services/Backup/DatabaseBackupService.php`
- [ ] انسخ: `app/Console/Commands/CreateDatabaseBackup.php`
- [ ] انسخ: `app/Livewire/Backups/Index.php` + عرض `backups/index.blade.php`
- [ ] أضف الراوت: `Route::get('/backups', BackupsIndex::class)->name('index')` داخل group سوبر أدمن
- [ ] سجّل الأمر في `routes/console.php`: `Schedule::command('app:database-backup')->dailyAt(config('backup.schedule_time', '03:00'))`
- [ ] أضف `DATABASE_BACKUP_KEEP=5` إلى `.env` + `.env.example`

### ب. الصيانة
- [ ] انسخ: `app/Livewire/Deployments/Maintenance.php` + عرض `deployments/maintenance.blade.php`
- [ ] أضف الراوت `deployments.maintenance`
- [ ] (اختياري) تأكد من مسار `/media` أو استخدم `Storage::url` في مشروعك

### ج. النشر الذكي
- [ ] انسخ: `config/deployment.php` (قسم `smart` + `allowed_paths`)
- [ ] انسخ: migrations (`deployment_allowed_paths`, `smart_deployments`)
- [ ] انسخ: `app/Support/Deployment/ProjectSnapshot.php`, `DeploymentPaths.php`, `DeploymentPathGuard.php`
- [ ] انسخ: `app/Models/DeploymentAllowedPath.php`, `app/Models/SmartDeployment.php` (+ factories)
- [ ] انسخ: `app/Services/Deployment/SmartDeploymentService.php`
- [ ] انسخ: `app/Livewire/Deployments/SmartDeployment.php` + عرض `deployments/smart-deployment.blade.php`
- [ ] انسخ: `public/deployer.php` → ارفعه على السيرفر مع المشروع
- [ ] أضف الراوتين `deployments.maintenance` + `deployments.smart-deployment`
- [ ] أضف `DEPLOY_SERVER_URL` + `DEPLOY_SECRET_KEY` إلى `.env` (المفتاح **نفسه** الموجود داخل `deployer.php`)
- [ ] أضف أمر تنظيف العمليات العالقة: `app:cleanup-stale-deployments` كل 5 دقائق (إن أردت)
- [ ] شغّل `php artisan migrate` + `npm run build`

### د. عام
- [ ] أضف إدخالات الشريط الجانبي في `Navigation.php`
- [ ] اكتب اختبارات (`tests/Feature/Deployments/DeploymentAllowedPathsTest.php`, `SmartDeploymentTest.php`, `BackupsTest.php`)
- [ ] `vendor/bin/pint` + `php artisan test`

---

## 7. الدروس المستفادة والمزالق

| # | المزلق | الحل المُتحقق منه |
|---|---|---|
| 1 | **Gateway timeout 504** عند نشر كبير على استضافة مشتركة | `Http::connectTimeout(10)->timeout(45)` — ينهي PHP قبل قطع nginx (~60s)، مع نافذة حالة Alpine تلتقط الخطأ برسالة عربية |
| 2 | **`exec()` معطّلة** على Hostinger/LiteSpeed | لا `storage:link` عبر Artisan — استخدم `symlink()` الأصلي أو اعتمد على `/media` |
| 3 | **`$dispatch` مع Alpine** | `$dispatch('open-modal', 'name')` — لا تغلّف بـ `{ detail: ... }` |
| 4 | **مطابقة القواعد بين المحلي والسيرفر** | `config/deployment.php → smart` يجب أن يطابق `$INCLUDE_PATHS`/`$EXCLUDE_WITHIN` في `deployer.php` — أي تعديل في أحدها يجب أن ينعكس على الآخر |
| 5 | **استعادة SQLite أثناء الاستخدام** | `copy` إلى مؤقت ثم `rename` ذرّي + `DB::disconnect()/reconnect()` |
| 6 | **المسارات المحفوظة عميقة** | عند حفظ مسار تحت مجلد محدد يُرفع تلقائيًا إلى جذره (`loadAllowedSelection`) — اختيار المجلد يغطي ما بداخله |
| 7 | **تسريب كلمات السر في سجل العمليات** | `DeploymentProcessRunner` يحجب أنماط `*SECRET*`/`*PASSWORD*`/`*TOKEN*` من المخرجات |
| 8 | **المانيفست يخالف المسارات** | `ProjectSnapshot::allowedOnly()` يفلتر اللقطات القديمة للمسارات غير المسموحة — يمنع صفوف "removed" وهمية |
| 9 | **بعد نشر مسارات جديدة** | على السيرفر: احذف الكاش من صفحة الصيانة أو `php artisan optimize:clear` وإلا 404 للمسارات الجديدة |
| 10 | **مجلد النسخ الاحتياطية** | `storage/app/database-backups` يجب أن يبقى خارج لقطات النشر (موجود ضمن `DEFAULT_EXCLUDES` كـ `storage`) |

---

## ملحق: المتغيرات المطلوبة في `.env`

```ini
# النشر الذكي
DEPLOY_SERVER_URL=https://example.com/deployer.php
DEPLOY_SECRET_KEY=<نفس المفتاح داخل deployer.php>

# النسخ الاحتياطية
DATABASE_BACKUP_KEEP=5
```

> ⚠️ **أمان**: `DEPLOY_SECRET_KEY` في `.env` يجب أن يطابق حرفيًا `DEPLOY_SECRET` داخل `public/deployer.php` — ويُفضّل توليد مفتاح عشوائي طويل (`openssl rand -hex 32`).
