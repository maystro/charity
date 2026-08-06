# خطة تنفيذ نموذج طلب المساعدة بنظام التبويبات (Aid Request Tabs Wizard)

خطة عمل متكاملة لتنفيذ نموذج طلب المساعدة الجديد المقسم إلى تبويبات وتوصيله بنظام قاعدة البيانات والتحقق من صلاحية البحث الاجتماعي.

---

## 1. بنية قاعدة البيانات (Migrations & Models)

سنقوم بإنشاء الجداول التالية مع علاقاتها الكاملة ومؤشراتها (Indexes) ومفاتيحها الخارجية (Foreign Keys):

### أ. الأسر والبحث الاجتماعي (مسبقة المتطلبات)

#### 1. جدول الأسر `families`
- `id` (Primary Key)
- `name` (رب الأسرة)
- `case_type` (نوع الحالة: يتيم، أرملة...)
- `national_id` (رقم الهوية)
- `phone` (رقم الجوال)
- `location` (الموقع/الحي)
- `members` (عدد الأفراد)
- `priority` (الأولوية: عالية، متوسطة، منخفضة)
- `status` (الحالة: نشط، قيد الدراسة، مكتمل)
- `last_visit` (تاريخ آخر زيارة)
- `branch_id` (المعرف الخارجي للفرع)
- `representative_id` (المعرف الخارجي للمندوب)
- `notes` (ملاحظات)
- `timestamps`

#### 2. جدول البحوث الاجتماعية `social_researches`
- `id` (Primary Key)
- `family_id` (Foreign Key -> families)
- `research_number` (رقم البحث فريد)
- `research_type` (نوع البحث: ميداني، مكتبي)
- `conducted_at` (تاريخ الإجراء)
- `approved_at` (تاريخ الاعتماد)
- `expiry_date` (تاريخ الانتهاء)
- `eligibility_degree` (درجة الاستحقاق)
- `average_income` (متوسط الدخل - Decimal)
- `net_income` (صافي الدخل - Decimal)
- `recommendation` (توصية الباحث)
- `committee_decision` (قرار اللجنة)
- `status` (الحالة: معتمد، مسودة)
- `timestamps`

---

### ب. جداول طلبات المساعدة

#### 3. جدول طلبات المساعدة `aid_requests`
- `id` (Primary Key)
- `request_number` (رقم فريد آمن للتكرار: `AR-YYYY-XXXXXX`)
- `family_id` (Foreign Key -> families)
- `branch_id` (Foreign Key -> branches / nullable)
- `area_id` (nullable)
- `representative_id` (nullable)
- `created_by` (Foreign Key -> users)
- `submitted_by` (Foreign Key -> users / nullable)
- `source_type` (مصدر الطلب: الأسرة، مندوب...)
- `applicant_name` (اسم مقدم الطلب)
- `applicant_relation` (الصلة بالأسرة)
- `applicant_phone` (هاتف مقدم الطلب)
- `request_type` (نوع الطلب: وقتية، دورية، طارئة)
- `priority` (عادية، متوسطة، مرتفعة، عاجلة جداً)
- `title` (عنوان مختصر للطلب)
- `description` (وصف الاحتياج)
- `requested_at` (تاريخ التقديم)
- `needed_by` (التاريخ المطلوب للتنفيذ - nullable)
- `campaign_id` (البرنامج أو الحملة - nullable)
- `status` (الحالة: draft, submitted, under_review, needs_completion, approved...)
- `internal_notes` (nullable)
- `exception_reason` (سبب استثناء البحث المنتهي - nullable)
- `duplicate_reason` (سبب تكرار الطلب المشابه - nullable)
- `total_estimated_amount` (إجمالي القيمة التقديرية - Decimal)
- `submitted_at` (nullable)
- `timestamps`
- `softDeletes`

#### 4. بنود طلب المساعدة `aid_request_items`
- `id` (Primary Key)
- `aid_request_id` (Foreign Key -> aid_requests)
- `category_id` (التصنيف الرئيسي للمساعدة)
- `subcategory_id` (التصنيف الفرعي للمساعدة - nullable)
- `title` (اسم البند)
- `description` (الوصف التفصيلي)
- `execution_type` (طريقة التنفيذ: نقدي، عيني...)
- `quantity` (الكمية - Decimal)
- `unit_id` (وحدة القياس - nullable)
- `unit_cost` (تكلفة الوحدة - Decimal)
- `estimated_total` (إجمالي القيمة التقديرية - Decimal)
- `recurrence_type` (وقتية أم دورية)
- `frequency` (دورية التكرار: أسبوعي، شهري...)
- `recurrence_start` (تاريخ البداية)
- `recurrence_end` (تاريخ النهاية)
- `installments_count` (عدد الدفعات المتوقع)
- `preferred_due_day` (يوم الاستحقاق المفضل)
- `stop_when_research_expires` (إيقاف عند انتهاء البحث - Boolean)
- `reminder_enabled` (تفعيل التنبيه - Boolean)
- `reminder_days` (عدد أيام التنبيه)
- `priority` (أولوية البند: عادي، مهم، عاجل، حرج)
- `payee_type` (الجهة المتوقع الدفع إليها: الأسرة، مستشفى...)
- `payee_name` (اسم الجهة)
- `payee_phone` (هاتف الجهة)
- `notes` (ملاحظات)
- `sort_order` (الترتيب)
- `timestamps`

#### 5. مرفقات طلب المساعدة `aid_request_attachments`
- `id` (Primary Key)
- `aid_request_id` (Foreign Key -> aid_requests)
- `aid_request_item_id` ( Foreign Key -> aid_request_items / nullable)
- `attachment_type_id` (نوع المرفق)
- `original_name` (الاسم الأصلي للملف)
- `stored_name` (الاسم المخزن للسرية)
- `path` (المسار داخل التخزين الخاص)
- `disk` (نوع القرص: local, s3...)
- `mime_type` (نوع الملف)
- `size` (الحجم بالبايت)
- `document_date` (تاريخ المستند - nullable)
- `expires_at` (تاريخ الانتهاء - nullable)
- `verification_status` (حالة التحقق: غير مراجع، صحيح، مرفوض...)
- `notes` (ملاحظات)
- `uploaded_by` (Foreign Key -> users)
- `timestamps`

#### 6. سجل التتبع لطلبات المساعدة `aid_request_status_histories`
- `id` (Primary Key)
- `aid_request_id` (Foreign Key -> aid_requests)
- `from_status` (الحالة السابقة - nullable)
- `to_status` (الحالة الجديدة)
- `changed_by` (Foreign Key -> users)
- `notes` (ملاحظات)
- `created_at`

---

## 2. النماذج والأكواد المساعدة (Services & Enums)

- **`AidRequestStatus`**: Enum لحالات الطلب (`draft`, `submitted`, `under_review`, `needs_completion`, `approved`, `rejected` ...).
- **`AidRequestPriority`**: Enum للأولويات (`low`, `medium`, `high`, `critical`).
- **`AidRequestNumberGenerator`**: خدمة لتوليد أرقام فريدة متسلسلة للطلبات وتخزينها بأمان لمنع التكرار بالتوازي (Concurrency-safe).
- **`ResearchValidityService`**: لتقييم حالة البحث الاجتماعي وتحديد ما إذا كان سارياً، منتهياً، أو شارف على الانتهاء.
- **`AidRequestService`**: لمعالجة وتعديل وتقديم طلب المساعدة باستخدام معاملات قاعدة البيانات (Database Transactions).

---

## 3. شاشات الواجهة الأمامية (Livewire Volt Components)

### أ. قائمة طلبات المساعدة (`/aid-requests`):
- جدول تفاعلي يعرض الطلبات السابقة مع إمكانية البحث والفلترة حسب الحالة، النوع، الأولوية، والفرع.
- زر للذهاب إلى صفحة إنشاء طلب جديد.

### ب. شاشة إنشاء طلب مساعدة بنظام التبويبات (`/aid-requests/create`):
- مكون يعتمد على نظام التبويبات الخمسة:
  1. **بيانات الطلب**: نموذج إدخال التفاصيل الأساسية واختيار الأسرة.
  2. **الأسرة والبحث**: قراءة مؤشرات صلاحية البحث الاجتماعي والمساعدات السابقة.
  3. **بنود المساعدة**: قائمة بالبنود والكميات وحساب الإجماليات بشكل حي ومستمر.
  4. **المستندات**: رفع الملفات والتخزين الخاص (Private Storage) مع التحقق من الصيغ.
  5. **المراجعة والإرسال**: فحص التنبيهات والأخطاء والإقرار بالإرسال.

### ج. شاشة عرض الطلب (`/aid-requests/{aidRequest}`):
- شاشة للقراءة فقط (Read Only) تستخدم نفس أسلوب عرض المراجعة مع سجل التتبع التاريخي لتغيير الحالات.

---

## 4. خطة التحقق والتوثيق (Verification & Docs)

- **الاختبارات التلقائية**: إنشاء اختبارات برمجية (`Feature Tests` و `Livewire Tests`) لتغطية كافة الصلاحيات، وحالات انتهاء البحث الاجتماعي، والتحقق المالي للبنود، ورفع الملفات الآمنة، والتحويل إلى الحالة `submitted`.
- **التوثيق**: كتابة دليل المطور النهائي في `AID_REQUEST_FORM.md` وتحديث ملفات `PROGRESS.md` و `TASKS.md`.


سنقوم بتعديل إضافة طلب مساعدة : 
١ - صندوق اختيار الاسرة المعتمدة في الاصل
٢ - كارت اضافة نوع الخدمة : زر اضافة خدمة ومنها يختار نوع الخدمة كما في المرفقات ثم يختار نوع الطلب ثم درجة الأولوية ثم صندوق عنوان مختصر للطلب و صندوق ادخال وصف الاحتياج
٣ - عند الحفظ يتم حفظ المطلب ( تحت المراجعة)