<?php

namespace App\Livewire\Visits;

use App\Enums\VisitStatus;
use App\Models\Visit;

#[Layout('layouts.app', ['title' => 'تعديل الزيارة'])]
class Edit extends Create
{
    public function mount(?Visit $visit = null): void
    {
        if (! $visit || ! $visit->exists) {
            abort(404);
        }

        // Only allow editing visits that are still pending
        if (! in_array($visit->status, VisitStatus::pendingStatuses(), true)) {
            abort(403, 'لا يمكن تعديل زيارة منتهية أو ملغاة.');
        }

        parent::mount($visit);
    }
}
