<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'type',
        'description',
    ];

    /**
     * Parsed values keyed by setting key (memoized for the current PHP request).
     *
     * @var array<string, mixed>
     */
    protected static array $requestCache = [];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$requestCache)) {
            return static::$requestCache[$key];
        }

        $setting = static::where('key', $key)->first();

        if (! $setting) {
            static::$requestCache[$key] = $default;

            return $default;
        }

        $value = match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            default => $setting->value,
        };

        static::$requestCache[$key] = $value;

        return $value;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public static function getMany(array $keys, mixed $default = null): array
    {
        $missing = array_values(array_filter($keys, fn (string $key): bool => ! array_key_exists($key, static::$requestCache)));

        if ($missing !== []) {
            $rows = static::query()->whereIn('key', $missing)->get()->keyBy('key');

            foreach ($missing as $key) {
                $setting = $rows->get($key);
                if (! $setting) {
                    static::$requestCache[$key] = $default;
                } else {
                    static::$requestCache[$key] = match ($setting->type) {
                        'integer' => (int) $setting->value,
                        'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                        default => $setting->value,
                    };
                }
            }
        }

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = static::$requestCache[$key];
        }

        return $result;
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, ?string $group = 'general', ?string $label = null, string $type = 'string', ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'group' => $group,
                'label' => $label,
                'type' => $type,
                'description' => $description,
            ]
        );

        static::$requestCache[$key] = match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
        };
    }

    public static function flushRequestCache(): void
    {
        static::$requestCache = [];
    }
}
