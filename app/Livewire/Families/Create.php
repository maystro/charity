<?php

namespace App\Livewire\Families;

use App\Enums\FamilyStatus;
use App\Livewire\Forms\FamilyForm;
use App\Models\Family;
use App\Models\FamilyAid;
use App\Models\FamilyBurden;
use App\Models\FamilyHousing;
use App\Models\FamilyIncomeSource;
use App\Models\FamilyMember;
use App\Models\FamilyResource;
use App\Models\FamilyStatusHistory;
use App\Services\Families\FamilyNumberGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Exceptions\ValidationException;

#[Layout('layouts.app', ['title' => 'إضافة أسرة جديدة'])]
class Create extends Component
{
    public FamilyForm $form;

    public string $activeTab = 'basic';

    public bool $submitting = false;

    public bool $showSubmitConfirm = false;

    private const TABS = ['basic', 'members', 'income_resources', 'burdens', 'housing', 'aid'];

    private const TAB_STEPS = [
        'basic' => 1,
        'members' => 2,
        'income_resources' => 3,
        'burdens' => 4,
        'housing' => 5,
        'aid' => 6,
    ];

    public int $currentStep = 1;

    public ?Family $family = null;

    public function mount(?Family $family = null): void
    {
        if ($family && $family->exists) {
            $this->family = $family;
            $this->form->id = $family->id;
            $this->form->case_number = $family->case_number;
            $this->form->case_type = $family->case_type;
            $this->form->case_name = $family->case_name;
            $this->form->community = $family->community ?? '';
            $this->form->detailed_address = $family->detailed_address ?? '';
            $this->form->phone = $family->phone ?? '';
            $this->form->family_type = $family->family_type;

            // Load members
            $this->form->members = $family->members()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (FamilyMember $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'national_id' => $m->national_id ?? '',
                    'relationship' => $m->relationship,
                    'occupation' => $m->occupation ?? '',
                    'income' => (float) $m->income,
                ])
                ->toArray();

            // Load income sources
            $this->form->incomeSources = $family->incomeSources()
                ->get()
                ->keyBy('source_type')
                ->map(fn (FamilyIncomeSource $s) => [
                    'is_active' => $s->is_active,
                    'amount' => (float) $s->amount,
                    'notes' => $s->notes ?? '',
                ])
                ->toArray();

            // Load resources
            $this->form->resources = $family->resources()
                ->get()
                ->keyBy('resource_type')
                ->map(fn (FamilyResource $r) => [
                    'is_active' => $r->is_active,
                    'quantity' => (float) $r->quantity,
                    'notes' => $r->notes ?? '',
                ])
                ->toArray();

            // Load burdens
            $this->form->burdens = $family->burdens()
                ->get()
                ->keyBy('burden_type')
                ->map(fn (FamilyBurden $b) => [
                    'amount' => (float) $b->amount,
                    'notes' => $b->notes ?? '',
                ])
                ->toArray();

            // Load housing
            $housing = $family->housing;
            if ($housing) {
                $this->form->housing_type = $housing->housing_type;
                $this->form->housing_type_other = $housing->housing_type_other ?? '';
                $this->form->residence_status = $housing->residence_status ?? '';
                $this->form->floors_count = $housing->floors_count;
                $this->form->rooms_count = $housing->rooms_count;
                $this->form->roof_type = $housing->roof_type ?? '';
                $this->form->has_water = (bool) $housing->has_water;
                $this->form->has_electricity = (bool) $housing->has_electricity;
                $this->form->has_sewage = (bool) $housing->has_sewage;
                $this->form->finishing_description = $housing->finishing_description ?? '';
                $this->form->electrical_appliances = $housing->electrical_appliances ?? '';
                $this->form->home_furniture = $housing->home_furniture ?? '';
                $this->form->other_equipment = $housing->other_equipment ?? '';
            }
        }
    }

    // ─── Navigation ─────────────────────────────────────────────────────────────

    public function updated($name, $value): void
    {
        // Auto-check is_active when amount > 0 for income sources
        if (preg_match('/^form\.incomeSources\.([a-z_]+)\.amount$/', $name, $m)) {
            $sourceKey = $m[1];
            if ((float) ($value ?? 0) > 0) {
                $this->form->incomeSources[$sourceKey]['is_active'] = true;
            }
        }
    }

    /**
     * Validate the current tab's fields before allowing navigation.
     *
     * @return bool True if the current tab is valid.
     */
    private function validateTab(string $tab): bool
    {
        try {
            match ($tab) {
                'basic' => $this->validate([
                    'form.case_type' => 'required|string',
                    'form.case_name' => 'required|string|max:255',
                    'form.community' => 'required|string',
                    'form.phone' => 'required|string',
                    'form.family_type' => 'required|in:بسيطة,مركبة',
                ]),
                'members' => $this->validate([
                    'form.members' => 'required|array|min:1',
                ]),
                default => true, // income_resources, aid, burdens, housing — optional
            };

            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    public function goToTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->activeTab = $tab;
            $this->currentStep = self::TAB_STEPS[$tab];
        }
    }

    public function nextTab(): void
    {
        // Validate the current tab before moving forward
        if (! $this->validateTab($this->activeTab)) {
            return;
        }

        $currentIndex = array_search($this->activeTab, self::TABS, true);
        if ($currentIndex !== false && $currentIndex < count(self::TABS) - 1) {
            $this->activeTab = self::TABS[$currentIndex + 1];
            $this->currentStep = self::TAB_STEPS[$this->activeTab];
        }
    }

    public function previousTab(): void
    {
        $currentIndex = array_search($this->activeTab, self::TABS, true);
        if ($currentIndex !== false && $currentIndex > 0) {
            $this->activeTab = self::TABS[$currentIndex - 1];
            $this->currentStep = self::TAB_STEPS[$this->activeTab];
        }
    }

    // ─── Members ───────────────────────────────────────────────────────────────

    public function addMember(): void
    {
        $this->form->members[] = [
            'id' => null,
            'name' => '',
            'national_id' => '',
            'relationship' => '',
            'occupation' => '',
            'income' => 0,
        ];
    }

    public function removeMember(int $index): void
    {
        unset($this->form->members[$index]);
        $this->form->members = array_values($this->form->members);
    }

    public function getMembersCount(): int
    {
        return count($this->form->members);
    }

    public function getTotalIncome(): string
    {
        $total = 0;
        foreach ($this->form->incomeSources as $source) {
            if (! empty($source['is_active'])) {
                $total += (float) ($source['amount'] ?? 0);
            }
        }

        return number_format($total, 2);
    }

    public function getAverageIncomePerPerson(): string
    {
        $total = 0;
        foreach ($this->form->incomeSources as $source) {
            if (! empty($source['is_active'])) {
                $total += (float) ($source['amount'] ?? 0);
            }
        }

        $count = count($this->form->members);
        if ($count === 0) {
            return '0.00';
        }

        return number_format($total / $count, 2);
    }

    // ─── Persist ────────────────────────────────────────────────────────────────

    public function saveDraft(): void
    {
        $this->submitting = true;

        $family = $this->persistFamily(FamilyStatus::Draft->value);

        $this->form->id = $family->id;
        $this->form->case_number = $family->case_number;
        $this->submitting = false;

        $this->dispatch('notify', message: 'تم حفظ المسودة بنجاح', type: 'success');
    }

    public function confirmSubmit(): void
    {
        // Validate all required fields before showing the confirmation modal
        try {
            $this->validate([
                'form.case_type' => 'required|string',
                'form.case_name' => 'required|string|max:255',
                'form.community' => 'required|string',
                'form.phone' => 'required|string',
                'form.family_type' => 'required|in:بسيطة,مركبة',
            ]);

            if (count($this->form->members) < 1) {
                $this->addError('form.members', 'يجب إضافة فرد واحد على الأقل.');
                $this->goToTab('members');

                return;
            }

            $this->showSubmitConfirm = true;
        } catch (ValidationException $e) {
            // Validation failed — errors are already set, redirect to the relevant tab
            $this->goToTab('basic');
        }
    }

    public function submit(): void
    {
        $this->submitting = true;
        $this->showSubmitConfirm = false;

        $this->validate([
            'form.case_type' => 'required|string',
            'form.case_name' => 'required|string|max:255',
            'form.community' => 'required|string',
            'form.phone' => 'required|string',
            'form.family_type' => 'required|in:بسيطة,مركبة',
        ]);

        if (count($this->form->members) < 1) {
            $this->addError('form.members', 'يجب إضافة فرد واحد على الأقل.');
            $this->submitting = false;
            $this->goToTab('members');

            return;
        }

        $family = $this->persistFamily(FamilyStatus::UnderReview->value, true);

        $this->submitting = false;

        $this->dispatch('notify', message: 'تم إرسال الأسرة للمراجعة بنجاح', type: 'success');
        $this->redirect(route('families.index', ['status' => 'under_review']), navigate: true);
    }

    private function persistFamily(string $status, bool $isSubmit = false): Family
    {
        $data = [
            'case_type' => $this->form->case_type,
            'case_name' => $this->form->case_name,
            'community' => $this->form->community,
            'detailed_address' => $this->form->detailed_address ?: null,
            'phone' => $this->form->phone,
            'family_type' => $this->form->family_type,
            'members_count' => count($this->form->members),
            'status' => $status,
        ];

        // Calculate income
        $totalIncome = 0;
        foreach ($this->form->incomeSources as $source) {
            if (! empty($source['is_active'])) {
                $totalIncome += (float) ($source['amount'] ?? 0);
            }
        }
        $data['total_income'] = $totalIncome;
        $data['average_income_per_person'] = count($this->form->members) > 0
            ? $totalIncome / count($this->form->members)
            : 0;

        if ($isSubmit) {
            $data['submitted_by'] = Auth::id();
            $data['submitted_at'] = now();
        }

        if ($this->form->id) {
            $data['updated_by'] = Auth::id();
            $family = Family::findOrFail($this->form->id);
            $family->update($data);
        } else {
            $data['case_number'] = app(FamilyNumberGenerator::class)->generate();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $family = Family::create($data);
        }

        // Sync members
        FamilyMember::where('family_id', $family->id)->delete();
        foreach ($this->form->members as $index => $member) {
            if (! empty($member['name'])) {
                FamilyMember::create([
                    'family_id' => $family->id,
                    'name' => $member['name'],
                    'national_id' => $member['national_id'] ?: null,
                    'relationship' => $member['relationship'],
                    'occupation' => $member['occupation'] ?: null,
                    'income' => (float) ($member['income'] ?? 0),
                    'sort_order' => $index,
                ]);
            }
        }

        // Sync income sources
        FamilyIncomeSource::where('family_id', $family->id)->delete();
        foreach ($this->form->incomeSources as $sourceType => $source) {
            if (! empty($source['is_active']) || ! empty($source['amount'])) {
                FamilyIncomeSource::create([
                    'family_id' => $family->id,
                    'source_type' => $sourceType,
                    'is_active' => $source['is_active'] ?? false,
                    'amount' => (float) ($source['amount'] ?? 0),
                    'notes' => $source['notes'] ?? null,
                ]);
            }
        }

        // Sync resources
        FamilyResource::where('family_id', $family->id)->delete();
        foreach ($this->form->resources as $resourceType => $resource) {
            if (! empty($resource['is_active']) || ! empty($resource['quantity'])) {
                FamilyResource::create([
                    'family_id' => $family->id,
                    'resource_type' => $resourceType,
                    'quantity' => (float) ($resource['quantity'] ?? 0),
                    'is_active' => $resource['is_active'] ?? false,
                    'notes' => $resource['notes'] ?? null,
                ]);
            }
        }

        // Sync burdens
        FamilyBurden::where('family_id', $family->id)->delete();
        foreach ($this->form->burdens as $burdenType => $burden) {
            if (! empty($burden['amount'])) {
                FamilyBurden::create([
                    'family_id' => $family->id,
                    'burden_type' => $burdenType,
                    'amount' => (float) $burden['amount'],
                    'notes' => $burden['notes'] ?? null,
                ]);
            }
        }

        // Sync housing
        FamilyHousing::updateOrCreate(
            ['family_id' => $family->id],
            [
                'housing_type' => $this->form->housing_type,
                'housing_type_other' => $this->form->housing_type_other ?: null,
                'residence_status' => $this->form->residence_status,
                'floors_count' => $this->form->floors_count,
                'rooms_count' => $this->form->rooms_count,
                'roof_type' => $this->form->roof_type ?: null,
                'has_water' => $this->form->has_water,
                'has_electricity' => $this->form->has_electricity,
                'has_sewage' => $this->form->has_sewage,
                'finishing_description' => $this->form->finishing_description ?: null,
                'electrical_appliances' => $this->form->electrical_appliances ?: null,
                'home_furniture' => $this->form->home_furniture ?: null,
                'other_equipment' => $this->form->other_equipment ?: null,
            ]
        );

        // Sync aids
        FamilyAid::where('family_id', $family->id)->delete();
        foreach ($this->form->aids as $aidType => $aid) {
            if (! empty($aid['eligible']) || ! empty($aid['reasons'])) {
                FamilyAid::create([
                    'family_id' => $family->id,
                    'aid_type' => $aidType,
                    'eligible' => $aid['eligible'] ?? false,
                    'reasons' => $aid['reasons'] ?: null,
                ]);
            }
        }

        // Record status history on submit
        if ($isSubmit) {
            FamilyStatusHistory::create([
                'family_id' => $family->id,
                'from_status' => $this->form->id ? ($family->getOriginal('status') ?? FamilyStatus::Draft->value) : null,
                'to_status' => FamilyStatus::UnderReview->value,
                'changed_by' => Auth::id(),
                'created_at' => now(),
            ]);
        }

        return $family->fresh();
    }

    public function render(): View
    {
        return view('livewire.pages.families.create', [
            'membersCount' => $this->getMembersCount(),
            'totalIncome' => $this->getTotalIncome(),
            'averageIncomePerPerson' => $this->getAverageIncomePerPerson(),
        ]);
    }
}
