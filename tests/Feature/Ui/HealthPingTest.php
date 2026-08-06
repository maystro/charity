<?php

namespace Tests\Feature\Ui;

use Tests\TestCase;

class HealthPingTest extends TestCase
{
    public function test_health_ping_returns_no_content(): void
    {
        $this->get('/health/ping')
            ->assertNoContent();
    }

    public function test_health_ping_head_request_returns_no_content(): void
    {
        $this->head('/health/ping')
            ->assertNoContent();
    }
}
