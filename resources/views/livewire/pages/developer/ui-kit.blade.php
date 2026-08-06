<?php

use Livewire\Volt\Component;

new
#[Layout('layouts.app', ['title' => 'مكتبة المكونات'])]
class extends Component
{
    public bool $showModal = false;
    public bool $showConfirm = false;
}

?>

<div class="space-y-10">
    <div>
        <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">مكتبة المكونات</h1>
        <p class="text-sm text-[var(--color-text-muted)] mt-1">دليل المكونات البصرية للنظام</p>
    </div>

    {{-- Buttons --}}
    <section id="buttons">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">الأزرار</h2>
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">أساسي</x-ui.button>
                <x-ui.button variant="secondary">ثانوي</x-ui.button>
                <x-ui.button variant="outline">مخطط</x-ui.button>
                <x-ui.button variant="ghost">شفاف</x-ui.button>
                <x-ui.button variant="danger">خطر</x-ui.button>
                <x-ui.button variant="success">نجاح</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button size="sm">صغير</x-ui.button>
                <x-ui.button size="md">متوسط</x-ui.button>
                <x-ui.button size="lg">كبير</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button loading>جارٍ التحميل</x-ui.button>
                <x-ui.button icon="heart">مع أيقونة</x-ui.button>
                <x-ui.button disabled>معطل</x-ui.button>
            </div>
        </div>
    </section>

    {{-- Form Controls --}}
    <section id="forms">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">النماذج</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.input label="حقل نصي" name="demo_text" placeholder="نص تجريبي..." />
            <x-ui.input label="مع أيقونة" name="demo_icon" icon="magnifying-glass" placeholder="بحث..." />
            <x-ui.input label="مع خطأ" name="demo_error" error="هذا الحقل مطلوب" />
            <x-ui.input label="مع بادئة" name="demo_prefix" prefix="+" placeholder="رقم الجوال" />
            <x-ui.input label="مع لاحقة" name="demo_suffix" suffix="ريال" value="١٬٠٠٠" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <x-ui.select label="قائمة منسدلة" name="demo_select" :options="['option-1' => 'خيار ١', 'option-2' => 'خيار ٢', 'option-3' => 'خيار ٣']" />
            <x-ui.textarea label="منطقة نصية" name="demo_textarea" placeholder="نص طويل..." rows="3" />
        </div>
        <div class="flex flex-wrap items-center gap-6 mt-4">
            <x-ui.checkbox label="خيار التحديد" name="demo_checkbox" />
            <x-ui.radio label="خيار ١" name="demo_radio" value="1" />
            <x-ui.radio label="خيار ٢" name="demo_radio" value="2" />
            <x-ui.switch label="تشغيل/إيقاف" name="demo_switch" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <x-ui.date-input label="تاريخ" name="demo_date" />
            <x-ui.file-upload label="رفع ملف" name="demo_file" />
        </div>
    </section>

    {{-- Data Display --}}
    <section id="data">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">عرض البيانات</h2>
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <x-ui.badge variant="primary">أساسي</x-ui.badge>
            <x-ui.badge variant="success">نجاح</x-ui.badge>
            <x-ui.badge variant="danger" dot>خطر</x-ui.badge>
            <x-ui.badge variant="warning">تحذير</x-ui.badge>
            <x-ui.badge variant="info">معلومات</x-ui.badge>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <x-ui.card title="بطاقة عادية">محتوى البطاقة</x-ui.card>
            <x-ui.card variant="glass">بطاقة زجاجية</x-ui.card>
            <x-ui.card variant="interactive">بطاقة تفاعلية</x-ui.card>
        </div>
        <div class="flex items-center gap-4 mb-4">
            <x-ui.avatar name="أحمد السعيد" />
            <x-ui.avatar name="نورة الدوسري" size="lg" status="online" />
            <x-ui.avatar name="خالد الحربي" size="xl" status="busy" />
        </div>
        <div class="space-y-2 mb-4">
            <x-ui.skeleton type="title" />
            <x-ui.skeleton type="text" />
            <x-ui.skeleton type="text" width="75%" />
            <x-ui.skeleton type="card" />
        </div>
        <x-ui.empty-state icon="archive" title="لا توجد بيانات" description="لم يتم العثور على أي بيانات تطابق معايير البحث" actionLabel="إضافة جديد" actionHref="#" />
    </section>

    {{-- Feedback --}}
    <section id="feedback">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">التغذية الراجعة</h2>
        <div class="space-y-3">
            <x-ui.alert variant="info">هذه رسالة معلوماتية.</x-ui.alert>
            <x-ui.alert variant="success" title="تم بنجاح">تم حفظ البيانات بنجاح.</x-ui.alert>
            <x-ui.alert variant="warning">يرجى الانتباه لهذا التنبيه المهم.</x-ui.alert>
            <x-ui.alert variant="danger" title="خطأ">حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.</x-ui.alert>
        </div>
    </section>

    {{-- Modal Trigger --}}
    <section id="modals">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">النوافذ المنبثقة</h2>
        <div class="flex gap-3">
            <x-ui.button variant="primary" x-on:click="$dispatch('open-modal', 'demo-modal')">فتح نافذة</x-ui.button>
            <x-ui.button variant="danger" x-on:click="$dispatch('open-modal', 'demo-confirm')">فتح تأكيد</x-ui.button>
        </div>

        <x-ui.modal name="demo-modal" title="نافذة منبثقة" subtitle="مثال على نافذة منبثقة تحتوي على محتوى.">
            <p class="text-sm text-[var(--color-text-secondary)]">هذا هو محتوى النافذة المنبثقة. يمكنك وضع أي محتوى هنا.</p>
            <div class="flex justify-end mt-4">
                <x-ui.button x-on:click="$dispatch('close-modal', 'demo-modal')">إغلاق</x-ui.button>
            </div>
        </x-ui.modal>

        <x-ui.confirm-modal name="demo-confirm" message="هل أنت متأكد من حذف هذا العنصر؟" action="$wire.showConfirm = true" />
    </section>

    {{-- Tabs --}}
    <section id="tabs">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">التبويبات</h2>
        <x-ui.tabs :tabs="['tab-1' => 'التبويب الأول', 'tab-2' => 'التبويب الثاني', 'tab-3' => 'التبويب الثالث']">
            <x-ui.tab-panel name="tab-1">
                <p class="text-sm text-[var(--color-text-secondary)]">محتوى التبويب الأول</p>
            </x-ui.tab-panel>
            <x-ui.tab-panel name="tab-2">
                <p class="text-sm text-[var(--color-text-secondary)]">محتوى التبويب الثاني</p>
            </x-ui.tab-panel>
            <x-ui.tab-panel name="tab-3">
                <p class="text-sm text-[var(--color-text-secondary)]">محتوى التبويب الثالث</p>
            </x-ui.tab-panel>
        </x-ui.tabs>
    </section>

    {{-- Breadcrumb --}}
    <section id="breadcrumb">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">مسار التنقل</h2>
        <x-ui.breadcrumb :items="[
            ['label' => 'الرئيسية', 'href' => '#'],
            ['label' => 'الأسر', 'href' => '#'],
            ['label' => 'تفاصيل الأسرة'],
        ]" />
    </section>

    {{-- Stepper --}}
    <section id="stepper">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">الخطوات</h2>
        <x-ui.stepper :steps="[
            ['label' => 'الخطوة الأولى', 'description' => 'وصف الخطوة'],
            ['label' => 'الخطوة الثانية', 'description' => 'وصف الخطوة'],
            ['label' => 'الخطوة الثالثة', 'description' => 'وصف الخطوة'],
            ['label' => 'الخطوة الرابعة', 'description' => 'وصف الخطوة'],
        ]" :current="2" />
    </section>

    {{-- Accordion --}}
    <section id="accordion">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">الأكورديون</h2>
        <div class="space-y-2">
            <x-ui.accordion title="القسم الأول" icon="home" open>
                <p class="text-sm text-[var(--color-text-secondary)]">محتوى القسم الأول</p>
            </x-ui.accordion>
            <x-ui.accordion title="القسم الثاني" icon="cog">
                <p class="text-sm text-[var(--color-text-secondary)]">محتوى القسم الثاني</p>
            </x-ui.accordion>
            <x-ui.accordion title="القسم الثالث" icon="user-group">
                <p class="text-sm text-[var(--color-text-secondary)]">محتوى القسم الثالث</p>
            </x-ui.accordion>
        </div>
    </section>

    {{-- Tooltip / Dropdown --}}
    <section id="interactions">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">التفاعل</h2>
        <div class="flex items-center gap-4">
            <x-ui.tooltip content="هذا تلميح نصي">
                <x-ui.button variant="ghost">مرر هنا</x-ui.button>
            </x-ui.tooltip>

            <x-ui.dropdown>
                <x-slot:trigger>
                    <x-ui.button variant="outline">قائمة منسدلة</x-ui.button>
                </x-slot:trigger>
                <x-slot:content>
                    <a href="#" class="block px-4 py-2 text-sm text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">خيار ١</a>
                    <a href="#" class="block px-4 py-2 text-sm text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">خيار ٢</a>
                    <a href="#" class="block px-4 py-2 text-sm text-[var(--color-text-primary)] hover:bg-[var(--color-bg-secondary)] transition-colors">خيار ٣</a>
                    <hr class="my-1 border-[var(--color-border)]" />
                    <a href="#" class="block px-4 py-2 text-sm text-[var(--color-danger-500)] hover:bg-[var(--color-danger-50)] transition-colors">حذف</a>
                </x-slot:content>
            </x-ui.dropdown>
        </div>
    </section>

    {{-- Table --}}
    <section id="tables">
        <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4 border-b border-[var(--color-border)] pb-2">الجداول</h2>
        <x-ui.table :headers="['الاسم', 'البريد الإلكتروني', 'الدور', 'الحالة']" :rows="[
            ['أحمد السعيد', 'ahmed@example.com', 'مدير', '<x-ui.badge variant=\"success\" size=\"sm\">نشط</x-ui.badge>'],
            ['سارة العتيبي', 'sara@example.com', 'باحث', '<x-ui.badge variant=\"warning\" size=\"sm\">معلق</x-ui.badge>'],
            ['خالد الحربي', 'khalid@example.com', 'مشرف', '<x-ui.badge variant=\"success\" size=\"sm\">نشط</x-ui.badge>'],
        ]" />
        <x-ui.pagination />
    </section>
</div>
