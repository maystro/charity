<?php

namespace App\Livewire\Visits;

use App\Enums\VisitStatus;
use App\Models\Visit;
use App\Services\Visits\VisitService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'تنفيذ الزيارة'])]
class Execute extends Component
{
    public Visit $visit;

    public int $currentTab = 0;

    // Tab 2: Attendance & Location
    public ?string $actual_started_at = null;

    public ?string $actual_completed_at = null;

    public ?string $contacted_person = null;

    public ?string $contacted_person_relation = null;

    public ?bool $location_verified = false;

    public ?bool $visit_completed = true;

    public ?string $not_completed_reason = null;

    // Tab 3: Notes & Results
    public ?string $outcome_summary = null;

    public ?string $new_needs = null;

    public ?string $urgency_assessment = null;

    // Tab 4: Recommendations & Closure
    public ?string $recommendations = null;

    public ?string $next_follow_up_at = null;

    public function mount(Visit $visit): void
    {
        $this->visit = $visit->load(['family', 'research', 'aidRequest', 'researcher', 'representative']);

        // Pre-fill from existing execution data if any
        $this->actual_started_at = $visit->started_at?->format('Y-m-d\TH:i');
        $this->actual_completed_at = $visit->completed_at?->format('Y-m-d\TH:i');
        $this->contacted_person = $visit->contacted_person;
        $this->contacted_person_relation = $visit->contacted_person_relation;
        $this->location_verified = $visit->location_verified;
        $this->not_completed_reason = $visit->not_completed_reason;
        $this->outcome_summary = $visit->outcome_summary;
        $this->recommendations = $visit->recommendations;
        $this->next_follow_up_at = $visit->next_follow_up_at?->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function visitStatus(): ?VisitStatus
    {
        return VisitStatus::tryFrom($this->visit->status);
    }

    public function goToTab(int $tab): void
    {
        $this->currentTab = $tab;
    }

    public function nextTab(): void
    {
        if ($this->currentTab < 3) {
            $this->currentTab++;
        }
    }

    public function prevTab(): void
    {
        if ($this->currentTab > 0) {
            $this->currentTab--;
        }
    }

    public function saveDraft(): void
    {
        $rules = $this->getTabRules();
        if ($rules !== []) {
            $this->validate($rules);
        }

        $this->visit->update([
            'started_at' => $this->actual_started_at,
            'completed_at' => $this->actual_completed_at,
            'contacted_person' => $this->contacted_person,
            'contacted_person_relation' => $this->contacted_person_relation,
            'location_verified' => $this->location_verified,
            'not_completed_reason' => $this->not_completed_reason,
            'outcome_summary' => $this->outcome_summary,
            'recommendations' => $this->recommendations,
            'next_follow_up_at' => $this->next_follow_up_at,
        ]);

        if ($this->visit->status !== VisitStatus::InProgress->value) {
            // Transition to in_progress on first save
            app(VisitService::class)->update($this->visit, [
                'status' => VisitStatus::InProgress->value,
                'status_notes' => 'بدء تنفيذ الزيارة',
            ]);
        }

        $this->dispatch('notify', message: 'تم حفظ بيانات التنفيذ.', type: 'success');
    }

    public function complete(VisitService $visitService): void
    {
        $this->validate([
            'outcome_summary' => ['required', 'string', 'max:5000'],
            'not_completed_reason' => ['required_if:visit_completed,false', 'nullable', 'string', 'max:2000'],
        ]);

        $data = [
            'started_at' => $this->actual_started_at,
            'completed_at' => now(),
            'contacted_person' => $this->contacted_person,
            'contacted_person_relation' => $this->contacted_person_relation,
            'location_verified' => $this->location_verified,
            'outcome_summary' => $this->outcome_summary,
            'recommendations' => $this->recommendations,
            'next_follow_up_at' => $this->next_follow_up_at,
            'not_completed_reason' => $this->visit_completed ? null : $this->not_completed_reason,
            'completed' => $this->visit_completed,
        ];

        $visitService->execute($this->visit, $data);

        $this->dispatch('notify', message: 'تم إكمال تنفيذ الزيارة بنجاح.', type: 'success');
        $this->redirect(route('visits.show', $this->visit), navigate: true);
    }

    /** @return array<string, mixed> */
    protected function getTabRules(): array
    {
        return match ($this->currentTab) {
            1 => [
                'contacted_person' => ['nullable', 'string', 'max:255'],
                'contacted_person_relation' => ['nullable', 'string', 'max:255'],
            ],
            2 => [
                'outcome_summary' => ['nullable', 'string', 'max:5000'],
            ],
            3 => [
                'recommendations' => ['nullable', 'string', 'max:5000'],
                'next_follow_up_at' => ['nullable', 'date'],
            ],
            default => [],
        };
    }

    public function render()
    {
        return view('livewire.pages.visits.execute');
    }
}
