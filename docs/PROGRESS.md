# Progress

## Phase 1: Inspection & Documentation ✅
- Inspected full project structure
- Created UI_IMPLEMENTATION_PLAN.md, UI_SYSTEM.md, TASKS.md, THEME_TOKENS.md, COMPONENTS.md, DECISIONS.md, ACCESSIBILITY.md

## Phase 2: Foundation ✅
- ✅ Installed Livewire v4
- ✅ Installed Alpine.js via npm and imported in `resources/js/app.js`
- ✅ Ran migrations
- ✅ Created `user_preferences` table migration
- ✅ Created `UserPreference` model with `User` relationship
- ✅ Created Volt login page
- ✅ Set app locale to `ar` and configured RTL direction via `lang/ar/ui.php` and `<html dir="rtl">`

## Phase 3: Design Tokens ✅
- ✅ CSS variables in `resources/css/app.css` (brown primary palette, semantic colors, motion, radius, z-index, sidebar sizes)
- ✅ 8 accent color presets via `data-accent`
- ✅ Density modes (`compact`, `balanced`, `spacious`) via `data-ui-density`
- ✅ Font size modes (`small`, `medium`, `large`) via `data-font-size`
- ✅ Reduced motion support via `data-reduced-motion`

## Phase 4: Login UI ✅
- ✅ Full-screen login page at `/login` using Volt (`resources/views/livewire/pages/login.blade.php`)
- ✅ Brown gradient background, glass card, username/password, loading state, validation
- ✅ Guest-only access; authenticated users redirected to `/dashboard`

## Phase 5: App Shell ✅
- ✅ Main app layout with sidebar + content area
- ✅ Sidebar component with active state, collapse state, mobile drawer
- ✅ Top bar with title slot and mobile toggle
- ✅ Mobile sidebar open via `toggle-mobile-sidebar` event
- ✅ User menu button opens preferences modal

## Phase 6: Global States ✅
- ✅ Theme manager in `resources/js/app.js` applies accent/density/font/reduced-motion via `window.applyPreferences()`
- ✅ Preferences persisted to DB (`UserPreference`) and dispatched as `preferences-applied`
- ✅ JS listens to `livewire:preferences-applied` and updates `<html>` attributes + localStorage
- ✅ Progress bar in `resources/js/app.js` respects reduced motion
- ✅ Connectivity/session placeholders prepared

## Phase 7: Core UI Components ✅
- ✅ 28+ reusable Blade UI components in `resources/views/components/ui/`
- ✅ New layout components: `page-header` and `breadcrumb` livewire shared

## Phase 8: UI Kit ✅
- ✅ Button, Input, Select, Switch, Modal, Alert, Badge, Card, Table, etc.
- ✅ Consistent use of CSS variables and Tailwind utility classes
- ✅ RTL-friendly icons and layout

## Phase 9: Demo Screens ✅
- ✅ `/dashboard` placeholder page
- ✅ `/health/ping` endpoint
- ✅ Auth routes in `routes/web.php`

## Phase 10: Review
- 🔄 Running tests and build validation
- 🔄 Verifying full-screen login → dashboard flow

## Phase 11: Aid Tab UI & Data Persistence ✅
- ✅ تبويب المساعدات بتصميم 3 أعمدة (نوع المساعدة / الاستحقاق نعم-لا / الأسباب)
- ✅ جدول `family_aids` مع نموذج `FamilyAid`
- ✅ مزامنة المساعدات عند إنشاء/تحديث الأسرة
- ✅ تبويب المساعدات في صفحة المراجعة

## Phase 12: تتبع المستخدمين ✅
- ✅ إضافة `updated_by` لجدول الأسر
- ✅ تسجيل `created_by` و `updated_by` في `Create.php`
- ✅ `FamilyApprovalService` يسجّل `updated_by` في الاعتماد/الرفض/الإعادة
- ✅ عرض "الباحث: [name]" في نافذة الاعتماد أسفل حقل الملاحظات

## Phase 13: نظام إعادة التقييم ✅
- ✅ جدول `family_assessments` مع رقم تسلسلي (round 1, 2, 3...)
- ✅ `ReAssessmentService` لنسخ البيانات من آخر تقييم معتمد
- ✅ `approveAssessment()` يحدّث `current_assessment_id`
- ✅ صفحة `ReAssessmentIndex` لعرض الأسر المعتمدة وبدء إعادة التقييم
- ✅ صفحة `AssessmentHistory` لعرض خط زمني للتقييمات
- ✅ المسارات في `routes/web.php` (قبل {family} catch-all)
- ✅ إدخال "إعادة التقييم" في الشريط الجانبي

## Phase 14: نظام الإعدادات ✅
- ✅ جدول `system_settings` (key-value مع نوع قيمة)
- ✅ نموذج `SystemSetting` مع `get()` و `set()`
- ✅ صفحة `/settings` عبر `SettingsIndex` Livewire
- ✅ إعداد فترة إعادة التقييم بالأشهر (1-24، افتراضي 3)

## Phase 15: محرك التنبيهات الدورية ✅
- ✅ جدول `alerts` مع علاقة متعددة الأشكال (polymorphic)
- ✅ نموذج `Alert` مع ثوابت، نطاقات، ودوال مساعدة
- ✅ `ReAssessmentAlertService` لتوليد تنبيهات إعادة التقييم
- ✅ أمر `php artisan app:generate-alerts`
- ✅ جدولة يومية الساعة 02:00 في `routes/console.php`
- ✅ صفحة `/alerts` عبر `AlertsIndex` Livewire مع تصفية وتجاهل/حل
- ✅ شارات حمراء في الشريط الجانبي (إعادة التقييم + التنبيهات)
- ✅ 6 اختبارات في `AlertGenerationTest` — جميع الاختبارات (32) ناجحة

## Phase 16: التوثيق ✅
- ✅ توثيق شامل في `docs/REASSESSMENT_AND_ALERTS.md`

## Phase 17: Delivery Workflow State 🔄
- 🔄 Route `/delivery` موجود كـ placeholder باسم `delivery.index`
- 🔄 الدور المنطقي لهذه الصفحة هو مرحلة التنفيذ والتسليم بعد الاعتماد
- 🔄 البيانات الجاهزة لهذا الفلو موجودة بالفعل في `AidRequestStatus` و `AidRequestItem`
- 🔄 `AidRequestStatus` يدعم `in_execution` و `delivered` و `completed`
- 🔄 `AidRequestItem` يدعم `delivered`, `delivery_date`, `delivery_notes`, `delivered_by`
- 🔄 الشريط الجانبي يعرض بند `التنفيذ والمتابعة` ضمن مجموعة `الحالات والمساعدات`
- 🔄 المطلوب في الجلسة القادمة: تحويل `/delivery` من placeholder إلى صفحة تشغيل فعلية تعرض الطلبات الجاهزة للتنفيذ، ما تحت التنفيذ، ما تم تسليمه، والمتأخرات

## Phase 18: نظام إدارة النشر — المراحل 1-5 ✅
- ✅ **المرحلة 1 — التوافق والبنية**: دور `admin`، `EnsureAdmin` middleware (مسار `admin`)، 4 Enums (ReleaseStatus, ReleaseChangeType, DeploymentEnvironment, DeploymentStatus)، 3 جداول (releases, release_changes, deployments)، Models وFactories
- ✅ **المرحلة 2 — إدارة الإصدارات**: `ReleaseService` (إنشاء/اعتماد/تراجع/حذف آمن في معاملة)، مكونات Index/Create/Show، مسارات `/deployments` محمية بـ EnsureAdmin، ترجمات عربية
- ✅ **المرحلة 3 — سجل النشر**: `config/deployment.php` (بيئات + allowlist)، `DeploymentService` (حجز pending، رفض المتزامن، تسجيل الحالة)، نافذة نشر في ShowRelease
- ✅ **المرحلة 4 — التنفيذ غير المتزامن**: جدول `deployment_steps`، `DeploymentStepStatus`، `DeploymentPathGuard` (رفض المسارات المطلقة/`..`/الروابط الرمزية الخارجة)، `DeploymentProcessRunner` (allowlist + تنقية الأسرار + حد الحجم)، `RunDeploymentJob` (WithoutOverlapping + تتبع خطوة بخطوة)، `CleanupStaleDeployments` مجدول كل 5 دقائق
- ✅ **المرحلة 5 — مؤشر التقدم**: `wire:poll.2.5s` يتوقف تلقائيًا عند الحالة النهائية، شريط تقدم من النسبة الفعلية `progressPercentage()`، الخطوة الحالية `currentStep()`، سجل خطوات بأيقونات الحالة، عرض مخرجات الفشل
- ✅ **الاختبارات**: 50 اختبارًا في `tests/Feature/Deployments` + 213 إجماليًا ناجحًا، Pint نظيف
- ✅ **إصلاح أثناء المرحلة 5**: ذاكرة الإعدادات كانت تعيد توجيه sqlite إلى `:memory:` — تم مسحها (`php artisan config:clear`) ليعمل النشر الفعلي ضد `database/database.sqlite`

## Phase 19: نظام إدارة النشر — المرحلة 6 (التشغيل التجريبي) ✅
- ✅ **نشر فعلي على `testing`**: إصدار تجريبي #1 → نشر #1 اكتمل 100% عبر Queue Worker حقيقي (database)
- ✅ **نشر فعلي على `staging`**: نشر #2 اكتمل 100%
- ✅ **نشر فعلي على `production`**: الإصدار #2 (1.1.0-prod) → نشر #4 اكتمل 100% (خطوتا migrate + optimize، مخرجات حقيقية نظيفة)
- ✅ **rollback**: الإصدار #1 أصبح «متراجع عنه» في المتصفح
- ✅ **السجلات**: لا أخطاء، لا تسريب أسرار، ذاكرة الإعدادات سليمة (database/database.sqlite)
- ✅ **الصلاحيات**: المسارات الثلاثة محمية بـ EnsureAdmin
- ✅ **اعتماد المالك**: قائمة أوامر الإنتاج = migrate + cache فقط + Queue Worker على Herd المحلي
- ✅ الاختبارات: 50/50 ناجحة (213 إجماليًا)، Pint نظيف

## Phase 20: نظام إدارة النشر — المرحلة 7 (أداة تنفيذ سريعة) ✅
- ✅ **المشكلة**: رفع الملفات المعدّلة يدويًا عبر FTP في كل تحديث — بحث بطيء عن الملفات + وقت طويل
- ✅ **الحل 1 — استيراد تلقائي للملفات**: عمود `file_snapshot` (JSON) في `releases` يُحفظ مع كل إصدار، و`ProjectSnapshot::changesSince()` يقارن اللقطات ليكشف المضاف/المعدّل/المحذوف — ثم زر «استيراد التغييرات تلقائيًا» في صفحة الإنشاء يملأ الجدول فورًا (الوصف يُعبأ تلقائيًا، وقابل للتعديل)
- ✅ **الحل 2 — حزمة رفع ZIP**: زر «حزمة الرفع ZIP» في صفحة تفاصيل الإصدار يُنشئ ملف ZIP واحدًا فيه كل ملفات الإصدار بمساراتها الصحيحة (+ `REMOVED_FILES.txt` للملفات المحذوفة، و`NOTE.txt` عند عدم وجود ملفات) ويُنزّله فورًا
- ✅ **الأمان**: استثناء `vendor`, `node_modules`, `storage`, `public/build`, `.env`, `.DS_Store` وغيرها + تجاهل المسارات المطلقة/`..`/غير الموجودة مع تحذير
- ✅ **اختبارات جديدة**: 11 (Unit + Feature) → الإجمالي 224/224 ناجحًا، Pint نظيف
- ✅ **تحقق مباشر في المتصفح**: الاستيراد كشف `docs/phase7-demo.txt` تلقائيًا، والحزمة ZIP نُزّلت وفُحص محتواها بنجاح
- ✅ بيانات العرض نُظّفت (بقي الإصدار الأساسي v0.1.0 كمرجع للمقارنة)

## Phase 21: فصل السوبر أدمن — منطقة تقنية مستقلة ✅
- ✅ **المشكلة**: أدوات النشر كانت داخل منطقة أدمن الجمعية (`/deployments` ضمن «إدارة النظام») رغم أنها أداة تقنية خاصة بالسوبر أدمن
- ✅ **دور جديد**: `ROLE_SUPER_ADMIN = 'super_admin'` في `User` + `isSuperAdmin()` + `scopeSuperAdmins()` + حالة `superAdmin()` في المصنع
- ✅ **حماية**: `EnsureSuperAdmin` middleware + alias `super_admin` في `bootstrap/app.php` — المسارات انتقلت إلى `/superadmin-dashboard` (أسماء المسارات ثابتة: `deployments.*`)
- ✅ **قائمة جانبية مستقلة**: مجموعة «الإدارة التقنية» تظهر للسوبر أدمن فقط، والسوبر أدمن لا يرى أي مجموعة خيرية (لوحة التحكم/الحالات/المشروعات/إدارة النظام)
- ✅ **منع أدمن الجمعية**: `Navigation::canAccessRoute()` يرفض مسارات السوبر أدمن صراحةً (403) + فحص `isSuperAdmin()` داخل مكونات Livewire الثلاثة
- ✅ **إخفاء بيانات الجمعية**: إحصائيات الشريط العلوي (بانتظار الاعتماد/إعادة التقييم/طلبات جديدة) مخفية عن السوبر أدمن، ولوحة التحكم تعيد توجيهه لصفحة الإصدارات
- ✅ **حساب جديد**: `superadmin` / `password` (اسم «السوبر أدمن التقني») — يُنصح بتغيير كلمة المرور
- ✅ **توجيه بعد الدخول**: السوبر أدمن ينتقل مباشرة لصفحة الإصدارات، وأدمن الجمعية يبقى على لوحة التحكم الخيرية
- ✅ **الاختبارات**: تحويل 8 ملفات اختبار إلى `superAdmin()` + اختبارا منع جديدان لأدمن الجمعية → 225/225 ناجحة (650 تأكيدًا)، Pint نظيف
- ✅ **تحقق في المتصفح**: سوبر أدمن → صفحة الإصدارات فقط، أدمن الجمعية → لوحة خيرية بدون أي أثر للنشر، والوصول المباشر لمسار السوبر أدمن → 403، والرابط القديم `/deployments` → 404
- ⚠️ **تنبيه**: قائمة `file_snapshot` أُعيد إنشاؤها (470 ملفًا) بعد مسح قاعدة البيانات — لا تشغّل `migrate:fresh` بدون حاجة
