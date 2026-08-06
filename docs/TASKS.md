# Tasks

## Active

### Completed
- [x] Install Livewire + Volt
- [x] Install Alpine.js via npm and wire it into `resources/js/app.js`
- [x] Run migrations (users, cache, jobs, user_preferences)
- [x] Create `UserPreference` model and factory
- [x] Create Volt login page (`/login`)
- [x] Create app layout (`/dashboard`) with sidebar
- [x] Create shared Volt sidebar component
- [x] Create shared Volt user-preferences modal component
- [x] Wire preferences persistence and client-side application
- [x] Create layout page-header and breadcrumb components
- [x] Add `lang/ar/ui.php` Arabic translations
- [x] Add mobile sidebar toggle in top bar
- [x] Update progress bar to respect reduced motion
- [x] Update PROGRESS.md and UI_SYSTEM.md
- [x] Write PHPUnit tests for login, dashboard, preferences, and health ping
- [x] Run `npm run build` and `php artisan test --compact`
- [x] Verify login full-screen → dashboard flow in browser
- [x] Sync remaining docs (COMPONENTS.md, DECISIONS.md) with final implementation

## Backlog

### Completed — Aid Tab & User Tracking
- [x] تبويب المساعدات بتصميم 3 أعمدة في صفحة الإنشاء
- [x] جدول `family_aids` ونموذج `FamilyAid`
- [x] مزامنة المساعدات عند الإنشاء/التحديث
- [x] إضافة `updated_by` لجدول الأسر
- [x] تسجيل `created_by`/`updated_by` في `Create.php`
- [x] `FamilyApprovalService` يسجّل `updated_by`
- [x] عرض "الباحث: [name]" في نافذة الاعتماد
- [x] تبويب المساعدات في صفحة المراجعة

### Completed — Re-Assessment System
- [x] جدول `family_assessments` مع رقم تسلسلي
- [x] `ReAssessmentService` لنسخ البيانات
- [x] `approveAssessment()` يحدّث `current_assessment_id`
- [x] صفحة `ReAssessmentIndex`
- [x] صفحة `AssessmentHistory`
- [x] المسارات في `routes/web.php`
- [x] إدخال الشريط الجانبي

### Completed — System Settings
- [x] جدول `system_settings`
- [x] نموذج `SystemSetting`
- [x] صفحة `/settings` عبر `SettingsIndex`

### Completed — Alerts Engine
- [x] جدول `alerts` مع polymorphic
- [x] نموذج `Alert`
- [x] `ReAssessmentAlertService`
- [x] أمر `app:generate-alerts`
- [x] جدولة يومية في `routes/console.php`
- [x] صفحة `/alerts` عبر `AlertsIndex`
- [x] شارات في الشريط الجانبي
- [x] اختبارات `AlertGenerationTest` (6 اختبارات)

### Completed — Documentation
- [x] توثيق شامل في `docs/REASSESSMENT_AND_ALERTS.md`
- [x] تحديث `docs/PROGRESS.md`
- [x] تحديث `docs/TASKS.md`

## Deployments

### Completed — Deployment System Phases 1-5
- [x] `EnsureAdmin` middleware + alias `admin` في `bootstrap/app.php`
- [x] Enums: `ReleaseStatus`, `ReleaseChangeType`, `DeploymentEnvironment`, `DeploymentStatus`, `DeploymentStepStatus`
- [x] Migrations: `releases`, `release_changes`, `deployments`, `deployment_steps`
- [x] Models + Factories: `Release`, `ReleaseChange`, `Deployment`, `DeploymentStep`
- [x] `ReleaseService` (create/publish/rollBack/delete في معاملة)
- [x] مكونات Livewire: `Deployments/Index`, `CreateRelease`, `ShowRelease`
- [x] مسارات `/deployments` (index/create/show) محمية بـ EnsureAdmin
- [x] `config/deployment.php`: بيئات + allowlist أوامر/مسارات + step_labels + إعدادات job
- [x] `DeploymentService` (حجز pending، رفض التزامن، dispatch بعد commit)
- [x] `DeploymentPathGuard` (رفض مطلق/`..`/روابط رمزية خارجة)
- [x] `DeploymentProcessRunner` (allowlist + تنقية أسرار + حد حجم 8KB)
- [x] `RunDeploymentJob` (WithoutOverlapping، خطوات فعلية، فشل/تخطي)
- [x] `CleanupStaleDeployments` + جدولة كل 5 دقائق
- [x] مؤشر التقدم: `wire:poll.2.5s` شرطي، شريط نسبة حقيقية، خطوة حالية، سجل خطوات، مخرجات فشل
- [x] 50 اختبارًا في `tests/Feature/Deployments` (كلها ناجحة)
- [x] إصلاح: مسح ذاكرة إعدادات كانت تشير إلى `:memory:`

### Completed — Phase 6: التشغيل التجريبي (كامل)
- [x] تجربة النشر الفعلي على `testing`: إصدار تجريبي #1 → نشر #1 مكتمل 100% (خطوتا migrate + cache تنفذتا فعليًا عبر Queue Worker حقيقي)
- [x] تجربة النشر الفعلي على `staging`: نشر #2 مكتمل 100%
- [x] تجربة النشر الفعلي على `production`: الإصدار #2 (1.1.0-prod) → نشر #4 مكتمل 100% (17:06، ثانية واحدة)
- [x] تشغيل Queue Worker حقيقي (database) ونفّذ `RunDeploymentJob` 3 مرات بنجاح
- [x] التحقق من rollback: الإصدار #1 أصبح «متراجع عنه» في المتصفح واختفى زر النشر
- [x] مراجعة السجلات: لا أخطاء من النشر، لا تسريب أسرار (تنقية تلقائية)، ذاكرة الإعدادات بعد `optimize` تشير لـ `database/database.sqlite`
- [x] التحقق من الصلاحيات: المسارات الثلاثة محمية بـ EnsureAdmin
- [x] **اعتماد المالك (2026-08-04)**: قائمة أوامر الإنتاج = `migrate + cache` فقط (موثّقة في config/deployment.php) + Queue Worker على جهاز Herd المحلي (`php artisan queue:work`)
- [x] الاختبارات النهائية: 50/50 ناجحة، Pint نظيف

### Completed — Phase 7: أداة تنفيذ سريعة (استيراد تلقائي + حزمة ZIP)
- [x] **الهدف**: بعد كل تعديل كود، بدل البحث اليدوي عن الملفات ورفعها واحدًا واحدًا → النظام يكتشف الملفات تلقائيًا ويجهّز ملف ZIP واحد للرفع على cPanel
- [x] **مبدأ العمل**: مقارنة لقطات (snapshots) — المشروع ليس Git، لذا نحفظ `file_snapshot` (مسار → hash) مع كل إصدار ونقارن
- [x] هجرة `2026_08_04_180000_add_file_snapshot_to_releases_table` (عمود JSON `file_snapshot` في `releases`)
- [x] `App\Support\Deployment\ProjectSnapshot::scan()` — يمسح المشروع ويستثني `vendor`, `node_modules`, `storage`, `bootstrap/cache`, `.git`, `.env`, `.DS_Store`, `Thumbs.db` وغيرها (يشمل `public/build` لضمان رفع ملفات Vite)
- [x] `ProjectSnapshot::changesSince()` — يكشف المضاف/المعدّل/المحذوف بين لقطتين
- [x] `ReleaseService::create()` — يخزّن اللقطة تلقائيًا مع كل إصدار جديد
- [x] `ReleaseService::detectChanges()` — يقارن الملفات الحالية بآخر إصدار محفوظ
- [x] `CreateRelease::importChanges()` + زر «استيراد التغييرات تلقائيًا» في صفحة الإنشاء (يملأ الجدول بالملفات المكتشفة، الوصف يُعبأ تلقائيًا)
- [x] `App\Services\Deployment\UploadPackageService::build()` — ينشئ ZIP يحتوي كل ملفات الإصدار (مساراته داخل `storage/app/deployment-packages`)، مع `REMOVED_FILES.txt` للملفات المحذوفة و`NOTE.txt` عند عدم وجود ملفات، ويتجاهل المسارات الخطرة (`..`/مطلقة/غير موجودة) مع تحذير
- [x] `ShowRelease::prepareUploadPackage()` + زر «حزمة الرفع ZIP» في صفحة التفاصيل (يُنزّل الحزمة فورًا ويعرض ملخصًا)
- [x] اختبارات جديدة: `tests/Unit/ProjectSnapshotTest` (3)، `tests/Feature/Deployments/UploadPackageServiceTest` (5)، `tests/Feature/Deployments/ReleaseSnapshotImportTest` (3) = 11 اختبارًا
- [x] تحقق مباشر في المتصفح: الاستيراد التلقائي ملأ الجدول بالملف المضاف، والحزمة ZIP نُزّلت وفحص محتواها (`docs/phase7-demo.txt` داخل الحزمة)
- [x] **النتيجة النهائية**: 224/224 اختبارًا ناجحًا، Pint نظيف

### Completed — فصل السوبر أدمن: منطقة تقنية مستقلة
- [x] **المشكلة**: أدوات النشر كانت داخل منطقة أدمن الجمعية رغم أنها أداة تقنية خاصة بالسوبر أدمن
- [x] دور جديد `super_admin` في `User` + `isSuperAdmin()` + `scopeSuperAdmins()` + حالة `superAdmin()` في المصنع
- [x] `EnsureSuperAdmin` middleware + alias `super_admin` في `bootstrap/app.php`
- [x] المسارات انتقلت إلى `/superadmin-dashboard` (أسماء المسارات ثابتة: `deployments.*`)
- [x] قائمة «الإدارة التقنية» في الشريط الجانبي للسوبر أدمن فقط — بدون أي بيانات خيرية
- [x] إخفاء إحصائيات الجمعية (الشريط العلوي) عن السوبر أدمن + تحويل لوحة التحكم لصفحة الإصدارات
- [x] توجيه السوبر أدمن بعد الدخول مباشرة إلى صفحة الإصدارات
- [x] منع أدمن الجمعية: 403 عند زيارة مسارات السوبر أدمن (طبقتا حماية: Navigation + middleware)
- [x] حساب جديد: `superadmin` / `password` (يُنصح بتغيير كلمة المرور)
- [x] الاختبارات: تحويل 8 ملفات اختبار + اختبارا منع جديدان → 225/225 ناجحة (650 تأكيدًا)
- [x] تحقق في المتصفح: سوبر أدمن → صفحة الإصدارات فقط، أدمن الجمعية → لوحة خيرية بدون أي أثر للنشر، الوصول المباشر → 403

