<?php

namespace App\Policies;

use App\Models\AidRequest;
use App\Models\User;

class AidRequestPolicy
{
    /**
     * Determine whether the user can view any aid requests.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('aid_requests.view_any');
    }

    /**
     * Determine whether the user can view the aid request.
     */
    public function view(User $user, AidRequest $aidRequest): bool
    {
        return ($user->isAdmin() || $user->isFieldworker())
            && $this->belongsToSameScope($user, $aidRequest);
    }

    /**
     * Determine whether the user can create an aid request.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isFieldworker();
    }

    /**
     * Determine whether the user can update a draft or needs_completion aid request.
     */
    public function update(User $user, AidRequest $aidRequest): bool
    {
        return ($user->isAdmin() || $user->isFieldworker())
            && in_array($aidRequest->status, ['draft', 'needs_completion'], true)
            && $this->belongsToSameScope($user, $aidRequest);
    }

    /**
     * Determine whether the user can submit the aid request for review.
     */
    public function submit(User $user, AidRequest $aidRequest): bool
    {
        return ($user->isAdmin() || $user->isFieldworker())
            && $aidRequest->status === 'draft'
            && $this->belongsToSameScope($user, $aidRequest);
    }

    /**
     * Determine whether the user can delete a draft aid request.
     */
    public function delete(User $user, AidRequest $aidRequest): bool
    {
        return $user->isAdmin()
            || ($user->isFieldworker()
                && $aidRequest->status === 'draft'
                && $this->belongsToSameScope($user, $aidRequest));
    }

    /**
     * Determine whether the user can override an expired research restriction.
     */
    public function overrideExpiredResearch(User $user, AidRequest $aidRequest): bool
    {
        return $user->can('aid_requests.override_expired_research') && $this->belongsToSameScope($user, $aidRequest);
    }

    /**
     * Helper to ensure the user and aid request belong to the same branch/area scope.
     */
    protected function belongsToSameScope(User $user, AidRequest $aidRequest): bool
    {
        // Example logic: admins can access all, others limited by branch.
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isFieldworker()
            && ($aidRequest->submitted_by === $user->id || $aidRequest->created_by === $user->id);
    }
}
