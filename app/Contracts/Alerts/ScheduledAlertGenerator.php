<?php

namespace App\Contracts\Alerts;

interface ScheduledAlertGenerator
{
    /**
     * @return array{created: int, updated: int, resolved: int}
     */
    public function generate(): array;
}
