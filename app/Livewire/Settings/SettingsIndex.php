<?php

namespace App\Livewire\Settings;

use App\Models\SystemSetting;
use App\Services\DemoData\DemoDataService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'إعدادات النظام'])]
class SettingsIndex extends Component
{
    // Families settings
    public int $reassessmentIntervalMonths = 3;

    public function mount(): void
    {
        $this->reassessmentIntervalMonths = (int) SystemSetting::get('reassessment_interval_months', 3);
    }

    public function save(): void
    {
        $this->validate([
            'reassessmentIntervalMonths' => 'required|integer|min:1|max:24',
        ]);

        SystemSetting::set(
            'reassessment_interval_months',
            $this->reassessmentIntervalMonths,
            'families',
            'فترة إعادة التقييم (شهور)',
            'integer',
            'عدد الشهور بعد которых يجب إعادة تقييم الأسرة'
        );

        $this->dispatch('notify', message: 'تم حفظ الإعدادات بنجاح.', type: 'success');
    }

    public function seedDemoData(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        app(DemoDataService::class)->seed();

        $this->dispatch('notify', message: 'تم إنشاء نموذج البيانات التجريبية بنجاح.', type: 'success');
    }

    public function deleteDemoData(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        app(DemoDataService::class)->purge();

        $this->dispatch('notify', message: 'تم حذف نموذج البيانات التجريبية.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.pages.settings.index');
    }
}
