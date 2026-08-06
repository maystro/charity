# توثيق نظام إعادة التقييم والتنبيهات الدورية

توثيق شامل لما تم تنفيذه في نظام إدارة الحالات الخيرية — يشمل نظام إعادة التقييم بنسخ البيانات، ونظام التنبيهات الدورية، وصفحة الإعدادات.

---

## جدول المحتويات

1. [نظرة عامة](#1-نظرة-عامة)
2. [نظام إعادة التقييم (Re-Assessment)](#2-نظام-إعادة-التقييم-re-assessment)
3. [نظام التنبيهات الدورية (Alerts Engine)](#3-نظام-التنبيهات-الدورية-alerts-engine)
4. [نظام الإعدادات (System Settings)](#4-نظام-الإعدادات-system-settings)
5. [قاعدة البيانات والترحيلات](#5-قاعدة-البيانات-والترحيلات)
6. [النماذج (Models)](#6-النماذج-models)
7. [الخدمات (Services)](#7-الخدمات-services)
8. [مكونات Livewire](#8-مكونات-livewire)
9. [الواجهات (Views/Blade)](#9-الواجهات-viewsblade)
10. [المسارات (Routes)](#10-المسارات-routes)
11. [الأوامر المجدولة (Scheduled Commands)](#11-الأوامر-المجدولة-scheduled-commands)
12. [الاختبارات (Tests)](#12-الاختبارات-tests)
13. [تكامل الشريط الجانبي (Sidebar)](#13-تكامل-الشريط-الجانبي-sidebar)
14. [ملخص الملفات المنشأة/المعدلة](#14-ملخص-الملفات-المنشأةالمعدلة)

---

## 1. نظرة عامة

تم تنفيذ ثلاثة أنظمة متكاملة:

| النظام | الوصف |
|--------|-------|
| **إعادة التقييم** | نسخ بيانات آخر تقييم معتمد إلى تقييم جديد برقم تسلسلي (round 1, 2, 3...)، مع بقاء الأسرة معتمدة حتى اعتماد التقييم الجديد |
| **التنبيهات الدورية** | محرك يفحص الأسر المعتمدة ويولّد تنبيهات عند استحقاق إعادة التقييم (كل ٣ شهور افتراضيًا، قابلة للتعديل من الإعدادات) |
| **الإعدادات** | صفحة لإدارة إعدادات النظام، أولها فترة إعادة التقييم بالأشهر |

### القواعد المعتمدة (Confirmed Rules)

1. **نسخ البيانات**: عند بدء إعادة تقييم، تُنسخ جميع البيانات من آخر تقييم معتمد (الأفراد، مصادر الدخل، الموارد، الأعباء، السكن، المساعدات)
2. **بقاء الحالة معتمدة**: الأسرة تبقى بحالة "معتمدة" حتى يُعتمد التقييم الجديد
3. **أرقام تسلسلية**: كل تقييم يأخذ رقمًا تسلسليًا (round = 1, 2, 3...)
4. **عدم التعديل بعد الاعتماد**: لا يمكن الرجوع لتعديل الملف بعد الاعتماد إلا من خلال إعادة التقييم

---

## 2. نظام إعادة التقييم (Re-Assessment)

### المخطط المعماري (Architecture Pattern)

```
Family (1) ──→ current_assessment_id ──→ FamilyAssessment (round 1, 2, 3...)
                                              │
                                              ├──→ FamilyMember (family_assessment_id)
                                              ├──→ FamilyIncomeSource (family_assessment_id)
                                              ├──→ FamilyResource (family_assessment_id)
                                              ├──→ FamilyBurden (family_assessment_id)
                                              ├──→ FamilyHousing (family_assessment_id)
                                              └──→ FamilyAid (family_assessment_id)
```

### تدفق العمل (Workflow)

1. **البدء**: من صفحة `إعادة التقييم`، يضغط المستخدم على زر إعادة التقييم لأحد الأسر المعتمدة
2. **النسخ**: `ReAssessmentService::startReAssessment()` ينسخ جميع البيانات من التقييم الحالي إلى تقييم جديد برقم تسلسلي أعلى
3. **التحرير**: يُعاد توجيه المستخدم إلى صفحة التحرير لتعديل البيانات المنسوخة
4. **الاعتماد**: بعد المراجعة، يُعتمد التقييم الجديد عبر `ReAssessmentService::approveAssessment()` الذي يحدّث `current_assessment_id` في جدول الأسر

### الجداول الفرعية المنسوخة

| الجدول | الوصف |
|--------|-------|
| `family_members` | أفراد الأسرة (الاسم، الهوية، الصلة، المهنة، الدخل) |
| `family_income_sources` | مصادر الدخل (النوع، الحالة، المبلغ، ملاحظات) |
| `family_resources` | الموارد (النوع، الكمية، الحالة، ملاحظات) |
| `family_burdens` | الأعباء (النوع، المبلغ، ملاحظات) |
| `family_housing` | بيانات السكن (النوع، الحالة، الطوابق، الغرف، المرافق...) |
| `family_aids` | المساعدات (النوع، الاستحقاق، الأسباب) |

---

## 3. نظام التنبيهات الدورية (Alerts Engine)

### آلية العمل

```
┌─────────────────────────────────────────────────────────┐
│  Schedule (daily 02:00)                                 │
│         │                                               │
│         ▼                                               │
│  php artisan app:generate-alerts                        │
│         │                                               │
│         ▼                                               │
│  ReAssessmentAlertService::generate()                  │
│         │                                               │
│         ├──→ Fetch approved families with assessment    │
│         ├──→ Compare approved_at + interval_months      │
│         ├──→ If due_at is past → TYPE_REASSESSMENT_OVERDUE │
│         ├──→ If due_at is approaching → TYPE_REASSESSMENT_DUE │
│         ├──→ Skip if not yet due                        │
│         ├──→ Resolve stale alerts if family re-assessed  │
│         └──→ Avoid duplicate active alerts              │
└─────────────────────────────────────────────────────────┘
```

### أنواع التنبيهات

| النوع | الثابت | الخطورة | الوصف |
|------|--------|---------|-------|
| استحقاق إعادة التقييم | `TYPE_REASSESSMENT_DUE` | warning | حان موعد إعادة التقييم |
| تأخر إعادة التقييم | `TYPE_REASSESSMENT_OVERDUE` | critical | تجاوز موعد إعادة التقييم |

### حالات التنبيه

| الحالة | الثابت | الوصف |
|--------|--------|-------|
| نشط | `STATUS_ACTIVE` | التنبيه معروض ويحتاج إجراء |
| تم تجاهله | `STATUS_DISMISSED` | تم تجاهله يدويًا من المستخدم |
| تم حله | `STATUS_RESOLVED` | تم حله (تلقائيًا عند إعادة التقييم أو يدويًا) |

### العلاقة متعددة الأشكال (Polymorphic)

نظام التنبيهات يستخدم `morphTo` بحيث يمكن ربط التنبيه بأي نموذج:

```php
$alert->alertable_type = Family::class;
$alert->alertable_id   = $family->id;
```

هذا يسمح مستقبلاً بإضافة تنبيهات لأنواع أخرى (طلبات مساعدة، زيارات ميدانية، إلخ).

---

## 4. نظام الإعدادات (System Settings)

### نموذج التخزين

نظام الإعدادات يستخدم نمط key-value مع نوع قيمة قابل للتحويل:

| المفتاح | النوع | الافتراضي | الوصف |
|---------|------|-----------|-------|
| `reassessment_interval_months` | integer | 3 | عدد الأشهر بين كل إعادة تقييم |

### واجهة الإعداد

صفحة `/settings` تحتوي على:
- حقل إدخال فترة إعادة التقييم (1-24 شهرًا)
- زر حفظ يخزّن القيمة عبر `SystemSetting::set()`

---

## 5. قاعدة البيانات والترحيلات

### الترحيلات المنشأة

| الملف | الوصف |
|------|-------|
| `add_assessment_relations_to_tables` | إضافة `current_assessment_id` لجدول الأسر، و`family_assessment_id` للجداول الفرعية |
| `create_family_assessments_table` | جدول التقييمات (round, status, approved_at...) |
| `create_system_settings_table` | جدول الإعدادات (key, value, type, group, label, description) |
| `create_alerts_table` | جدول التنبيهات (type, severity, status, alertable_type, alertable_id, due_at...) |

### مخطط جدول التنبيهات

```sql
alerts:
  id              (PK)
  type            (varchar) — نوع التنبيه
  title           (varchar) — العنوان
  message         (text)   — الرسالة
  severity        (varchar) — info | warning | critical
  status          (varchar) — active | dismissed | resolved
  alertable_type  (varchar) — morphTo type
  alertable_id    (bigint)  — morphTo id
  created_by      (bigint, nullable)
  due_at          (datetime, nullable)
  dismissed_at    (datetime, nullable)
  timestamps
```

---

## 6. النماذج (Models)

### `Family` — `app/Models/Family.php`

العلاقات المضافة:
- `assessments()` — جميع التقييمات مرتبة تنازليًا بالرقم التسلسلي
- `currentAssessment()` — التقييم الحالي (belongsTo عبر `current_assessment_id`)

الحقول المضافة إلى `$fillable`:
- `current_assessment_id`

### `FamilyAssessment` — `app/Models/FamilyAssessment.php`

نموذج التقييم مع:
- `round` — الرقم التسلسلي (1, 2, 3...)
- `status` — حالة التقييم
- `approved_at` — تاريخ الاعتماد
- علاقات: `family()`, `members()`, `incomeSources()`, `resources()`, `burdens()`, `housing()`, `aids()`
- علاقات المستخدمين: `creator()`, `submitter()`, `approver()`

### `Alert` — `app/Models/Alert.php`

```php
// الثوابت
TYPE_REASSESSMENT_DUE, TYPE_REASSESSMENT_OVERDUE
SEVERITY_INFO, SEVERITY_WARNING, SEVERITY_CRITICAL
STATUS_ACTIVE, STATUS_DISMISSED, STATUS_RESOLVED

// العلاقات
alertable() — MorphTo

// النطاقات (Scopes)
active(), dismissed(), resolved(), forType(), forAlertable(), overdue()

// الدوال المساعدة
dismiss(), resolve(), isActive(), isOverdue()
```

### `SystemSetting` — `app/Models/SystemSetting.php`

```php
// دوال ثابتة
get(key, default) — قراءة قيمة مع تحويل النوع
set(key, value, group, label, type, description) — كتابة قيمة
```

---

## 7. الخدمات (Services)

### `ReAssessmentService` — `app/Services/Families/ReAssessmentService.php`

| الدالة | الوصف |
|--------|-------|
| `startReAssessment(Family)` | تنشئ تقييمًا جديدًا بنسخ جميع البيانات من التقييم الحالي |
| `approveAssessment(FamilyAssessment, notes)` | تعتمد التقييم، تحدّث `current_assessment_id`، تسجل في سجل الحالات |

### `ReAssessmentAlertService` — `app/Services/Alerts/ReAssessmentAlertService.php`

| الدالة | الوصف |
|--------|-------|
| `generate()` | تفحص الأسر المعتمدة وتولّد/تحلّ التنبيهات حسب فترة الإعادة |
| `titleFor(type)` | عنوان التنبيه حسب النوع |
| `messageFor(family, type, dueAt)` | رسالة التنبيه مع اسم الأسرة وتاريخ الاستحقاق |

**منطق `generate()`:**
1. يقرأ `reassessment_interval_months` من الإعدادات (افتراضي 3)
2. يجلب جميع الأسر المعتمدة التي لها `current_assessment_id`
3. لكل أسرة: يحسب `due_at = approved_at + interval_months`
4. إذا كان `due_at` في المستقبل → لا تنبيه (ويحل أي تنبيه قديم)
5. إذا كان `due_at` في الماضي → `TYPE_REASSESSMENT_OVERDUE` (critical)
6. يتحقق من عدم وجود تنبيه نشط مكرر
7. إذا كان الترقية من due إلى overdue → يحل التنبيه القديم وينشئ جديد

### `FamilyApprovalService` — `app/Services/Families/FamilyApprovalService.php`

محدّث ليسجّل `updated_by` في جميع العمليات:
- `approve()` — `updated_by => Auth::id()`
- `returnForCompletion()` — `updated_by => Auth::id()`
- `reject()` — `updated_by => Auth::id()`

---

## 8. مكونات Livewire

### `ReAssessmentIndex` — `app/Livewire/Families/ReAssessmentIndex.php`

- يعرض الأسر المعتمدة التي لها تقييم حالي
- `startReAssessment(familyId)` — يستدعي `ReAssessmentService` ويوجّه لصفحة التحرير
- بحث برقم الحالة أو الاسم أو الهاتف
- ترقيم صفحات (10 لكل صفحة)

### `AssessmentHistory` — `app/Livewire/Families/AssessmentHistory.php`

- يعرض خط زمني لجميع تقييمات الأسرة
- لكل تقييم: الرقم التسلسلي، الحالة، ملخص البيانات، معلومات الاعتماد/الرفض

### `AlertsIndex` — `app/Livewire/Alerts/AlertsIndex.php`

- يعرض التنبيهات مع تبويبات تصفية (نشطة/متأخرة/تم تجاهلها/تم حلها)
- `dismissAlert(alertId)` — تجاهل تنبيه
- `resolveAlert(alertId)` — حل تنبيه
- عدادات لكل تصنيف

### `SettingsIndex` — `app/Livewire/Settings/SettingsIndex.php`

- `reassessmentIntervalMonths` — خاصية مرتبطة بحقل الإدخال
- `save()` — يخزّن القيمة عبر `SystemSetting::set()`
- التحقق: عدد صحيح بين 1 و 24

### `Create` — `app/Livewire/Families/Create.php`

محدّث ليسجّل:
- `created_by` و `updated_by` عند الإنشاء
- `updated_by` عند التحديث
- مزامنة المساعدات عبر `FamilyAid::create()`

---

## 9. الواجهات (Views/Blade)

### `resources/views/livewire/pages/families/create.blade.php`

تبويب المساعدات (Aid Tab) بتصميم 3 أعمدة:
1. **نوع المساعدة** — قائمة من `AidType::cases()`
2. **الاستحقاق (نعم/لا)** — مفاتيح تبديل
3. **الأسباب** — حقل نصي

### `resources/views/livewire/pages/families/re-assessment-index.blade.php`

جدول الأسر المعتمدة مع:
- رقم الحالة، الاسم، المنطقة، الهاتف
- شارة آخر تقييم (round number)
- عدد التقييمات
- تاريخ آخر تقييم
- أزرار: عرض التاريخ، إعادة تقييم

### `resources/views/livewire/pages/families/assessment-history.blade.php`

خط زمني للتقييمات:
- الرقم التسلسلي مع شارة الحالة
- شبكة ملخص البيانات (الأفراد، الدخل، السكن...)
- معلومات الاعتماد/الرفض (المستخدم، التاريخ، الملاحظات)

### `resources/views/livewire/pages/families/review-show.blade.php`

نافذة الاعتماد تعرض:
- "الباحث: [اسم الباحث]" أسفل حقل الملاحظات
- تبويب المساعدات المقترحة بجدول 3 أعمدة

### `resources/views/livewire/pages/alerts/index.blade.php`

صفحة التنبيهات:
- تبويبات تصفية مع عدادات
- قائمة تنبيهات بأيقونات حسب الخطورة (critical/warning/info)
- شارات الحالة (متأخر/تم تجاهل/تم حل)
- روابط للأسرة المرتبطة
- أزرار: حل، تجاهل

### `resources/views/livewire/pages/settings/index.blade.php`

بطاقة إعدادات:
- حقل فترة إعادة التقييم
- زر حفظ

---

## 10. المسارات (Routes)

### `routes/web.php`

```php
// إعادة التقييم (قبل {family} catch-all)
Route::get('/families/re-assessment', FamilyReAssessmentIndex::class)
    ->name('families.re-assessment-index');

// تاريخ التقييمات
Route::get('/families/{family}/assessment-history', FamilyAssessmentHistory::class)
    ->name('families.assessment-history');

// التنبيهات
Route::get('/alerts', AlertsIndex::class)->name('alerts.index');

// الإعدادات
Route::get('/settings', SettingsIndex::class)->name('settings.index');
```

> **مهم**: مسار `re-assessment` يجب أن يسبق مسار `{family}` catch-all لتجنب 404.

---

## 11. الأوامر المجدولة (Scheduled Commands)

### `app/Console/Commands/GenerateAlerts.php`

```bash
php artisan app:generate-alerts
```

يستدعي `ReAssessmentAlertService::generate()` ويعرض ملخص النتائج.

### `routes/console.php`

```php
Schedule::command('app:generate-alerts')
    ->dailyAt('02:00')
    ->description('فحص وتوليد تنبيهات إعادة التقييم');
```

يعمل يوميًا الساعة 02:00 صباحًا.

---

## 12. الاختبارات (Tests)

### `tests/Feature/AlertGenerationTest.php` — 6 اختبارات

| الاختبار | الوصف |
|---------|-------|
| `test_no_alerts_for_recently_approved_family` | لا تنبيهات للأسر المعتمدة حديثًا |
| `test_due_alert_created_for_family_past_interval` | إنشاء تنبيه متأخر للأسرة تجاوزت الفترة |
| `test_no_duplicate_alerts_on_repeated_generation` | عدم تكرار التنبيهات عند التشغيل المتكرر |
| `test_alert_resolved_when_family_no_longer_due` | حل التنبيه عند إعادة تقييم الأسرة |
| `test_command_outputs_summary` | الأمر يعرض ملخص النتائج |
| `test_interval_setting_affects_alert_generation` | إعداد الفترة يؤثر على توليد التنبيهات |

### المصانع (Factories)

- `FamilyAssessmentFactory` — بحالات: `approved()`, `approvedMonthsAgo(int)`, `draft()`
- `FamilyFactory` — بحالات: `approved()`, `draft()`, `underReview()`, `needsCompletion()`, `rejected()`

### نتيجة الاختبارات

```
Tests: 32, Assertions: 80, All passed ✅
```

---

## 13. تكامل الشريط الجانبي (Sidebar)

### `resources/views/livewire/shared/sidebar.blade.php`

#### إدخالات مضافة:

1. **"إعادة التقييم"** تحت مجموعة "إدارة الحالات"
   - شارة حمراء نابضة (animate-pulse) تعرض عدد التنبيهات المتأخرة

2. **"التنبيهات"** تحت مجموعة "الاتصالات والمتابعة"
   - شارة حمراء تعرض إجمالي التنبيهات النشطة

#### منطق الشارات:

```php
// لإعادة التقييم: عدد التنبيهات المتأخرة فقط
Alert::active()->forType(Alert::TYPE_REASSESSMENT_OVERDUE)->count()

// لصفحة التنبيهات: إجمالي التنبيهات النشطة
Alert::active()->count()
```

---

## 14. ملخص الملفات المنشأة/المعدلة

### ملفات منشأة جديدة

| الملف | النوع |
|------|------|
| `app/Services/Alerts/ReAssessmentAlertService.php` | Service |
| `app/Console/Commands/GenerateAlerts.php` | Command |
| `app/Livewire/Alerts/AlertsIndex.php` | Livewire Component |
| `app/Livewire/Families/ReAssessmentIndex.php` | Livewire Component |
| `app/Livewire/Families/AssessmentHistory.php` | Livewire Component |
| `app/Livewire/Settings/SettingsIndex.php` | Livewire Component |
| `app/Models/FamilyAssessment.php` | Model |
| `app/Models/Alert.php` | Model |
| `app/Models/SystemSetting.php` | Model |
| `database/factories/FamilyAssessmentFactory.php` | Factory |
| `resources/views/livewire/pages/alerts/index.blade.php` | View |
| `resources/views/livewire/pages/families/re-assessment-index.blade.php` | View |
| `resources/views/livewire/pages/families/assessment-history.blade.php` | View |
| `resources/views/livewire/pages/settings/index.blade.php` | View |
| `tests/Feature/AlertGenerationTest.php` | Test |

### ملفات معدلة

| الملف | التعديل |
|------|---------|
| `app/Models/Family.php` | إضافة `current_assessment_id` لـ `$fillable`، علاقات `assessments()` و `currentAssessment()` |
| `app/Models/FamilyAssessment.php` | إضافة `HasFactory` trait |
| `app/Services/Families/FamilyApprovalService.php` | إضافة `updated_by` في جميع العمليات |
| `app/Livewire/Families/Create.php` | تسجيل `created_by`/`updated_by`، مزامنة المساعدات |
| `resources/views/livewire/pages/families/create.blade.php` | تبويب المساعدات بتصميم 3 أعمدة |
| `resources/views/livewire/pages/families/review-show.blade.php` | اسم الباحث أسفل الملاحظات، تبويب المساعدات |
| `resources/views/livewire/shared/sidebar.blade.php` | إدخال إعادة التقييم والتنبيهات مع الشارات |
| `routes/web.php` | مسارات إعادة التقييم، التاريخ، التنبيهات، الإعدادات |
| `routes/console.php` | جدولة أمر توليد التنبيهات |
| `database/factories/FamilyFactory.php` | (موجود مسبقًا) حالات: approved, draft, underReview... |

---

## ملاحظات تقنية

- **قاعدة البيانات**: SQLite (موجودة مسبقًا)
- **Laravel**: v13 / **PHP**: 8.4 / **Livewire**: v4 / **Volt**: v1
- **Tailwind**: v4 / **Alpine**: v3
- **التنسيق**: `vendor/bin/pint --dirty --format agent` بعد كل تعديل
- **الاختبارات**: `php artisan test --compact` — جميع الاختبارات (32) ناجحة
- **الجدولة**: تعمل عبر `Schedule` في `routes/console.php`
