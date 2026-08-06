# توثيق مشروع: نظام إدارة منشأة خيرية
## ما كان مطلوباً — وإلى أين وصلنا

---

## 1. نظرة عامة على المشروع

نظام إدارة لمنشأة خيرية مبني على **Laravel 13 + Livewire 4 + Volt + TailwindCSS 4 + Alpine.js 3**.
يهدف إلى إدارة: الأسر المستفيدة، البحوث الاجتماعية، طلبات المساعدة، المتبرعين، الصرف، والإدارة.

---

## 2. ما كان مطلوباً (من ملف المتطلبات)

### وفق `aid_request_tabs_prompt_ar.txt` — نموذج طلب المساعدة

#### دورة العمل المطلوبة
1. اختيار الأسرة
2. التحقق من صلاحية آخر بحث اجتماعي
3. إدخال بيانات الطلب
4. إضافة بنود المساعدة
5. رفع المستندات
6. مراجعة الطلب
7. حفظه كمسودة أو إرساله للمراجعة

#### التبويبات الخمسة المطلوبة
| # | التبويب | الحالة |
|---|---------|--------|
| 1 | بيانات الطلب | ✅ منجز |
| 2 | الأسرة والبحث الاجتماعي | ✅ منجز (جزئياً — بدون بيانات البحث التفصيلية) |
| 3 | بنود المساعدة | ✅ منجز |
| 4 | المستندات والمرفقات | ✅ منجز (واجهة فقط، رفع الملفات لاحقاً) |
| 5 | المراجعة والإرسال | ✅ منجز |

#### ما صُرِّح بأنه خارج النطاق الآن
- ❌ قرار لجنة الاعتماد
- ❌ الاعتماد الكلي أو الجزئي للبنود
- ❌ الصرف أو التنفيذ
- ❌ رسائل واتساب للمتبرعين
- ❌ النظام المالي أو المخازن

---

## 3. ما تم تنفيذه فعلياً في هذه الجلسة

### 3.1 البنية الأساسية (قاعدة البيانات والنماذج)

#### Migration ✅
- `aid_requests` — جدول الطلبات الرئيسي
- `aid_request_items` — بنود كل طلب
- `aid_request_attachments` — مرفقات الطلبات
- `aid_request_status_histories` — سجل تغييرات الحالة

#### Models ✅ (مُعادة بناؤها أو مُكملة)
| النموذج | الملاحظة |
|---------|----------|
| [`AidRequest`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Models/AidRequest.php) | إزالة cast خاطئ للـ priority، إضافة SoftDeletes |
| [`AidRequestItem`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Models/AidRequestItem.php) | بناء كامل مع fillable و casts |
| [`AidRequestAttachment`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Models/AidRequestAttachment.php) | بناء كامل |
| [`AidRequestStatusHistory`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Models/AidRequestStatusHistory.php) | إنشاء جديد |
| [`Family`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Models/Family.php) | إصلاح كامل — كان مفتقداً class declaration |

### 3.2 طبقة الخدمات (Services)

| الملف | ما يفعله |
|-------|----------|
| [`AidRequestNumberGenerator`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Services/AidRequests/AidRequestNumberGenerator.php) | يولد أرقام `AR-2026-000001` بـ DB lock لمنع التكرار |
| [`AidRequestService`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Services/AidRequests/AidRequestService.php) | `createOrUpdateDraft()` + `submit()` + `syncItems()` |

### 3.3 Livewire Component

| الملف | التفاصيل |
|-------|---------|
| [`CreateAidRequest.php`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Livewire/AidRequests/CreateAidRequest.php) | Full-page Livewire Component (class-based, ليس Volt) |
| [`AidRequestForm.php`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Livewire/Forms/AidRequestForm.php) | Livewire Form Object مع Validate attributes |
| [`create.blade.php`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/resources/views/livewire/pages/aid-requests/create.blade.php) | Blade نظيف بدون PHP code |

### 3.4 Events

| الملف | الوصف |
|-------|-------|
| [`AidRequestSubmitted`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/app/Events/AidRequestSubmitted.php) | يُطلق عند إرسال الطلب للمراجعة |

### 3.5 Routes

```
GET /aid-requests         → placeholder (قائمة مؤقتة)
GET /aid-requests/create  → CreateAidRequest::class  ✅
GET /aid-requests/{id}    → placeholder (عرض مؤقت)
GET /aid-requests/{id}/edit → CreateAidRequest::class
```

### 3.6 تحسينات الـ Layout والـ UI

| التغيير | الملف | الوصف |
|---------|-------|-------|
| تثبيت القائمة الجانبية | [`app.blade.php`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/resources/views/layouts/app.blade.php) | `h-screen overflow-hidden` — الـ sidebar لا تتحرك مع الـ scroll |
| تثبيت Sidebar | [`sidebar.blade.php`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/resources/views/livewire/shared/sidebar.blade.php) | `lg:static h-full` بدلاً من `lg:relative` |
| Custom Scrollbar | [`app.css`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/resources/css/app.css) | شريط تمرير 4px رفيع بلون الـ accent، يتكيف مع الثيم |
| إصلاح أيقونة | [`families/index.blade.php`](file:///Volumes/Exchange%20HD/dev/laravel13/charity/resources/views/livewire/pages/families/index.blade.php) | `hand-rising` → `hand-raised` |

---

## 4. وظائف نموذج طلب المساعدة — تفصيل

### ✅ التبويب 1: بيانات الطلب
- اختيار الأسرة (Select)
- مصدر الطلب (9 خيارات)
- بيانات مقدم الطلب (اسم، صفة، هاتف)
- نوع الطلب (وقتية / دورية / طارئة)
- درجة الأولوية (4 درجات)
- عنوان مختصر + وصف الاحتياج
- تاريخ التنفيذ المطلوب (اختياري)
- ملاحظات داخلية
- سبب الاستعجال (يظهر فقط عند اختيار "عاجلة جداً")

### ✅ التبويب 2: الأسرة والبحث الاجتماعي
- عرض بيانات الأسرة المختارة
- > **ناقص**: بيانات البحث الاجتماعي التفصيلية (تنتظر ربط وحدة البحوث)

### ✅ التبويب 3: بنود المساعدة
- إضافة/حذف عدد غير محدود من البنود
- لكل بند: تصنيف، اسم، وصف، طريقة التنفيذ، كمية، تكلفة وحدة، أولوية، ملاحظات
- حساب الإجمالي التقديري تلقائياً

### ✅ التبويب 4: المستندات
- إضافة/حذف مرفقات مع نوع المستند (13 نوع)
- > **ناقص**: رفع الملفات الفعلي (يحتاج Livewire file upload integration)

### ✅ التبويب 5: المراجعة والإرسال
- ملخص شامل للطلب والبنود
- جدول البنود مع الإجماليات
- قائمة المرفقات
- Checkbox الإقرار (إلزامي قبل الإرسال)
- Confirm Modal قبل الإرسال النهائي
- زرا "حفظ مسودة" و"إرسال للمراجعة"

---

## 5. السلوك البرمجي المنجز

| الوظيفة | الحالة | الملاحظة |
|---------|--------|----------|
| حفظ كمسودة من أي تبويب | ✅ | يحفظ البيانات المتاحة دون اشتراط اكتمال النموذج |
| توليد رقم الطلب | ✅ | `AR-2026-000001` عند أول حفظ فقط |
| تثبيت الـ ID بعد الحفظ الأول | ✅ | التحديثات اللاحقة تُعدِّل نفس السجل |
| التحقق قبل التقدم للتبويب التالي | ✅ | (الحقول الإلزامية في التبويب الأول) |
| Stepper بصري | ✅ | 5 خطوات مع مؤشر الخطوة الحالية |
| Confirm Modal قبل الإرسال | ✅ | رسالة تحذيرية واضحة |
| حفظ البنود في قاعدة البيانات | ✅ | `syncItems()` في الـ service |
| سجل تغييرات الحالة | ✅ | `AidRequestStatusHistory` عند الإرسال |
| إطلاق Event عند الإرسال | ✅ | `AidRequestSubmitted` جاهز للـ Listeners |

---

## 6. ما لم يُنجز بعد (المراحل القادمة)

### أولوية عالية
- [ ] **صفحة قائمة الطلبات** `GET /aid-requests` — عرض وبحث وفلترة
- [ ] **صفحة عرض الطلب** `GET /aid-requests/{id}` — قراءة فقط
- [ ] **رفع الملفات الفعلي** في تبويب المستندات (Livewire file upload)
- [ ] **ربط البحث الاجتماعي** بتبويب الأسرة (Research Validity Indicator)

### أولوية متوسطة
- [ ] **صفحة تعديل المسودة** `GET /aid-requests/{id}/edit`
- [ ] **Authorization Policy** — صلاحيات على مستوى الفرع والمنطقة
- [ ] **Searchable Select** للأسرة (بحث بالاسم / الكود / الهاتف)
- [ ] **تحذير المغادرة** عند وجود بيانات غير محفوظة (Confirm Modal)

### أولوية منخفضة (مراحل لاحقة)
- [ ] قرار لجنة الاعتماد
- [ ] الاعتماد الجزئي للبنود
- [ ] إشعارات داخلية لمسؤول المراجعة
- [ ] Auto-Save (حفظ تلقائي)

---

## 7. هيكل الملفات الحالي

```
app/
├── Enums/
│   ├── AidRequestPriority.php       ← low/medium/high/critical (إنجليزي)
│   └── AidRequestStatus.php         ← draft/submitted/under_review/...
├── Events/
│   └── AidRequestSubmitted.php      ✅ جديد
├── Livewire/
│   ├── AidRequests/
│   │   └── CreateAidRequest.php     ✅ مُعاد بناؤه كاملاً
│   └── Forms/
│       └── AidRequestForm.php       ✅ مُعاد بناؤه (Livewire\Form)
├── Models/
│   ├── AidRequest.php               ✅ مُصلح
│   ├── AidRequestItem.php           ✅ مُكمَّل
│   ├── AidRequestAttachment.php     ✅ مُكمَّل
│   ├── AidRequestStatusHistory.php  ✅ جديد
│   └── Family.php                   ✅ مُصلح
└── Services/
    └── AidRequests/
        ├── AidRequestNumberGenerator.php  ✅ جديد
        └── AidRequestService.php          ✅ مُعاد بناؤه

resources/views/
├── layouts/
│   └── app.blade.php                ✅ h-screen + fixed sidebar
├── livewire/
│   ├── pages/aid-requests/
│   │   └── create.blade.php         ✅ مُعاد بناؤه (Blade نظيف)
│   └── shared/
│       └── sidebar.blade.php        ✅ تثبيت القائمة

resources/css/
└── app.css                          ✅ Custom Scrollbar (4px, accent-colored)
```

---

## 8. ملاحظات تقنية مهمة

> [!IMPORTANT]
> **الـ Priority Enum مكسور**: `AidRequestPriority` يحتوي على قيم إنجليزية (`low/medium/high/critical`) لكن قاعدة البيانات تخزّن قيماً عربية (`عادية/متوسطة/مرتفعة/عاجلة جداً`). يجب توحيد هذا لاحقاً.

> [!NOTE]
> **رفع الملفات**: تبويب المستندات يعرض الواجهة فقط دون رفع فعلي. يحتاج تكامل `Livewire\WithFileUploads` مع تخزين private.

> [!TIP]
> **الـ Route للـ show**: حالياً placeholder. عند بناء صفحة العرض، تحويل الـ route لـ `ShowAidRequest::class`.
