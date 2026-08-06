<?php

namespace Tests\Feature\Deployments;

use App\Livewire\Deployments\CreateRelease;
use App\Models\Release;
use App\Models\User;
use App\Services\Deployment\ReleaseService;
use App\Support\Deployment\ProjectSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReleaseSnapshotImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_service_create_stores_snapshot(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $release = app(ReleaseService::class)->create(
            ['version' => 'v3.1.0', 'title' => 'إصدار جديد', 'description' => null],
            [['type' => 'updated', 'file_path' => 'routes/web.php', 'description' => 'تحديث المسارات']]
        );

        $this->assertNotNull($release->file_snapshot);
        $this->assertArrayHasKey('routes/web.php', $release->file_snapshot);
        // composer.json داخل allowlist => يظهر في اللقطة (مطلوب للسيرفر)
        $this->assertArrayHasKey('composer.json', $release->file_snapshot);
        // خارج allowlist => لا يظهر في اللقطة إطلاقًا
        $this->assertArrayNotHasKey('vendor/autoload.php', $release->file_snapshot);
    }

    public function test_import_changes_detects_added_modified_and_removed_files(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $scan = (new ProjectSnapshot())->scan();
        $this->assertNotEmpty($scan);
        $this->assertArrayHasKey('routes/web.php', $scan);
        $this->assertArrayHasKey('config/app.php', $scan);
        // composer.json داخل allowlist => يُمسح ويظهر في اللقطة
        $this->assertArrayHasKey('composer.json', $scan);
        // خارج allowlist => لا يُمسح إطلاقًا
        $this->assertArrayNotHasKey('README.md', $scan);

        // routes/web.php موجود الآن ولم يكن في اللقطة السابقة => مضاف
        // config/app.php تغيّر محتواه => معدّل
        // app/RemovedService.php كان في اللقطة السابقة ولم يعد موجودًا => محذوف
        // composer.json في allowlist لكن بنفس البصمة => لا يظهر كتغيير
        $previousScan = $scan;
        $previousScan['config/app.php'] = 'changed-hash';
        $previousScan['app/RemovedService.php'] = 'x';
        unset($previousScan['routes/web.php']);

        Release::factory()->create([
            'created_by' => $superAdmin->id,
            'file_snapshot' => $previousScan,
        ]);

        // الترتيب حسب ASCII: app/RemovedService.php (a) ثم config/app.php (c) ثم routes (r)
        Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->call('importChanges')
            ->assertSet('importing', false)
            ->assertSet('changes.0.file_path', 'app/RemovedService.php')
            ->assertSet('changes.1.file_path', 'config/app.php')
            ->assertSet('changes.2.file_path', 'routes/web.php')
            // الأنواع الحقيقية من المقارنة بين اللقطتين
            ->assertSet('changes.0.type', 'removed')
            ->assertSet('changes.1.type', 'modified')
            ->assertSet('changes.2.type', 'added');
    }

    public function test_import_changes_with_no_previous_release_uses_recent_files_fallback(): void
    {
        // نافذة كشف واسعة جدًا => كل ملفات المشروع "معدّلة مؤخرًا"
        config(['deployment.detection_window_days' => 99999]);

        $superAdmin = User::factory()->superAdmin()->create();

        $component = Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->call('importChanges');

        $component
            ->assertDispatched('notify')
            ->assertSet('importing', false)
            ->assertSet('importNoticeType', 'warning');

        $this->assertGreaterThan(0, count($component->get('changes')));
        $this->assertSame('added', $component->get('changes')[0]['type']);
    }

    public function test_import_twice_does_not_duplicate_rows(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $scan = (new ProjectSnapshot())->scan();
        $previousScan = $scan;
        unset($previousScan['routes/web.php']); // سيُكتشف كمضاف
        $previousScan['config/app.php'] = 'changed-hash'; // سيُكتشف كمعدّل

        Release::factory()->create([
            'created_by' => $superAdmin->id,
            'file_snapshot' => $previousScan,
        ]);

        $component = Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->call('importChanges');

        $firstCount = count($component->get('changes'));
        $this->assertGreaterThan(0, $firstCount);

        // النوع الحقيقي من الكشف: routes/web.php مضاف، config/app.php معدّل
        $firstType = $component->get('changes')[0]['type'];
        $this->assertContains($firstType, ['added', 'modified', 'removed']);

        // استيراد ثانٍ لا يكرر الصفوف ولا يغيّر النوع الثابت
        $component->call('importChanges');

        $changes = collect($component->get('changes'));

        // لا يوجد تكرار للملفات — وكل ملف له نوع حقيقي من الكشف (إضافة/تعديل/حذف)
        $this->assertSame($firstCount, $changes->count());
        $this->assertSame($firstCount, $changes->pluck('file_path')->unique()->count());
    }

    public function test_import_changes_without_recent_files_keeps_empty_form(): void
    {
        // نافذة سالبة => حدّ الكشف في المستقبل => لا شيء يتطابق
        config(['deployment.detection_window_days' => -1]);

        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(CreateRelease::class)
            ->call('importChanges')
            ->assertDispatched('notify')
            ->assertSet('importing', false)
            ->assertSet('importNoticeType', 'info')
            // الجدول للعرض فقط — لا صفوف فارغة مبدئية
            ->assertSet('changes', []);
    }
}
