<?php

namespace App\Livewire\Visits;

use App\Enums\FamilyStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\AidRequest;
use App\Models\Family;
use App\Models\Fieldworker;
use App\Models\SocialResearch;
use App\Models\Visit;
use App\Services\Visits\VisitService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'إضافة زيارة'])]
class Create extends Component
{
    public ?int $editingId = null;

    public ?int $family_id = null;

    public string $visit_type = 'other';

    public string $priority = 'medium';

    public ?string $purpose = null;

    public ?int $research_id = null;

    public ?int $aid_request_id = null;

    public ?int $researcher_id = null;

    public ?int $representative_id = null;

    public ?string $scheduled_at = null;

    public ?int $duration_minutes = null;

    public ?string $address_snapshot = null;

    public ?string $notes = null;

    public string $status = 'scheduled';

    protected function familyQuery(): Builder
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return Family::query()->where('status', FamilyStatus::Approved->value);
        }

        return Family::query()
            ->where('status', FamilyStatus::Approved->value)
            ->where(function (Builder $query) use ($user): void {
                $query->where('submitted_by', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('fieldworker', fn (Builder $fieldworker) => $fieldworker->where('user_id', $user->id));
            });
    }

    #[Computed]
    public function families(): array
    {
        return $this->familyQuery()
            ->orderBy('case_name')
            ->get(['id', 'case_name', 'case_number', 'detailed_address'])
            ->map(fn (Family $family): array => [
                'id' => $family->id,
                'label' => $family->case_name.' ('.$family->case_number.')',
            ])->all();
    }

    #[Computed]
    public function researches(): array
    {
        if (! $this->family_id) {
            return [];
        }

        return SocialResearch::query()
            ->where('family_id', $this->family_id)
            ->orderByDesc('conducted_at')
            ->get(['id', 'research_number', 'status'])
            ->map(fn (SocialResearch $research): array => [
                'id' => $research->id,
                'label' => $research->research_number.' ('.$research->status.')',
            ])->all();
    }

    #[Computed]
    public function aidRequests(): array
    {
        if (! $this->family_id) {
            return [];
        }

        return AidRequest::query()
            ->where('family_id', $this->family_id)
            ->latest()
            ->get(['id', 'request_number', 'title'])
            ->map(fn (AidRequest $request): array => [
                'id' => $request->id,
                'label' => $request->request_number.' — '.$request->title,
            ])->all();
    }

    #[Computed]
    public function fieldworkers(): array
    {
        return Fieldworker::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Fieldworker $fw): array => [
                'id' => $fw->id,
                'label' => $fw->name.' ('.$fw->code.')',
            ])->all();
    }

    /** @return array<string, string> */
    public function visitTypeOptions(): array
    {
        return VisitType::options();
    }

    /** @return array<string, string> */
    public function visitStatusOptions(): array
    {
        return VisitStatus::options();
    }

    public function updatedFamilyId(): void
    {
        // Pre-fill address from family
        if ($this->family_id) {
            $family = Family::find($this->family_id);
            if ($family && empty($this->address_snapshot)) {
                $this->address_snapshot = $family->detailed_address;
            }
        }

        $this->research_id = null;
        $this->aid_request_id = null;
        unset($this->researches, $this->aidRequests);
    }

    public function mount(?Visit $visit = null): void
    {
        if ($visit && $visit->exists) {
            $this->editingId = $visit->id;
            $this->family_id = $visit->family_id;
            $this->visit_type = $visit->visit_type;
            $this->priority = $visit->priority;
            $this->purpose = $visit->purpose;
            $this->research_id = $visit->research_id;
            $this->aid_request_id = $visit->aid_request_id;
            $this->researcher_id = $visit->researcher_id;
            $this->representative_id = $visit->representative_id;
            $this->scheduled_at = $visit->scheduled_at?->format('Y-m-d\TH:i');
            $this->duration_minutes = $visit->duration_minutes;
            $this->address_snapshot = $visit->address_snapshot;
            $this->notes = $visit->notes;
            $this->status = $visit->status;
        }
    }

    public function save(VisitService $visitService): void
    {
        $this->validate([
            'family_id' => ['required', Rule::exists('families', 'id')->where('status', FamilyStatus::Approved->value)],
            'visit_type' => ['required', Rule::in(collect(VisitType::cases())->map->value->all())],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'research_id' => ['nullable', Rule::exists('social_researches', 'id')->where('family_id', $this->family_id)],
            'aid_request_id' => ['nullable', Rule::exists('aid_requests', 'id')->where('family_id', $this->family_id)],
            'researcher_id' => ['nullable', Rule::exists('fieldworkers', 'id')],
            'representative_id' => ['nullable', Rule::exists('fieldworkers', 'id')],
            'scheduled_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'address_snapshot' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($this->familyQuery()->whereKey($this->family_id)->exists(), 403);

        $data = [
            'family_id' => $this->family_id,
            'visit_type' => $this->visit_type,
            'priority' => $this->priority,
            'purpose' => $this->purpose,
            'research_id' => $this->research_id,
            'aid_request_id' => $this->aid_request_id,
            'researcher_id' => $this->researcher_id,
            'representative_id' => $this->representative_id,
            'scheduled_at' => $this->scheduled_at,
            'duration_minutes' => $this->duration_minutes,
            'address_snapshot' => $this->address_snapshot,
            'notes' => $this->notes,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $visit = Visit::findOrFail($this->editingId);
            $visitService->update($visit, $data);
            $message = 'تم تحديث الزيارة بنجاح.';
        } else {
            $visit = $visitService->create($data);
            $message = 'تم حفظ الزيارة بنجاح.';
        }

        $this->dispatch('notify', message: $message, type: 'success');

        $this->redirect(route('visits.show', $visit), navigate: true);
    }

    public function render()
    {
        return view('livewire.pages.visits.create', [
            'families' => $this->families,
            'researches' => $this->researches,
            'aidRequests' => $this->aidRequests,
            'fieldworkers' => $this->fieldworkers,
            'visitTypeOptions' => $this->visitTypeOptions(),
            'visitStatusOptions' => $this->visitStatusOptions(),
            'isEditing' => $this->editingId !== null,
        ]);
    }
}
