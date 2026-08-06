<?php

namespace App\Services\Deployment;

use App\Enums\ReleaseStatus;
use App\Models\Release;
use App\Support\Deployment\ProjectSnapshot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReleaseService
{
    /**
     * Create a new release with its changes.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $changes
     */
    public function create(array $data, array $changes): Release
    {
        return DB::transaction(function () use ($data, $changes) {
            /** @var Release $release */
            $release = Release::create([
                'version' => $data['version'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'status' => ReleaseStatus::Draft,
                'source_revision' => $data['source_revision'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($changes as $change) {
                $release->changes()->create([
                    'type' => $change['type'],
                    'file_path' => $change['file_path'],
                    'description' => $change['description'],
                ]);
            }

            // Record a snapshot of the project at this point so the next
            // release can auto-detect what changed since.
            $release->forceFill([
                'file_snapshot' => (new ProjectSnapshot())->scan(),
            ])->save();

            return $release->load('changes', 'creator');
        });
    }

    /**
     * Detect files changed since the latest release that holds a snapshot.
     *
     * When no baseline snapshot exists yet (e.g. right after a database
     * reset), falls back to listing files touched after the most recent
     * release — or, if there are no releases at all, files touched within
     * the configurable detection window. Every fallback file is reported as
     * "added" (first release) or "modified" (a release exists, but no
     * snapshot to diff against).
     *
     * @return array<int, array{file_path: string, type: string, hash: string}>
     */
    public function detectChanges(?Release $since = null): array
    {
        $previous = $since ?? Release::query()
            ->whereNotNull('file_snapshot')
            ->orderByDesc('id')
            ->first();

        $snapshot = new ProjectSnapshot();

        if ($previous !== null) {
            return $snapshot->changesSince(
                $snapshot->allowedOnly((array) $previous->file_snapshot),
                $snapshot->scan()
            );
        }

        $latest = Release::query()->orderByDesc('created_at')->first();
        $cutoff = $latest?->created_at
            ?? now()->subDays((int) config('deployment.detection_window_days', 30));

        return $snapshot->changesByMtimeSince($cutoff, $latest ? 'modified' : 'added');
    }

    /**
     * Whether any release holds a snapshot to diff against.
     */
    public function hasBaselineSnapshot(): bool
    {
        return Release::query()->whereNotNull('file_snapshot')->exists();
    }

    /**
     * Publish a draft release.
     */
    public function publish(Release $release): void
    {
        if (! $release->isDraft()) {
            throw new \RuntimeException('Only draft releases can be published.');
        }

        $release->update([
            'status' => ReleaseStatus::Published,
            'released_at' => now(),
        ]);
    }

    /**
     * Roll back a published release.
     */
    public function rollBack(Release $release): void
    {
        if (! $release->isPublished()) {
            throw new \RuntimeException('Only published releases can be rolled back.');
        }

        $release->update([
            'status' => ReleaseStatus::RolledBack,
        ]);
    }

    /**
     * Delete a draft release.
     */
    public function delete(Release $release): void
    {
        if (! $release->isDraft()) {
            throw new \RuntimeException('Only draft releases can be deleted.');
        }

        if ($release->deployments()->exists()) {
            throw new \RuntimeException('Cannot delete a release that has deployments.');
        }

        $release->delete();
    }
}
