<?php

namespace App\Services\Families;

use App\Enums\FamilyStatus;
use App\Models\Family;
use App\Models\FamilyAid;
use App\Models\FamilyAssessment;
use App\Models\FamilyBurden;
use App\Models\FamilyHousing;
use App\Models\FamilyIncomeSource;
use App\Models\FamilyMember;
use App\Models\FamilyResource;
use App\Models\FamilyStatusHistory;
use Illuminate\Support\Facades\Auth;

class ReAssessmentService
{
    /**
     * Start a new re-assessment by copying the current assessment's data.
     */
    public function startReAssessment(Family $family): FamilyAssessment
    {
        $current = $family->currentAssessment;
        $nextRound = ($family->assessments()->max('round') ?? 0) + 1;

        // Create new assessment with copied data
        $newAssessment = FamilyAssessment::create([
            'family_id' => $family->id,
            'round' => $nextRound,
            'status' => FamilyStatus::Draft->value,
            'case_type' => $current->case_type,
            'case_name' => $current->case_name,
            'community' => $current->community,
            'detailed_address' => $current->detailed_address,
            'phone' => $current->phone,
            'family_type' => $current->family_type,
            'members_count' => $current->members_count,
            'total_income' => $current->total_income,
            'average_income_per_person' => $current->average_income_per_person,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        // Copy members
        foreach ($current->members as $member) {
            FamilyMember::create([
                'family_id' => $family->id,
                'family_assessment_id' => $newAssessment->id,
                'name' => $member->name,
                'national_id' => $member->national_id,
                'relationship' => $member->relationship,
                'occupation' => $member->occupation,
                'income' => $member->income,
                'sort_order' => $member->sort_order,
            ]);
        }

        // Copy income sources
        foreach ($current->incomeSources as $source) {
            FamilyIncomeSource::create([
                'family_id' => $family->id,
                'family_assessment_id' => $newAssessment->id,
                'source_type' => $source->source_type,
                'is_active' => $source->is_active,
                'amount' => $source->amount,
                'notes' => $source->notes,
            ]);
        }

        // Copy resources
        foreach ($current->resources as $resource) {
            FamilyResource::create([
                'family_id' => $family->id,
                'family_assessment_id' => $newAssessment->id,
                'resource_type' => $resource->resource_type,
                'quantity' => $resource->quantity,
                'is_active' => $resource->is_active,
                'notes' => $resource->notes,
            ]);
        }

        // Copy burdens
        foreach ($current->burdens as $burden) {
            FamilyBurden::create([
                'family_id' => $family->id,
                'family_assessment_id' => $newAssessment->id,
                'burden_type' => $burden->burden_type,
                'amount' => $burden->amount,
                'notes' => $burden->notes,
            ]);
        }

        // Copy housing
        $housing = $current->housing;
        if ($housing) {
            FamilyHousing::create([
                'family_id' => $family->id,
                'family_assessment_id' => $newAssessment->id,
                'housing_type' => $housing->housing_type,
                'housing_type_other' => $housing->housing_type_other,
                'residence_status' => $housing->residence_status,
                'floors_count' => $housing->floors_count,
                'rooms_count' => $housing->rooms_count,
                'roof_type' => $housing->roof_type,
                'has_water' => $housing->has_water,
                'has_electricity' => $housing->has_electricity,
                'has_sewage' => $housing->has_sewage,
                'finishing_description' => $housing->finishing_description,
                'electrical_appliances' => $housing->electrical_appliances,
                'home_furniture' => $housing->home_furniture,
                'other_equipment' => $housing->other_equipment,
            ]);
        }

        // Copy aids
        foreach ($current->aids as $aid) {
            FamilyAid::create([
                'family_id' => $family->id,
                'family_assessment_id' => $newAssessment->id,
                'aid_type' => $aid->aid_type,
                'eligible' => $aid->eligible,
                'reasons' => $aid->reasons,
            ]);
        }

        return $newAssessment;
    }

    /**
     * Approve an assessment and make it the current one.
     */
    public function approveAssessment(FamilyAssessment $assessment, ?string $notes = null): Family
    {
        $assessment->update([
            'status' => FamilyStatus::Approved->value,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'review_notes' => $notes,
            'updated_by' => Auth::id(),
        ]);

        // Update family to point to new current assessment
        $assessment->family->update([
            'current_assessment_id' => $assessment->id,
            'updated_by' => Auth::id(),
        ]);

        // Record status history
        FamilyStatusHistory::create([
            'family_id' => $assessment->family_id,
            'from_status' => FamilyStatus::Approved->value,
            'to_status' => FamilyStatus::Approved->value,
            'changed_by' => Auth::id(),
            'notes' => $notes ? "اعتماد التقييم رقم {$assessment->round}: {$notes}" : "اعتماد التقييم رقم {$assessment->round}",
            'created_at' => now(),
        ]);

        return $assessment->family->fresh();
    }
}
