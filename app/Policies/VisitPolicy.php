<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isFieldworker()) {
            return $visit->created_by === $user->id
                || $visit->family?->created_by === $user->id
                || $visit->family?->submitted_by === $user->id
                || $visit->family?->fieldworker?->user_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $visit->created_by === $user->id;
    }

    public function execute(User $user, Visit $visit): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $visit->researcher_id !== null
            && $visit->researcher?->user_id === $user->id;
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->isAdmin();
    }
}
