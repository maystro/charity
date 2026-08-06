<?php

namespace App\Services\Deployment;

use App\Models\DeploymentSetting;

/**
 * Reads and writes FTP connection credentials for deployments.
 *
 * Values saved from the UI are stored encrypted in the deployment_settings
 * table. Any key without a saved value falls back to config('deployment.ftp').
 */
class DeploymentFtpSettings
{
    public const KEYS = [
        'ftp_host',
        'ftp_port',
        'ftp_username',
        'ftp_password',
        'ftp_root_path',
    ];

    /**
     * Get a single setting: DB first, then config fallback.
     */
    public function get(string $key): ?string
    {
        $row = DeploymentSetting::query()->where('key', $key)->first();

        if ($row !== null) {
            return $row->value;
        }

        $map = [
            'ftp_host' => config('deployment.ftp.host'),
            'ftp_port' => config('deployment.ftp.port'),
            'ftp_username' => config('deployment.ftp.username'),
            'ftp_password' => config('deployment.ftp.password'),
            'ftp_root_path' => config('deployment.ftp.root_path'),
        ];

        $value = $map[$key] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * All FTP credentials as a single array.
     *
     * @return array{host: string|null, port: int, username: string|null, password: string|null, root_path: string}
     */
    public function all(): array
    {
        return [
            'host' => $this->get('ftp_host'),
            'port' => (int) ($this->get('ftp_port') ?: 21),
            'username' => $this->get('ftp_username'),
            'password' => $this->get('ftp_password'),
            'root_path' => $this->get('ftp_root_path') ?: '/',
        ];
    }

    /**
     * Whether a host + username are configured (enough to attempt a login).
     */
    public function isConfigured(): bool
    {
        $creds = $this->all();

        return $creds['host'] !== null && $creds['username'] !== null;
    }

    /**
     * Persist a key/value pair. Sensitive keys are flagged as encrypted.
     */
    public function set(string $key, ?string $value): void
    {
        if (! in_array($key, self::KEYS, true)) {
            throw new \InvalidArgumentException("Unknown deployment setting [{$key}].");
        }

        if ($value === null || $value === '') {
            DeploymentSetting::query()->where('key', $key)->delete();

            return;
        }

        DeploymentSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'is_encrypted' => $key === 'ftp_password',
            ]
        );
    }
}
