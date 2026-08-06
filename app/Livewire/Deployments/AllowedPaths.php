<?php

namespace App\Livewire\Deployments;

use App\Models\DeploymentAllowedPath;
use App\Support\Deployment\DeploymentPaths;
use App\Support\Deployment\ProjectSnapshot;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'المسارات المسموحة'])]
class AllowedPaths extends Component
{
    /**
     * Checked relative paths (folders and/or files).
     *
     * @var array<int, string>
     */
    public array $selected = [];

    public string $search = '';

    public bool $saving = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $roots = array_column($this->entries, 'path');
        $selected = [];

        // رفع أي مسار محفوظ عميق إلى جذره ليظهر في قائمة الجذر.
        foreach (DeploymentPaths::allowed() as $path) {
            $root = explode('/', $path)[0];

            if (in_array($path, $roots, true)) {
                $selected[] = $path;
            } elseif (in_array($root, $roots, true)) {
                $selected[] = $root;
            }
        }

        $this->selected = array_values(array_unique($selected));
        sort($this->selected);
    }

    /**
     * Root-level project entries only: folders and files directly under base_path().
     * Choosing a folder covers everything inside it.
     *
     * @return array<int, array{path: string, label: string, depth: int, type: string}>
     */
    #[Computed]
    public function entries(): array
    {
        $entries = [];

        foreach (scandir(base_path()) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if ($this->isExcluded($item)) {
                continue;
            }

            $entries[] = [
                'path' => $item,
                'label' => $item,
                'depth' => 0,
                'type' => is_dir(base_path().'/'.$item) ? 'dir' : 'file',
            ];
        }

        usort($entries, function (array $a, array $b) {
            // Folders first, then files — both alphabetical.
            if (($a['type'] === 'dir') !== ($b['type'] === 'dir')) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            return strcmp(mb_strtolower($a['label']), mb_strtolower($b['label']));
        });

        return $entries;
    }

    /**
     * Number of checked paths.
     */
    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selected);
    }

    /**
     * Root-level entries that exist but are always excluded from deployment.
     * Shown for transparency only — never selectable or saved.
     *
     * @return array<int, array{path: string, label: string, depth: int, type: string}>
     */
    #[Computed]
    public function disabledEntries(): array
    {
        $entries = [];

        foreach (scandir(base_path()) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (! $this->isExcluded($item)) {
                continue;
            }

            $entries[] = [
                'path' => $item,
                'label' => $item,
                'depth' => 0,
                'type' => is_dir(base_path().'/'.$item) ? 'dir' : 'file',
            ];
        }

        usort($entries, function (array $a, array $b) {
            // Folders first, then files — both alphabetical.
            if (($a['type'] === 'dir') !== ($b['type'] === 'dir')) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            return strcmp(mb_strtolower($a['label']), mb_strtolower($b['label']));
        });

        return $entries;
    }

    public function selectAll(): void
    {
        $this->selected = array_column($this->entries, 'path');
    }

    public function clearAll(): void
    {
        $this->selected = [];
    }

    public function save(): void
    {
        $this->saving = true;

        try {
            $validPaths = array_flip(array_column($this->entries, 'path'));

            // Reject anything that is not a real project entry.
            $selection = array_values(array_unique(array_filter(
                $this->selected,
                fn (string $path): bool => isset($validPaths[$path])
            )));

            // A checked folder already covers everything under it — drop children.
            $selection = array_values(array_filter(
                $selection,
                fn (string $path): bool => ! $this->isUnderSelectedDir($path, $selection)
            ));

            sort($selection);

            DeploymentAllowedPath::query()->delete();
            foreach ($selection as $path) {
                DeploymentAllowedPath::create(['path' => $path]);
            }

            $this->dispatch('notify', message: 'تم حفظ '.count($selection).' مسار مسموح.', type: 'success');
        } finally {
            $this->saving = false;
        }
    }

    /**
     * Whether the given path sits under another selected directory.
     *
     * @param  array<int, string>  $selection
     */
    protected function isUnderSelectedDir(string $path, array $selection): bool
    {
        foreach ($selection as $candidate) {
            if ($candidate !== $path && Str::startsWith($path, $candidate.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a relative path falls inside any excluded segment.
     */
    protected function isExcluded(string $relative): bool
    {
        foreach (ProjectSnapshot::DEFAULT_EXCLUDES as $exclude) {
            if ($relative === $exclude || Str::startsWith($relative, $exclude.'/')) {
                return true;
            }
        }

        return in_array(basename($relative), ProjectSnapshot::SKIP_FILENAMES, true);
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.allowed-paths');
    }
}
