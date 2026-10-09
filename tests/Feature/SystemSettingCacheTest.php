<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemSettingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_uses_request_cache_after_first_load(): void
    {
        SystemSetting::set('cache_test_key', 'value', 'general', 'test', 'string');
        SystemSetting::flushRequestCache();

        DB::enableQueryLog();

        $first = SystemSetting::get('cache_test_key');
        $second = SystemSetting::get('cache_test_key');

        $this->assertSame('value', $first);
        $this->assertSame('value', $second);
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_set_updates_request_cache(): void
    {
        SystemSetting::flushRequestCache();
        SystemSetting::set('mutable_key', 'two', 'general', 'test', 'string');

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertSame('two', SystemSetting::get('mutable_key'));
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_get_many_loads_missing_keys_in_one_query(): void
    {
        SystemSetting::set('key_a', 'a', 'general', 'a', 'string');
        SystemSetting::set('key_b', 'b', 'general', 'b', 'string');
        SystemSetting::flushRequestCache();

        DB::enableQueryLog();

        $values = SystemSetting::getMany(['key_a', 'key_b']);

        $this->assertSame(['key_a' => 'a', 'key_b' => 'b'], $values);
        $this->assertCount(1, DB::getQueryLog());
    }
}
