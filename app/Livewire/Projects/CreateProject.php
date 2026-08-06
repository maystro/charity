<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'إضافة مشروع جديد'])]
class CreateProject extends Component
{
    public string $name = '';

    public ?string $description = null;

    public ?string $governorate = null;

    public string $status = 'planning';

    public ?string $start_date = null;

    public ?string $end_date = null;

    /** @var array<int, array<string, mixed>> */
    public array $phases = [];

    public function mount(): void
    {
        // ابدأ بمرحلة افتراضية واحدة فارغة.
        $this->phases = [
            ['name' => '', 'description' => '', 'cost' => ''],
        ];
    }

    #[Computed]
    public function governorates(): array
    {
        return array_values(config('governorates.egypt'));
    }

    #[Computed]
    public function statusOptions(): array
    {
        return ProjectStatus::options();
    }

    /**
     * إجمالي تكلفة المراحل المدخلة — يحسب لحظياً.
     */
    #[Computed]
    public function totalAmount(): float
    {
        return collect($this->phases)
            ->map(fn ($p) => (float) ($p['cost'] ?? 0))
            ->sum();
    }

    public function addPhase(): void
    {
        $this->phases[] = ['name' => '', 'description' => '', 'cost' => ''];
    }

    public function removePhase(int $index): void
    {
        if (count($this->phases) <= 1) {
            $this->dispatch('notify', message: 'يجب وجود مرحلة واحدة على الأقل', type: 'warning');

            return;
        }

        unset($this->phases[$index]);
        $this->phases = array_values($this->phases);
    }

    public function movePhaseUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        $temp = $this->phases[$index - 1];
        $this->phases[$index - 1] = $this->phases[$index];
        $this->phases[$index] = $temp;
    }

    public function movePhaseDown(int $index): void
    {
        if ($index >= count($this->phases) - 1) {
            return;
        }

        $temp = $this->phases[$index + 1];
        $this->phases[$index + 1] = $this->phases[$index];
        $this->phases[$index] = $temp;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'governorate' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:'.implode(',', array_column(ProjectStatus::cases(), 'value'))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'phases' => ['required', 'array', 'min:1'],
            'phases.*.name' => ['required', 'string', 'max:255'],
            'phases.*.description' => ['nullable', 'string'],
            'phases.*.cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'اسم المشروع مطلوب',
            'phases.*.name.required' => 'اسم المرحلة مطلوب',
            'phases.*.cost.required' => 'تكلفة المرحلة مطلوبة',
            'phases.*.cost.numeric' => 'تكلفة المرحلة يجب أن تكون رقماً',
            'phases.*.cost.min' => 'تكلفة المرحلة لا يمكن أن تكون سالبة',
            'end_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        /** @var Project $project */
        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'governorate' => $data['governorate'],
            'status' => $data['status'],
            'total_budget' => $this->totalAmount,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'created_by' => auth()->id(),
        ]);

        foreach ($data['phases'] as $i => $phase) {
            ProjectPhase::create([
                'project_id' => $project->id,
                'name' => $phase['name'],
                'description' => $phase['description'],
                'cost' => $phase['cost'],
                'sort_order' => $i,
            ]);
        }

        $this->dispatch('notify', message: 'تم إنشاء المشروع بنجاح', type: 'success');

        $this->redirect(route('projects.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.pages.projects.create', [
            'governorates' => $this->governorates,
            'statusOptions' => $this->statusOptions,
            'totalAmount' => $this->totalAmount,
        ]);
    }
}
