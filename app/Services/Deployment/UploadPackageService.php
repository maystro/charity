<?php

namespace App\Services\Deployment;

use App\Models\Release;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Builds a single ZIP archive containing every file referenced by a release.
 *
 * The archive is meant to be uploaded to a shared host (e.g. cPanel) in one
 * step instead of hunting and uploading each changed file manually. Files
 * marked as removed are listed in a REMOVED_FILES.txt note inside the archive
 * because they no longer exist locally.
 */
class UploadPackageService
{
    /**
     * Directory where upload packages are stored.
     */
    public function packageDirectory(): string
    {
        return storage_path('app/deployment-packages');
    }

    /**
     * Build the ZIP package for a release.
     *
     * @return array{path: string, filename: string, count: int, missing: array<int, string>, removed: array<int, string>}
     */
    public function build(Release $release): array
    {
        $directory = $this->packageDirectory();

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $this->clearOldPackages($release);

        $filename = sprintf(
            'release-%d-%s-%s.zip',
            $release->id,
            $this->safeVersion($release->version),
            now()->format('Ymd-His-u')
        );

        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذر إنشاء ملف الحزمة.');
        }

        $count = 0;
        $missing = [];
        $removed = [];

        foreach ($release->changes as $change) {
            $relative = $change->file_path;

            if ($change->type->value === 'removed') {
                $removed[] = $relative;
                continue;
            }

            $absolute = $this->resolvePath($relative);

            if ($absolute === null) {
                $missing[] = $relative;
                continue;
            }

            $zip->addFile($absolute, $relative);
            $count++;
        }

        if ($removed !== []) {
            $note = "الملفات التالية حُذفت من المشروع وتحتاج حذفًا يدويًا من السيرفر:\n\n";

            foreach ($removed as $removedPath) {
                $note .= "- {$removedPath}\n";
            }

            $zip->addFromString('REMOVED_FILES.txt', $note);
        }

        if ($count === 0 && $removed === []) {
            $zip->addFromString('NOTE.txt', "لم يتم العثور على أي ملف قابل للرفع لهذا الإصدار.\nتحقق من مسارات الملفات في التغييرات.");
        }

        $zip->close();

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $count,
            'missing' => $missing,
            'removed' => $removed,
        ];
    }

    /**
     * Resolve a relative path inside the project, or null when unsafe/missing.
     */
    protected function resolvePath(string $relative): ?string
    {
        $relative = str_replace('\\', '/', $relative);

        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, '..')) {
            return null;
        }

        $absolute = realpath(base_path($relative));

        if ($absolute === false || ! str_starts_with($absolute, base_path().DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Remove previously generated packages for the same release.
     */
    protected function clearOldPackages(Release $release): void
    {
        $pattern = $this->packageDirectory().DIRECTORY_SEPARATOR.'release-'.$release->id.'-*.zip';

        foreach (glob($pattern) ?: [] as $old) {
            @unlink($old);
        }
    }

    /**
     * Make a version safe for filenames.
     */
    protected function safeVersion(string $version): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $version) ?? 'v';

        return Str::limit($safe, 40, '');
    }
}
