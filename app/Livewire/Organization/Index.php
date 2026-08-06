<?php

namespace App\Livewire\Organization;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['title' => 'بيانات المؤسسة'])]
class Index extends Component
{
    use WithFileUploads;

    public string $organizationName = '';

    public string $organizationTagline = '';

    public $logo = null;

    public ?string $currentLogoPath = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->organizationName = (string) SystemSetting::get('organization_name', 'منشأة خيرية');
        $this->organizationTagline = (string) SystemSetting::get('organization_tagline', 'الاسم التعريفي');
        $this->currentLogoPath = SystemSetting::get('organization_logo_path');
    }

    protected function rules(): array
    {
        return [
            'organizationName' => ['required', 'string', 'max:255'],
            'organizationTagline' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'organizationName.required' => 'اسم المؤسسة مطلوب',
            'organizationTagline.required' => 'الاسم التعريفي مطلوب',
            'logo.image' => 'الشعار يجب أن يكون صورة',
            'logo.max' => 'حجم الشعار يجب ألا يتجاوز 2MB',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->logo) {
            $oldPath = SystemSetting::get('organization_logo_path');

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $data['logo'] = $this->logo->storePublicly('organization', 'public');
        } else {
            $data['logo'] = $this->currentLogoPath;
        }

        SystemSetting::set('organization_name', $data['organizationName'], 'organization', 'اسم المؤسسة', 'string', 'اسم المؤسسة الظاهر في النظام');
        SystemSetting::set('organization_tagline', $data['organizationTagline'], 'organization', 'الاسم التعريفي', 'string', 'الاسم التعريفي الظاهر في رأس القائمة الجانبية');
        SystemSetting::set('organization_logo_path', $data['logo'] ?? '', 'organization', 'شعار المؤسسة', 'string', 'مسار شعار المؤسسة');

        $this->currentLogoPath = $data['logo'] ?: null;
        $this->logo = null;

        $this->dispatch('organization-updated');
        $this->dispatch('notify', message: 'تم حفظ بيانات المؤسسة بنجاح.', type: 'success');

        $this->redirect(route('organization.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.organization.index', [
            'currentLogoUrl' => $this->currentLogoPath && Storage::disk('public')->exists($this->currentLogoPath)
                ? Storage::disk('public')->url($this->currentLogoPath)
                : null,
        ]);
    }
}
