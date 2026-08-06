<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isFieldworker();
    }

    public function view(User $user, Family $family): bool
    {
        return $user->isAdmin() || $this->ownsFamily($user, $family);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isFieldworker();
    }

    public function editDraft(User $user, Family $family): bool
    {
        return ($user->isAdmin() || $this->ownsFamily($user, $family))
            && in_array($family->status, ['draft', 'needs_completion'], true);
    }

    public function submit(User $user, Family $family): bool
    {
        return $this->editDraft($user, $family);
    }

    public function review(User $user): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, Family $family): bool
    {
        return $user->isAdmin() && $family->status === 'under_review';
    }

    public function returnForCompletion(User $user, Family $family): bool
    {
        return $user->isAdmin() && $family->status === 'under_review';
    }

    public function reject(User $user, Family $family): bool
    {
        return $user->isAdmin() && $family->status === 'under_review';
    }

    public function update(User $user, Family $family): bool
    {
        return $this->editDraft($user, $family);
    }

    public function delete(User $user, Family $family): bool
    {
        return ($user->isAdmin() || $this->ownsFamily($user, $family))
            && $family->status === 'draft';
    }

    private function ownsFamily(User $user, Family $family): bool
    {
        return $family->submitted_by === $user->id
            || $family->created_by === $user->id
            || $family->fieldworker?->user_id === $user->id;
    }
}
