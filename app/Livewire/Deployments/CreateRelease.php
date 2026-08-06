<?php

namespace App\Livewire\Deployments;

use App\Services\Deployment\ReleaseService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'إصدار جديد'])]
class CreateRelease extends Component
{
    public string $version = '';

    public string $title = '';

    public string $description = '';

    public array $changes = [];

    public bool $importing = false;

    /** رسالة توضيحية دائمة تظهر أسفل زر الاستيراد (لا تختفي كالتوست). */
    public ?string $importNotice = null;

    /** نوع الرسالة: info | warning | success. */
    public string $importNoticeType = 'info';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        // الجدول للعرض فقط — يمتلئ بزر الاستيراد ولا يمكن تعديله يدويًا.
        $this->changes = [];
    }

    /**
     * Detect files changed since the last release and fill the form.
     */
    public function importChanges(): void
    {
        $this->importing = true;
        $this->importNotice = null;

        try {
            $service = app(ReleaseService::class);
            $detected = $service->detectChanges();

            if ($detected === []) {
                $this->importNotice = 'لم يتم العثور على ملفات معدّلة. إذا كان هذا أول إصدار، احفظه أولًا ليصبح نقطة الأساس، ثم ستُكتشف التغييرات تلقائيًا في الإصدارات التالية.';
                $this->importNoticeType = 'info';
                $this->dispatch('notify', message: $this->importNotice, type: 'info');

                return;
            }

            // دمج النتائج مع الصفوف الموجودة: أي مسار مكرر يُستبدل بنسخة الكشف
            // (الأحدث من القرص) بدلًا من تكراره، والصفوف اليدوية غير المكررة تبقى.
            $merged = [];

            foreach ($this->changes as $change) {
                if ($change['file_path'] !== '') {
                    $merged[$change['file_path']] = $change;
                }
            }

            foreach ($detected as $change) {
                $merged[$change['file_path']] = [
                    'type' => $change['type'],
                    'file_path' => $change['file_path'],
                    'description' => $change['type'] === 'removed'
                        ? 'حذف الملف'
                        : ($change['type'] === 'added' ? 'ملف جديد' : 'تعديل الملف'),
                ];
            }

            $this->changes = array_values($merged);

            if (! $service->hasBaselineSnapshot()) {
                $this->importNotice = 'لا يوجد إصدار سابق للمقارنة — تم اكتشاف الملفات المعدّلة مؤخرًا. بعد حفظ هذا الإصدار سيصبح نقطة الأساس، ويصبح الكشف دقيقًا (إضافة/تعديل/حذف) في الإصدارات التالية.';
                $this->importNoticeType = 'warning';
            }

            $this->dispatch(
                'notify',
                message: 'تم استيراد '.count($this->changes).' تغيير تلقائيًا.',
                type: 'success'
            );
        } catch (\Throwable $e) {
            $this->importNotice = 'حدث خطأ أثناء الكشف: '.$e->getMessage();
            $this->importNoticeType = 'error';
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        } finally {
            $this->importing = false;
        }
    }

    public function removeChange(int $index): void
    {
        unset($this->changes[$index]);
        $this->changes = array_values($this->changes);
    }

    public function save(): void
    {
        $this->validate();

        try {
            app(ReleaseService::class)->create(
                [
                    'version' => $this->version,
                    'title' => $this->title,
                    'description' => $this->description ?: null,
                ],
                $this->changes
            );

            $this->dispatch('notify', message: 'تم إنشاء الإصدار بنجاح.', type: 'success');
            $this->redirect(route('deployments.index'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    protected function rules(): array
    {
        return [
            'version' => 'required|string|max:20|unique:releases,version',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'changes' => 'required|array|min:1',
            'changes.*.type' => 'required|string|in:added,modified,fixed,updated,removed',
            'changes.*.file_path' => 'required|string|max:500',
            'changes.*.description' => 'required|string',
        ];
    }

    protected function messages(): array
    {
        return [
            'version.required' => 'رقم الإصدار مطلوب.',
            'version.unique' => 'رقم الإصدار موجود مسبقاً.',
            'title.required' => 'عنوان الإصدار مطلوب.',
            'changes.required' => 'يجب إضافة تغيير واحد على الأقل.',
            'changes.min' => 'يجب إضافة تغيير واحد على الأقل.',
            'changes.*.type.required' => 'نوع التغيير مطلوب.',
            'changes.*.file_path.required' => 'مسار الملف مطلوب.',
            'changes.*.description.required' => 'وصف التغيير مطلوب.',
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.create');
    }
}
