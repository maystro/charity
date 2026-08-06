<?php

namespace Tests\Unit;

use App\Support\Deployment\ProjectSnapshot;
use PHPUnit\Framework\TestCase;

class ProjectSnapshotTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/snapshot-test-'.uniqid();
        mkdir($this->root.'/app', 0755, true);
        mkdir($this->root.'/vendor', 0755, true);
        mkdir($this->root.'/storage', 0755, true);
        mkdir($this->root.'/public/build', 0755, true);

        file_put_contents($this->root.'/app/User.php', '<?php // user');
        file_put_contents($this->root.'/app/Service.php', '<?php // service');
        file_put_contents($this->root.'/vendor/autoload.php', 'skip me');
        file_put_contents($this->root.'/storage/log.txt', 'skip me too');
        file_put_contents($this->root.'/public/build/manifest.json', '{"manifest":true}');
        file_put_contents($this->root.'/README.md', 'readme');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/*') ?: [] as $entry) {
            if (is_dir($entry)) {
                $this->deleteDirectory($entry);
            } else {
                @unlink($entry);
            }
        }

        @rmdir($this->root);

        parent::tearDown();
    }

    public function test_scan_builds_hash_snapshot_and_excludes_directories(): void
    {
        $snapshot = (new ProjectSnapshot())->scan($this->root, ['vendor', 'storage'], ['app', 'README.md']);

        $this->assertArrayHasKey('app/User.php', $snapshot);
        $this->assertArrayHasKey('app/Service.php', $snapshot);
        $this->assertArrayHasKey('README.md', $snapshot);
        $this->assertArrayNotHasKey('vendor/autoload.php', $snapshot);
        $this->assertArrayNotHasKey('storage/log.txt', $snapshot);
        $this->assertSame(md5_file($this->root.'/app/User.php'), $snapshot['app/User.php']);
    }

    public function test_scan_only_includes_files_under_allowed_paths(): void
    {
        $snapshot = (new ProjectSnapshot())->scan($this->root, [], ['app']);

        $this->assertArrayHasKey('app/User.php', $snapshot);
        $this->assertArrayHasKey('app/Service.php', $snapshot);
        $this->assertArrayNotHasKey('README.md', $snapshot);
        $this->assertArrayNotHasKey('public/build/manifest.json', $snapshot);
        $this->assertArrayNotHasKey('vendor/autoload.php', $snapshot);
        $this->assertArrayNotHasKey('storage/log.txt', $snapshot);
    }

    public function test_changes_since_detects_added_modified_and_removed(): void
    {
        $previous = [
            'a.txt' => 'old-hash',
            'b.txt' => 'same-hash',
            'gone.txt' => 'x',
        ];

        $current = [
            'a.txt' => 'new-hash',
            'b.txt' => 'same-hash',
            'c.txt' => 'brand-new',
        ];

        $changes = (new ProjectSnapshot())->changesSince($previous, $current);

        $byPath = collect($changes)->keyBy('file_path');

        $this->assertSame('modified', $byPath['a.txt']['type']);
        $this->assertSame('added', $byPath['c.txt']['type']);
        $this->assertSame('removed', $byPath['gone.txt']['type']);
        $this->assertArrayNotHasKey('b.txt', $byPath);
    }

    public function test_changes_are_sorted_by_file_path(): void
    {
        $previous = ['z.php' => 'a'];
        $current = ['a.php' => 'new', 'z.php' => 'a', 'm.php' => 'new'];

        $changes = (new ProjectSnapshot())->changesSince($previous, $current);

        $paths = array_column($changes, 'file_path');

        $this->assertSame(['a.php', 'm.php'], $paths);
    }

    public function test_changes_by_mtime_since_detects_recently_modified_files(): void
    {
        touch($this->root.'/app/User.php', time() - (86400 * 10)); // منذ 10 أيام
        touch($this->root.'/app/Service.php', time()); // الآن
        touch($this->root.'/README.md', time() - (86400 * 3)); // منذ 3 أيام

        $since = (new \DateTimeImmutable())->modify('-2 days');

        $changes = (new ProjectSnapshot())->changesByMtimeSince($since, 'added', $this->root, ['app', 'README.md']);

        $paths = array_column($changes, 'file_path');

        $this->assertSame('added', $changes[0]['type']);
        $this->assertContains('app/Service.php', $paths);
        $this->assertNotContains('app/User.php', $paths);
        $this->assertNotContains('README.md', $paths);
    }

    public function test_hidden_dot_folders_outside_allowlist_are_excluded(): void
    {
        mkdir($this->root.'/.agents/skills', 0755, true);
        mkdir($this->root.'/.github/workflows', 0755, true);
        file_put_contents($this->root.'/.agents/skills/SKILL.md', '# skill');
        file_put_contents($this->root.'/.github/workflows/deploy.yml', 'on: push');
        file_put_contents($this->root.'/.gitignore', 'ignored');

        $snapshot = (new ProjectSnapshot())->scan($this->root, [], ['app']);

        $this->assertArrayNotHasKey('.agents/skills/SKILL.md', $snapshot);
        $this->assertArrayNotHasKey('.github/workflows/deploy.yml', $snapshot);
        $this->assertArrayNotHasKey('.gitignore', $snapshot);
        $this->assertArrayHasKey('app/User.php', $snapshot);
    }

    public function test_allowed_only_filters_existing_snapshot_to_allowlist(): void
    {
        $snapshot = [
            'app/User.php' => 'a',
            'routes/web.php' => 'b',
            'composer.json' => 'c',
            'README.md' => 'd',
        ];

        $filtered = (new ProjectSnapshot())->allowedOnly($snapshot, ['app', 'routes']);

        $this->assertArrayHasKey('app/User.php', $filtered);
        $this->assertArrayHasKey('routes/web.php', $filtered);
        $this->assertArrayNotHasKey('composer.json', $filtered);
        $this->assertArrayNotHasKey('README.md', $filtered);
    }

    private function deleteDirectory(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $entry) {
            is_dir($entry) ? $this->deleteDirectory($entry) : @unlink($entry);
        }

        @rmdir($dir);
    }
}
