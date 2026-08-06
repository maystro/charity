<?php

namespace Tests\Feature\Deployments;

use App\Services\Deployment\DeploymentProcessRunner;
use App\Support\Deployment\DeploymentPathGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeploymentProcessRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_unknown_command_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not allowed');

        app(DeploymentProcessRunner::class)->run('rm-rf');
    }

    public function test_rejects_unknown_command_key_even_if_looks_like_shell(): void
    {
        $this->expectException(RuntimeException::class);

        app(DeploymentProcessRunner::class)->run('migrate; rm -rf /');
    }

    public function test_sanitizes_secrets_from_output(): void
    {
        $runner = new class(app(DeploymentPathGuard::class)) extends DeploymentProcessRunner
        {
            public function exposeSanitizedOutput(string $output): string
            {
                return $this->sanitizeOutput($output);
            }
        };

        $sanitized = $runner->exposeSanitizedOutput(
            "APP_KEY=base64:supersecretvalue\nDB_PASSWORD=hunter2\nMigration ran OK."
        );

        $this->assertStringNotContainsString('supersecretvalue', $sanitized);
        $this->assertStringNotContainsString('hunter2', $sanitized);
        $this->assertStringContainsString('[REDACTED]', $sanitized);
        $this->assertStringContainsString('Migration ran OK.', $sanitized);
    }

    public function test_limits_output_size(): void
    {
        $runner = new class(app(DeploymentPathGuard::class)) extends DeploymentProcessRunner
        {
            public function exposeSanitizedOutput(string $output): string
            {
                return $this->sanitizeOutput($output);
            }
        };

        $sanitized = $runner->exposeSanitizedOutput(str_repeat('a', 20000));

        $this->assertLessThan(9000, strlen($sanitized));
        $this->assertStringContainsString('مقتطع', $sanitized);
    }
}
