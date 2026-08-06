<?php

namespace Tests\Feature\Donations;

use App\Models\Donor;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateDonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_donations_index_shows_the_add_donation_button(): void
    {
        $user = User::factory()->admin()->create();

        Livewire::actingAs($user)
            ->test('donations.index')
            ->assertSee('إضافة تبرع');
    }

    public function test_admin_can_create_a_donation_from_the_create_page(): void
    {
        $user = User::factory()->admin()->create();
        $donor = Donor::factory()->organization()->create(['name' => 'مؤسسة العطاء']);
        $project = Project::factory()->create(['created_by' => $user->id, 'name' => 'مشروع الإغاثة']);

        Livewire::actingAs($user)
            ->test('donations.create')
            ->assertSee('ابحث باسم المتبرع أو الجهة...')
            ->set('donor_id', $donor->id)
            ->set('amount', '2500')
            ->set('method', 'instapay')
            ->set('type', 'cash')
            ->set('project_id', $project->id)
            ->set('donated_at', '2026-07-30')
            ->set('notes', 'تبرع تجريبي')
            ->call('save')
            ->assertRedirect(route('donations.index'));

        $this->assertDatabaseHas('donations', [
            'donor_id' => $donor->id,
            'project_id' => $project->id,
            'donor_name' => 'مؤسسة العطاء',
            'donor_type' => 'organization',
            'amount' => 2500,
            'method' => 'instapay',
            'type' => 'cash',
            'donated_at' => '2026-07-30 00:00:00',
            'notes' => 'تبرع تجريبي',
            'created_by' => $user->id,
        ]);
    }
}
