<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorageFileServingTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDir = storage_path('app/public/__media_test__');

        if (! is_dir($this->testDir)) {
            mkdir($this->testDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->testDir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }

        parent::tearDown();
    }

    public function test_serves_existing_file_with_correct_headers(): void
    {
        // PNG حقيقي صالح (1×1) حتى يكتشف finfo نوع الملف بشكل صحيح.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        file_put_contents($this->testDir.'/logo.png', $png);

        $response = $this->get('/media/__media_test__/logo.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
        $this->assertSame($png, $response->streamedContent());
    }

    public function test_returns_404_for_missing_file(): void
    {
        $this->get('/media/__media_test__/missing.png')->assertNotFound();
    }

    public function test_rejects_path_traversal_escaping_public_root(): void
    {
        $this->get('/media/../../../.env')->assertNotFound();
        $this->get('/media/%2e%2e/%2e%2e/.env')->assertNotFound();
        $this->get('/media/__media_test__/../../.env')->assertNotFound();
    }

    public function test_rejects_directories(): void
    {
        $this->get('/media/__media_test__')->assertNotFound();
        $this->get('/media')->assertNotFound();
    }
}
