<?php

namespace App\Livewire\Donations;

use App\Enums\DonationMethod;
use App\Enums\DonationType;
use App\Enums\DonorType;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'إضافة تبرع جديد'])]
class Create extends Component
{
    public ?int $donor_id = null;

    public string $donor_type = DonorType::Individual->value;

    public ?int $project_id = null;

    public string $amount = '';

    public string $method = DonationMethod::Cash->value;

    public string $type = DonationType::Cash->value;

    public string $donated_at = '';

    public ?string $notes = null;

    // New donor modal
    public bool $showNewDonorModal = false;

    public string $newDonorName = '';

    public string $newDonorType = DonorType::Individual->value;

    public string $newDonorCity = '';

    public function mount(): void
    {
        $this->donated_at = now()->format('Y-m-d');
    }

    #[Computed]
    public function donorOptions(): array
    {
        return Donor::query()
            ->select('id', 'name', 'type')
            ->orderBy('name')
            ->get()
            ->map(fn (Donor $donor): array => [
                'value' => $donor->id,
                'label' => $donor->name,
                'meta' => $donor->type->label(),
            ])
            ->values()
            ->all();
    }

    #[Computed]
    public function projectOptions(): array
    {
        return Project::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    #[Computed]
    public function donorTypeOptions(): array
    {
        return DonorType::options();
    }

    /** @return array<int, string> */
    public function governorateOptions(): array
    {
        return config('governorates.egypt', []);
    }

    #[Computed]
    public function methodOptions(): array
    {
        return DonationMethod::options();
    }

    #[Computed]
    public function typeOptions(): array
    {
        return DonationType::options();
    }

    public function updatedDonorId(): void
    {
        $this->syncSelectedDonor();
    }

    protected function syncSelectedDonor(): void
    {
        if (! $this->donor_id) {
            return;
        }

        $donor = Donor::find($this->donor_id);

        if (! $donor) {
            return;
        }

        $this->donor_type = $donor->type->value;
    }

    public function openNewDonorModal(): void
    {
        $this->resetValidation();
        $this->reset(['newDonorName', 'newDonorType', 'newDonorCity']);
        $this->newDonorType = DonorType::Individual->value;
        $this->showNewDonorModal = true;
        $this->dispatch('open-modal', 'new-donor');
    }

    public function closeNewDonorModal(): void
    {
        $this->showNewDonorModal = false;
    }

    public function saveNewDonor(): void
    {
        $this->validate([
            'newDonorName' => ['required', 'string', 'max:255'],
            'newDonorType' => ['required', Rule::in(array_map(fn (DonorType $t) => $t->value, DonorType::cases()))],
            'newDonorCity' => ['required', 'string', 'max:255', Rule::in(config('governorates.egypt', []))],
        ]);

        $donor = Donor::create([
            'name' => $this->newDonorName,
            'type' => $this->newDonorType,
            'city' => $this->newDonorCity,
        ]);

        // Refresh donor options so the combobox picks up the new donor
        unset($this->donorOptions);

        // Auto-select the newly created donor
        $this->donor_id = $donor->id;

        $this->showNewDonorModal = false;
        $this->dispatch('close-modal', 'new-donor');
        $this->dispatch('notify', message: 'تم إضافة المتبرع بنجاح.', type: 'success');
    }

    protected function rules(): array
    {
        return [
            'donor_id' => ['nullable', 'exists:donors,id'],
            'donor_type' => ['nullable', Rule::in(array_map(fn (DonorType $type) => $type->value, DonorType::cases()))],
            'project_id' => ['nullable', 'exists:projects,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(array_map(fn (DonationMethod $method) => $method->value, DonationMethod::cases()))],
            'type' => ['required', Rule::in(array_map(fn (DonationType $type) => $type->value, DonationType::cases()))],
            'donated_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.required' => 'قيمة التبرع مطلوبة',
            'amount.numeric' => 'قيمة التبرع يجب أن تكون رقماً',
            'amount.min' => 'قيمة التبرع يجب أن تكون أكبر من صفر',
            'method.required' => 'وسيلة التبرع مطلوبة',
            'type.required' => 'نوع التبرع مطلوب',
            'donated_at.required' => 'تاريخ التبرع مطلوب',
            'donated_at.date' => 'تاريخ التبرع غير صالح',
        ];
    }

    public function save(): void
    {
        $this->syncSelectedDonor();

        $data = $this->validate();

        // Get donor name from the selected registered donor, or leave as null
        $donorName = null;
        if (! blank($this->donor_id)) {
            $donor = Donor::find($this->donor_id);
            $donorName = $donor?->name;
        }

        Donation::create([
            'donor_id' => blank($data['donor_id']) ? null : (int) $data['donor_id'],
            'project_id' => blank($data['project_id']) ? null : (int) $data['project_id'],
            'donor_name' => $donorName,
            'donor_type' => $data['donor_type'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'type' => $data['type'],
            'donated_at' => $data['donated_at'],
            'notes' => $data['notes'],
            'created_by' => auth()->id(),
        ]);

        $this->dispatch('notify', message: 'تم إضافة التبرع بنجاح', type: 'success');

        $this->redirect(route('donations.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.donations.create', [
            'donors' => $this->donorOptions,
            'projects' => $this->projectOptions,
            'donorTypeOptions' => $this->donorTypeOptions,
            'governorateOptions' => $this->governorateOptions(),
            'methodOptions' => $this->methodOptions,
            'typeOptions' => $this->typeOptions,
        ]);
    }
}
