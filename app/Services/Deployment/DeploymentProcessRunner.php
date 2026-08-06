<?php

namespace App\Services\Deployment;

use App\Support\Deployment\DeploymentPathGuard;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Runs deployment commands with strict allowlisting.
 *
 * Only command keys defined in config('deployment.commands') are executed.
 * Raw user input never reaches the Process layer. Output is sanitized: size is
 * limited and common secret patterns are redacted before being stored.
 */
class DeploymentProcessRunner
{
    public function __construct(
        protected DeploymentPathGuard $guard,
    ) {
    }

    /**
     * Run a whitelisted command key, optionally scoped to a validated path.
     *
     * @param  string  $commandKey  A key from config('deployment.commands').
     * @param  string|null  $path  A path validated against the allowlist, or null.
     *
     * @throws RuntimeException when the command key is unknown or the path is rejected.
     */
    public function run(string $commandKey, ?string $path = null, int $timeout = 120): ProcessResult
    {
        $commandLine = config("deployment.commands.{$commandKey}");

        if ($commandLine === null) {
            throw new RuntimeException("Command [{$commandKey}] is not allowed.");
        }

        if ($path !== null) {
            $this->guard->validate($path);
        }

        $process = Process::fromShellCommandline($commandLine, base_path());
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run();

        $output = $this->sanitizeOutput($process->getOutput().$process->getErrorOutput());

        return new ProcessResult(
            successful: $process->isSuccessful(),
            output: $output,
            exitCode: $process->getExitCode(),
        );
    }

    /**
     * Limit output size and redact common secret patterns before storage.
     */
    protected function sanitizeOutput(string $output): string
    {
        $output = preg_replace(
            '/(APP_KEY|DB_PASSWORD|MAIL_PASSWORD|REDIS_PASSWORD|AWS_SECRET_ACCESS_KEY|PAYMENT_SECRET|.*_SECRET|.*_TOKEN|.*PASSWORD)=[^\s]+/i',
            '$1=[REDACTED]',
            $output
        ) ?? $output;

        return Str::limit($output, 8192, '… [مقتطع]');
    }
}
