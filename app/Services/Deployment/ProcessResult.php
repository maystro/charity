<?php

namespace App\Services\Deployment;

/**
 * Immutable result of a single whitelisted deployment command.
 */
class ProcessResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $output,
        public readonly ?int $exitCode,
    ) {
    }
}
