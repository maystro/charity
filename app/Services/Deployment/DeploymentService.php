<?php

namespace App\Services\Deployment;

use App\Enums\DeploymentEnvironment;
use App\Enums\DeploymentStatus;
use App\Jobs\RunDeploymentJob;
use App\Models\Deployment;
use App\Models\Release;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeploymentService
{
    /**
     * Queue a deployment for a published release on a given environment.
     *
     * Creates a pending deployment record and pushes the job to the queue.
     * No commands are executed inside the HTTP request cycle.
     */
    public function queue(Release $release, DeploymentEnvironment $environment, User $user): Deployment
    {
        if (! $release->isPublished()) {
            throw new \RuntimeException('Only published releases can be deployed.');
        }

        if (! $this->isEnvironmentAllowed($environment)) {
            throw new \RuntimeException("Environment [{$environment->value}] is not allowed.");
        }

        if ($this->hasActiveDeployment($release, $environment)) {
            throw new \RuntimeException("There is already an active deployment for [{$environment->label()}].");
        }

        $deployment = DB::transaction(function () use ($release, $environment, $user) {
            /** @var Deployment $deployment */
            $deployment = Deployment::create([
                'release_id' => $release->id,
                'environment' => $environment,
                'status' => DeploymentStatus::Pending,
                'created_by' => $user->id,
            ]);

            return $deployment;
        });

        // Push after the transaction commits so the job never sees a
        // deployment record that could still be rolled back.
        RunDeploymentJob::dispatch($deployment);

        return $deployment->load('release', 'creator');
    }

    /**
     * Mark a deployment as completed.
     */
    public function markAsCompleted(Deployment $deployment, ?string $sourceRevision = null): void
    {
        if ($deployment->status === DeploymentStatus::Completed) {
            return;
        }

        $deployment->markAsCompleted($sourceRevision);
    }

    /**
     * Mark a deployment as failed with a safe reason.
     */
    public function markAsFailed(Deployment $deployment, string $reason): void
    {
        if ($deployment->status === DeploymentStatus::Failed || $deployment->status === DeploymentStatus::RolledBack) {
            return;
        }

        $deployment->markAsFailed($reason);
    }

    /**
     * Roll back a deployment.
     */
    public function rollback(Deployment $deployment): void
    {
        if (! in_array($deployment->status, [DeploymentStatus::Completed, DeploymentStatus::Failed], true)) {
            throw new \RuntimeException('Only completed or failed deployments can be rolled back.');
        }

        $deployment->markAsRolledBack();
    }

    /**
     * Whether the environment is listed in the application configuration.
     */
    public function isEnvironmentAllowed(DeploymentEnvironment $environment): bool
    {
        return array_key_exists($environment->value, (array) config('deployment.environments', []));
    }

    /**
     * Whether the release already has a pending or in-progress deployment
     * for the same environment.
     */
    public function hasActiveDeployment(Release $release, DeploymentEnvironment $environment): bool
    {
        return $release->deployments()
            ->where('environment', $environment->value)
            ->whereIn('status', [DeploymentStatus::Pending->value, DeploymentStatus::InProgress->value])
            ->exists();
    }
}
