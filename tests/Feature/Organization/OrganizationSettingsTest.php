<?php

namespace Tests\Feature\Organization;

use App\Livewire\Organization\Index;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\OrganizationSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_settings_seeder_provides_the_default_profile(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('organization/logo.png', 'fake-logo');

        $this->seed(OrganizationSettingsSeeder::class);

        $this->assertSame('جمعية التضامن الأجتماعي - بني سويف', SystemSetting::get('organization_name'));
        $this->assertSame('جمعية عهد الخير للتنمية والخدمات', SystemSetting::get('organization_tagline'));
        $this->assertSame('organization/logo.png', SystemSetting::get('organization_logo_path'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('sidebar')
            ->assertSee('جمعية التضامن الأجتماعي - بني سويف')
            ->assertSee('جمعية عهد الخير للتنمية والخدمات')
            ->assertSee(asset('media/organization/logo.png'));
    }

    public function test_admin_can_save_organization_profile_and_sidebar_uses_it(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('organizationName', 'جمعية النور')
            ->set('organizationTagline', 'خدمة المجتمع')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertRedirect(route('organization.index'));

        $logoPath = (string) SystemSetting::get('organization_logo_path');

        $this->assertSame('جمعية النور', SystemSetting::get('organization_name'));
        $this->assertSame('خدمة المجتمع', SystemSetting::get('organization_tagline'));
        $this->assertNotEmpty($logoPath);

        Storage::disk('public')->assertExists($logoPath);

        Volt::test('sidebar')
            ->assertSee('جمعية النور')
            ->assertSee('خدمة المجتمع')
            ->assertSee(asset('media/'.$logoPath));
    }
}
