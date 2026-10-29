<?php

namespace App\Livewire\Deployments;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;
use Throwable;

#[Layout('layouts.app', ['title' => 'الصيانة'])]
class Maintenance extends Component
{
    public bool $isClearingCache = false;

    public bool $isBuildingCache = false;

    public bool $isUpdatingPackages = false;

    public bool $isRunningMigrations = false;

    public bool $freshMode = false;

    public bool $isLinkingStorage = false;

    public bool $storageLinkExists = false;

    public ?string $migrationOutput = null;

    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->storageLinkExists = is_link(public_path('storage'));
    }

    /**
     * Number of migration files that have not been recorded as run yet.
     * Returns null when the migrations repository is unavailable (fresh database).
     */
    #[Computed]
    public function pendingMigrationsCount(): ?int
    {
        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
        } catch (Throwable) {
            return null;
        }

        return count(array_diff(array_keys($files), $ran));
    }

    /**
     * Entry point for the migrate button: the switch changes what the button runs.
     * In fresh mode a destructive confirmation is required first.
     */
    public function startMigrations(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if ($this->freshMode) {
            $this->dispatch('open-modal', 'confirm-fresh-migrations');

            return;
        }

        $this->runMigrations();
    }

    public function runMigrations(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->executeMigrations(fresh: false);
    }

    public function runFreshMigrations(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_unless($this->freshMode, 403);

        $this->executeMigrations(fresh: true);
    }

    private function executeMigrations(bool $fresh): void
    {
        $this->isRunningMigrations = true;
        $this->errorMessage = null;
        $this->statusMessage = null;
        $this->migrationOutput = null;

        // Migrations may take longer than the default max_execution_time.
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        try {
            // Artisan::call runs in-process: no terminal, exec() or shell needed.
            $exitCode = $fresh
                ? Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true])
                : Artisan::call('migrate', ['--force' => true]);

            $this->migrationOutput = trim(Artisan::output());

            if ($exitCode !== 0) {
                throw new RuntimeException($this->migrationOutput !== '' ? $this->migrationOutput : 'فشل تنفيذ المايجريشن.');
            }

            $this->statusMessage = $this->migrationOutput !== ''
                ? $this->migrationOutput
                : ($fresh ? 'تمت إعادة تهيئة قاعدة البيانات وتشغيل البذور بنجاح.' : 'تم تنفيذ المايجريشن بنجاح.');

            $this->dispatch(
                'notify',
                message: $fresh ? 'تمت إعادة تهيئة قاعدة البيانات وتشغيل البذور.' : 'تم تنفيذ المايجريشن بنجاح.',
                type: 'success',
            );

            // Never leave the destructive switch armed after a successful reset.
            $this->freshMode = false;
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isRunningMigrations = false;
        }
    }

    public function clearCaches(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->isClearingCache = true;
        $this->errorMessage = null;
        $this->statusMessage = null;

        try {
            $completed = [];
            $warnings = [];

            if (Artisan::call('optimize:clear') === 0) {
                $completed[] = '• كاش Bootstrap (الإعدادات والمسارات والعروض والأحداث)';
            } else {
                $warnings[] = 'تعذّر مسح كاش Bootstrap (optimize:clear).';
            }

            // blade-icons registers its commands only in console context,
            // so icons:clear is not reachable through Artisan::call from a web request.
            $icons = $this->runArtisanSubprocess('icons:clear');

            if ($icons['success']) {
                $completed[] = '• كاش الأيقونات (Blade Icons)';
            } else {
                $warnings[] = 'تعذّر مسح كاش الأيقونات'.$this->appendSubprocessReason($icons);
            }

            if ($completed === []) {
                throw new RuntimeException('فشل حذف الكاش — راجع سجلات التطبيق.');
            }

            $this->statusMessage = "تم حذف الكاش بشكل كامل\n\n"
                .implode("\n", $completed)
                .(count($warnings) > 0 ? "\n\n".implode("\n", $warnings) : '');

            $this->dispatch('notify', message: $this->statusMessage, type: 'success');
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isClearingCache = false;
        }
    }

    public function buildCaches(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->isBuildingCache = true;
        $this->errorMessage = null;
        $this->statusMessage = null;

        try {
            $steps = [
                'config:cache' => 'كاش الإعدادات',
                'route:cache' => 'كاش المسارات',
                'view:cache' => 'كاش وتجميع العروض',
                'event:cache' => 'كاش الأحداث والمستمعين',
                'icons:cache' => 'كاش الأيقونات (Blade Icons)',
            ];

            $completed = [];
            $failed = [];

            foreach ($steps as $command => $label) {
                if ($command === 'icons:cache') {
                    // Same console-only registration as above: run in a subprocess.
                    $icons = $this->runArtisanSubprocess($command);

                    if ($icons['success']) {
                        $completed[] = $label;
                    } else {
                        $failed[] = 'تعذّر بناء '.$label.' ('.$command.')'.$this->appendSubprocessReason($icons);
                    }

                    continue;
                }

                if (Artisan::call($command) === 0) {
                    $completed[] = $label;

                    continue;
                }

                $output = trim(Artisan::output());
                $failed[] = 'تعذّر بناء '.$label.' ('.$command.')'.($output !== '' ? "\n".$output : '');
            }

            if ($completed === []) {
                throw new RuntimeException("فشل بناء الكاش\n\n".implode("\n\n", $failed));
            }

            $this->statusMessage = 'تم بناء الكاش بنجاح ('.count($completed).' من '.count($steps)."):\n"
                .implode("\n", array_map(fn (string $label): string => '• '.$label, $completed));

            if (count($failed) > 0) {
                $this->statusMessage .= "\n\n".implode("\n\n", $failed);
            }

            $this->dispatch('notify', message: $this->statusMessage, type: 'success');
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isBuildingCache = false;
        }
    }

    /**
     * Runs an artisan command in a separate console PHP process, needed for
     * commands that are registered only when runningInConsole() is true.
     *
     * @return array{success: bool, message: string}
     */
    private function runArtisanSubprocess(string $command): array
    {
        if (! function_exists('proc_open')) {
            return ['success' => false, 'message' => ' — هذه الاستضافة تمنع proc_open، نفّذ `php artisan '.$command.'` من الطرفية.'];
        }

        try {
            $result = Process::path(base_path())
                ->timeout(120)
                ->run(escapeshellarg($this->phpBinary()).' artisan '.$command.' --no-interaction');

            if ($result->successful()) {
                return ['success' => true, 'message' => ''];
            }

            return ['success' => false, 'message' => ' — '.(trim($result->errorOutput()) !== '' ? trim($result->errorOutput()) : trim($result->output()))];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => ' — '.$e->getMessage()];
        }
    }

    /**
     * @param  array{success: bool, message: string}  $result
     */
    private function appendSubprocessReason(array $result): string
    {
        $message = str_replace(' — ', '', $result['message']);

        return $message !== '' ? "\n  ".$message : ' — أعد المحاولة لاحقًا.';
    }

    public function createStorageLink(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->isLinkingStorage = true;
        $this->errorMessage = null;
        $this->statusMessage = null;

        try {
            $link = public_path('storage');
            $target = storage_path('app/public');

            if (is_link($link)) {
                $this->statusMessage = 'رابط التخزين موجود بالفعل: public/storage.';
                $this->dispatch('notify', message: $this->statusMessage, type: 'info');
            } else {
                // لا نستخدم Artisan::call('storage:link') — فإصدار Laravel القديم
                // على الاستضافة المشتركة يستدعي exec('ln -s ...') وهي معطّلة.
                // دالة symlink() الأصلية لا تحتاج exec إطلاقًا.
                if (! function_exists('symlink')) {
                    throw new RuntimeException('دالة symlink غير متاحة على هذه الاستضافة — يمكنك تجاهل هذا الزر، فالملفات تُعرض تلقائيًا عبر مسار /media دون الحاجة للرابط.');
                }

                if (! @symlink($target, $link)) {
                    $error = error_get_last();

                    throw new RuntimeException('تعذر إنشاء الرابط الرمزي: '.($error['message'] ?? 'خطأ غير معروف — قد تمنع الاستضافة إنشاء الروابط الرمزية').' — يمكنك تجاهل هذا الزر، فالملفات تُعرض تلقائيًا عبر مسار /media دون الحاجة للرابط.');
                }

                $this->storageLinkExists = true;

                $this->statusMessage = 'تم إنشاء رابط التخزين بنجاح: public/storage → storage/app/public.';
                $this->dispatch('notify', message: 'تم إنشاء رابط التخزين بنجاح.', type: 'success');
            }
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isLinkingStorage = false;
        }
    }

    public function updatePackages(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->isUpdatingPackages = true;
        $this->errorMessage = null;
        $this->statusMessage = null;

        try {
            // Shared hosts (Hostinger) often disable proc_open, which Process relies on.
            if (! function_exists('proc_open')) {
                throw new RuntimeException('هذه الاستضافة تمنع proc_open، لذلك لا يمكن تشغيل Composer من داخل الموقع. استخدم Cron Job في hPanel مع composer.phar، أو ابنِ مجلد vendor على جهازك وارفعه مضغوطًا (التفاصيل في بطاقة تحديث الحزم).');
            }

            // Composer may run longer than the web request's default time limit.
            if (function_exists('set_time_limit')) {
                @set_time_limit(600);
            }

            if (function_exists('ignore_user_abort')) {
                @ignore_user_abort(true);
            }

            // Composer must run from the project root (where composer.json lives),
            // never from public/. Walk up from public/ to be certain.
            $projectRoot = $this->composerProjectRoot();
            $command = $this->composerCommand($projectRoot);

            $result = Process::path($projectRoot)
                ->env($this->composerEnvironment())
                ->timeout(900)
                ->run($command);

            if (! $result->successful()) {
                $output = trim($result->output()."\n".$result->errorOutput());

                throw new RuntimeException(
                    ($output !== '' ? $output : 'فشل تحديث الحزم.')
                    ."\n\nالمجلد: {$projectRoot}\nالأمر: {$command}"
                );
            }

            $output = trim($result->output());

            $this->statusMessage = "تم التنفيذ من: {$projectRoot}\n\n"
                .($output !== '' ? $output : 'تم تحديث الحزم بنجاح.')
                ."\n\n".$this->refreshAfterPackageUpdate($projectRoot);

            $this->dispatch('notify', message: 'تم تحديث الحزم بنجاح.', type: 'success');
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('notify', message: $this->errorMessage, type: 'error');
        } finally {
            $this->isUpdatingPackages = false;
        }
    }

    /**
     * The Laravel project root (the folder that contains composer.json),
     * found by walking up from public/ so Composer never runs inside it.
     */
    private function composerProjectRoot(): string
    {
        $directory = public_path();

        for ($depth = 0; $depth < 4; $depth++) {
            $directory = dirname($directory);

            if (is_file($directory.DIRECTORY_SEPARATOR.'composer.json')) {
                return $directory;
            }
        }

        if (is_file(base_path('composer.json'))) {
            return base_path();
        }

        throw new RuntimeException('لم يتم العثور على composer.json خارج مجلد public — تأكد من رفع ملفات المشروع كاملة.');
    }

    private function composerCommand(string $projectRoot): string
    {
        $binary = $this->composerBinary();

        $runner = $binary === 'composer'
            ? 'composer'
            : escapeshellarg($this->phpBinary()).' '.escapeshellarg($binary);

        // --no-scripts matters on shared hosting: the post-update-cmd script
        // (boost:update / vendor:publish) can fail there and would make Composer
        // exit with an error even though every package was installed correctly.
        // Laravel's own caches are rebuilt afterwards by refreshAfterPackageUpdate().
        return sprintf(
            '%s --working-dir=%s update --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts',
            $runner,
            escapeshellarg($projectRoot),
        );
    }

    /**
     * Composer runs with --no-scripts, so the framework caches are rebuilt here,
     * in a separate PHP process that loads the newly installed packages.
     */
    private function refreshAfterPackageUpdate(string $projectRoot): string
    {
        $command = escapeshellarg($this->phpBinary()).' artisan optimize:clear --no-interaction';

        try {
            $result = Process::path($projectRoot)
                ->env($this->composerEnvironment())
                ->timeout(120)
                ->run($command);

            if ($result->successful()) {
                return 'تم تحديث الحزم وتنظيف الكاش بنجاح.';
            }

            $output = trim($result->output()."\n".$result->errorOutput());

            return 'تم تحديث الحزم، لكن تعذّر تنظيف الكاش تلقائيًا — استخدم زر «حذف الكاش».'
                .($output !== '' ? "\n".$output : '');
        } catch (Throwable $e) {
            return 'تم تحديث الحزم، لكن تعذّر تنظيف الكاش تلقائيًا — استخدم زر «حذف الكاش».'."\n".$e->getMessage();
        }
    }

    private function composerBinary(): string
    {
        $candidates = [
            base_path('composer.phar'),
            '/usr/local/bin/composer2.phar',
            '/usr/local/bin/composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'composer';
    }

    private function phpBinary(): string
    {
        $candidates = [
            PHP_BINDIR.DIRECTORY_SEPARATOR.'php',
            '/usr/bin/php',
            '/usr/local/bin/php',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }

    /**
     * Composer needs a writable HOME/COMPOSER_HOME on locked shared hosts.
     *
     * @return array<string, string>
     */
    private function composerEnvironment(): array
    {
        $composerHome = storage_path('framework/composer-home');

        if (! is_dir($composerHome)) {
            @mkdir($composerHome, 0755, true);
        }

        return [
            'HOME' => storage_path('framework'),
            'COMPOSER_HOME' => $composerHome,
            'COMPOSER_CACHE_DIR' => storage_path('framework/composer-cache'),
            'COMPOSER_ALLOW_SUPERUSER' => '1',
        ];
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.maintenance');
    }
}
