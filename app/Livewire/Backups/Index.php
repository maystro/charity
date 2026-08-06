<?php

namespace App\Livewire\Backups;

use App\Models\DatabaseBackup;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'النسخ الاحتياطية'])]
class Index extends Component
{
    public bool $creating = false;

    public ?int $restoringId = null;

    public string $confirmFilename = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    #[Computed]
    public function backups()
    {
        return DatabaseBackup::query()
            ->with('creator')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function latestLabel(): string
    {
        return $this->backups->first()?->created_at?->translatedFormat('Y-m-d H:i') ?? 'لا توجد نسخ';
    }

    #[Computed]
    public function keepCount(): int
    {
        return (int) config('backup.keep', 5);
    }

    #[Computed]
    public function scheduleTime(): string
    {
        return (string) config('backup.schedule_time', '03:00');
    }

    #[Computed]
    public function restoreSupported(): bool
    {
        return app(DatabaseBackupService::class)->restoreSupported();
    }

    #[Computed]
    public function backupToRestore(): ?DatabaseBackup
    {
        if ($this->restoringId === null) {
            return null;
        }

        return DatabaseBackup::find($this->restoringId);
    }

    public function create(): void
    {
        $this->creating = true;

        try {
            $backup = app(DatabaseBackupService::class)->create(auth()->user());

            $this->dispatch('notify', message: 'تم إنشاء النسخة الاحتياطية: '.$backup->filename, type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        } finally {
            $this->creating = false;
        }
    }

    public function download(int $id): mixed
    {
        $backup = DatabaseBackup::findOrFail($id);

        return app(DatabaseBackupService::class)->download($backup);
    }

    public function delete(int $id): void
    {
        $backup = DatabaseBackup::findOrFail($id);

        app(DatabaseBackupService::class)->delete($backup);

        $this->dispatch('notify', message: 'تم حذف النسخة الاحتياطية.', type: 'success');
    }

    public function openRestoreModal(int $id): void
    {
        $this->restoringId = $id;
        $this->confirmFilename = '';
        $this->dispatch('open-modal', 'restore-modal');
    }

    public function closeRestoreModal(): void
    {
        $this->reset(['restoringId', 'confirmFilename']);
        $this->dispatch('close-modal', 'restore-modal');
    }

    public function restore(): void
    {
        $backup = DatabaseBackup::findOrFail($this->restoringId);

        if ($this->confirmFilename !== $backup->filename) {
            $this->addError('confirmFilename', 'اكتب اسم الملف بالضبط للموافقة على الاستعادة.');

            return;
        }

        try {
            app(DatabaseBackupService::class)->restore($backup);

            $this->closeRestoreModal();
            $this->dispatch('notify', message: 'تمت استعادة النسخة الاحتياطية بنجاح.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function render(): View
    {
        return view('livewire.pages.backups.index');
    }
}
