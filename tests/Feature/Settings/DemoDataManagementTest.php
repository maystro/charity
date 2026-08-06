<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\SettingsIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DemoDataManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_seed_and_delete_demo_data_from_settings(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'مدير النظام',
            'email' => 'admin@charity.org',
            'username' => 'admin',
        ]);

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->assertSee('wire:loading.attr="disabled"', false)
            ->assertSee('wire:target="seedDemoData"', false)
            ->call('seedDemoData')
            ->assertSee('نموذج البيانات التجريبية');

        $this->assertDatabaseCount('fieldworkers', 10);
        $this->assertDatabaseCount('families', 10);
        $this->assertDatabaseCount('family_assessments', 5);
        $this->assertDatabaseCount('social_researches', 10);
        $this->assertDatabaseCount('aid_requests', 10);
        $this->assertDatabaseCount('projects', 10);
        $this->assertDatabaseCount('project_phases', 20);
        $this->assertDatabaseCount('donors', 10);
        $this->assertDatabaseCount('donations', 10);
        $this->assertDatabaseCount('users', 11);
        $this->assertDatabaseHas('donations', [
            'method' => 'instapay',
        ]);

        Livewire::actingAs($admin)
            ->test(SettingsIndex::class)
            ->call('deleteDemoData');

        $this->assertDatabaseCount('fieldworkers', 0);
        $this->assertDatabaseCount('families', 0);
        $this->assertDatabaseCount('family_assessments', 0);
        $this->assertDatabaseCount('social_researches', 0);
        $this->assertDatabaseCount('aid_requests', 0);
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('project_phases', 0);
        $this->assertDatabaseCount('donors', 0);
        $this->assertDatabaseCount('donations', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'username' => 'admin',
        ]);
    }
}
