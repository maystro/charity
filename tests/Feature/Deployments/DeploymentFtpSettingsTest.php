<?php

namespace Tests\Feature\Deployments;

use App\Livewire\Deployments\FtpSettings;
use App\Models\DeploymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentFtpSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_save_settings_encrypted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(FtpSettings::class)
            ->set('host', 'ftp.example.com')
            ->set('port', '21')
            ->set('username', 'deploy_user')
            ->set('password', 's3cret-pass')
            ->set('rootPath', '/public_html')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertDatabaseHas('deployment_settings', ['key' => 'ftp_host']);
        $this->assertSame('ftp.example.com', app(\App\Services\Deployment\DeploymentFtpSettings::class)->get('ftp_host'));
        $this->assertSame('deploy_user', app(\App\Services\Deployment\DeploymentFtpSettings::class)->get('ftp_username'));

        // كلمة المرور مخزنة مشفّرة — النص الصريح لا يظهر في قاعدة البيانات
        $row = DeploymentSetting::query()->where('key', 'ftp_password')->first();
        $this->assertTrue($row->is_encrypted);
        $this->assertNotSame('s3cret-pass', $row->getRawOriginal('value'));
        $this->assertSame('s3cret-pass', $row->value);
    }

    public function test_saving_empty_password_keeps_saved_one(): void
    {
        app(\App\Services\Deployment\DeploymentFtpSettings::class)->set('ftp_password', 'original-pass');

        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(FtpSettings::class)
            ->set('host', 'ftp.example.com')
            ->set('username', 'deploy_user')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            'original-pass',
            app(\App\Services\Deployment\DeploymentFtpSettings::class)->get('ftp_password')
        );
    }

    public function test_validation_requires_host_and_username(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(FtpSettings::class)
            ->set('host', '')
            ->set('username', '')
            ->call('save')
            ->assertHasErrors(['host', 'username']);
    }

    public function test_test_connection_reports_failure_without_network(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(FtpSettings::class)
            ->set('host', '192.0.2.1') // TEST-NET — لا يوجد سيرفر حقيقي
            ->set('port', '21')
            ->set('username', 'nobody')
            ->set('password', 'nope')
            ->set('rootPath', '/')
            ->call('testConnection')
            ->assertOk()
            ->assertNotSet('testOk', true);
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(FtpSettings::class)
            ->assertForbidden();
    }
}
