<?php

namespace Tests\Feature\Deployments;

use App\Support\Deployment\DeploymentPathGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeploymentPathGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_path_inside_allowed_scope(): void
    {
        $this->assertSame(
            'app/Models/Deployment.php',
            app(DeploymentPathGuard::class)->validate('app/Models/Deployment.php')
        );
    }

    public function test_accepts_deep_path_under_nested_allowed_entry(): void
    {
        $this->assertSame(
            'database/migrations/2026_01_01_create_users_table.php',
            app(DeploymentPathGuard::class)->validate('database/migrations/2026_01_01_create_users_table.php')
        );
    }

    public function test_accepts_direct_file_entry(): void
    {
        config(['deployment.allowed_paths' => ['composer.json']]);

        $this->assertSame(
            'composer.json',
            app(DeploymentPathGuard::class)->validate('composer.json')
        );
    }

    public function test_rejects_absolute_unix_path(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Absolute paths are not allowed');

        app(DeploymentPathGuard::class)->validate('/etc/passwd');
    }

    public function test_rejects_absolute_windows_path(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Absolute paths are not allowed');

        app(DeploymentPathGuard::class)->validate('C:\\Windows\\System32');
    }

    public function test_rejects_parent_traversal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path traversal is not allowed');

        app(DeploymentPathGuard::class)->validate('app/../config/app.php');
    }

    public function test_rejects_bare_parent_traversal(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path traversal is not allowed');

        app(DeploymentPathGuard::class)->validate('..');
    }

    public function test_rejects_current_directory_prefix(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Path traversal is not allowed');

        app(DeploymentPathGuard::class)->validate('./app/Models/User.php');
    }

    public function test_rejects_path_outside_allowed_scope(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('outside the allowed deployment scope');

        app(DeploymentPathGuard::class)->validate('vendor/laravel/framework/src/Illuminate/Foundation/Application.php');
    }

    public function test_rejects_empty_path(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be empty');

        app(DeploymentPathGuard::class)->validate('   ');
    }

    public function test_rejects_symlink_escaping_project_directory(): void
    {
        $outside = sys_get_temp_dir().'/deploy-guard-'.uniqid();
        @mkdir($outside);
        $linkName = 'deploy-link-'.uniqid();
        $linkPath = base_path($linkName);
        @symlink($outside, $linkPath);

        try {
            config(['deployment.allowed_paths' => [$linkName]]);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('resolves outside the project directory');

            app(DeploymentPathGuard::class)->validate($linkName);
        } finally {
            @unlink($linkPath);
            @rmdir($outside);
        }
    }

    public function test_accepts_symlink_staying_inside_project(): void
    {
        $linkName = 'deploy-inside-'.uniqid();
        $linkPath = base_path($linkName);
        @symlink(base_path('app'), $linkPath);

        try {
            config(['deployment.allowed_paths' => [$linkName]]);

            $this->assertSame(
                $linkName,
                app(DeploymentPathGuard::class)->validate($linkName)
            );
        } finally {
            @unlink($linkPath);
        }
    }
}
