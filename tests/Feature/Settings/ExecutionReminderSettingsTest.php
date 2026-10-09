<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\SettingsIndex;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExecutionReminderSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_execution_reminder_lead_days(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(SettingsIndex::class)
            ->set('executionReminderLeadDays', 7)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(7, (int) SystemSetting::get('execution_reminder_lead_days'));
    }
}
