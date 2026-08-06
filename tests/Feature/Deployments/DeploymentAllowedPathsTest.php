<?php

namespace Tests\Feature\Deployments;

use App\Livewire\Deployments\AllowedPaths;
use App\Models\DeploymentAllowedPath;
use App\Models\User;
use App\Support\Deployment\DeploymentPaths;
use App\Support\Deployment\ProjectSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeploymentAllowedPathsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_root_entries_only(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $test = Livewire::actingAs($superAdmin)
            ->test(AllowedPaths::class)
            ->assertOk();

        // مسارات config الافتراضية تُرفع إلى جذورها (composer.json ضمن القائمة)
        $test->assertSet('selected', ['app', 'composer.json', 'config', 'database', 'lang', 'resources', 'routes']);

        $paths = array_column($test->get('entries'), 'path');

        // عناصر الجذر فقط — لا مسارات داخلية
        $this->assertContains('app', $paths);
        $this->assertContains('routes', $paths);
        $this->assertContains('composer.json', $paths);
        $this->assertNotContains('vendor', $paths);
        $this->assertNotContains('node_modules', $paths);

        // لا يوجد أي عنصر داخلي (لا يحتوي على شرطة مائلة)
        $this->assertSame([], array_values(array_filter(
            $paths,
            fn (string $path): bool => str_contains($path, '/')
        )));
    }

    public function test_mount_lifts_deep_saved_paths_to_their_root(): void
    {
        DeploymentAllowedPath::create(['path' => 'database/migrations']);
        DeploymentAllowedPath::create(['path' => 'composer.json']);

        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(AllowedPaths::class)
            ->assertSet('selected', ['composer.json', 'database']);
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(AllowedPaths::class)
            ->assertForbidden();
    }

    public function test_super_admin_can_save_allowed_paths(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(AllowedPaths::class)
            ->set('selected', ['app', 'config', 'composer.json'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertSame(
            ['app', 'composer.json', 'config'],
            DeploymentPaths::allowed()
        );

        // اللقطة تحترم الـ allowlist المحفوظ
        $snapshot = (new ProjectSnapshot)->scan();
        $this->assertArrayHasKey('app/Models/User.php', $snapshot);
        $this->assertArrayHasKey('composer.json', $snapshot);
        $this->assertArrayNotHasKey('routes/web.php', $snapshot);
        $this->assertArrayNotHasKey('README.md', $snapshot);
    }

    public function test_save_rejects_paths_that_do_not_exist_in_project(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(AllowedPaths::class)
            ->set('selected', ['app', 'non/existent/path.php', '../outside.php'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['app'], DeploymentPaths::allowed());
    }

    public function test_save_normalizes_children_under_selected_directories(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(AllowedPaths::class)
            ->set('selected', ['app', 'app/Models/User.php', 'routes', 'routes/web.php'])
            ->call('save');

        // المسارات الداخلية ليست من عناصر الجذر فتُرفض، واختيار المجلد يغطي ما بداخله
        $this->assertSame(['app', 'routes'], DeploymentPaths::allowed());
    }

    public function test_empty_table_falls_back_to_config_defaults(): void
    {
        $this->assertSame(
            config('deployment.allowed_paths'),
            DeploymentPaths::allowed()
        );

        $this->assertDatabaseCount('deployment_allowed_paths', 0);
    }

    public function test_excluded_entries_are_shown_disabled_and_never_selectable(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $test = Livewire::actingAs($superAdmin)
            ->test(AllowedPaths::class)
            ->assertOk();

        // العناصر المستبعدة تلقائيًا تظهر للشفافية
        $disabled = array_column($test->get('disabledEntries'), 'path');

        $this->assertContains('storage', $disabled);
        $this->assertContains('vendor', $disabled);
        $this->assertContains('node_modules', $disabled);

        // لا تظهر ضمن العناصر القابلة للاختيار
        $enabled = array_column($test->get('entries'), 'path');
        $this->assertNotContains('storage', $enabled);
        $this->assertNotContains('vendor', $enabled);

        // تحديد الكل لا يشمل العناصر المعطّلة أبدًا
        $test->call('selectAll')
            ->assertSet('selected', $enabled);

        // حتى لو أُدخلت يدويًا، الحفظ يرفضها لأنها ليست من عناصر الجذر القابلة للاختيار
        $test->set('selected', [...$enabled, 'storage', 'vendor'])
            ->call('save')
            ->assertHasNoErrors();

        // save() ترتّب النتيجة بـ sort() القياسي (حساس لحالة الأحرف)
        sort($enabled);

        $this->assertSame($enabled, DeploymentPaths::allowed());
    }
}
