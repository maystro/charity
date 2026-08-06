<?php

namespace App\Livewire\Deployments;

use App\Services\Deployment\DeploymentFtpSettings;
use App\Services\Deployment\FtpClient;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app', ['title' => 'إعدادات النشر'])]
class FtpSettings extends Component
{
    public string $host = '';

    public string $port = '21';

    public string $username = '';

    public string $password = '';

    public string $rootPath = '/';

    /** هل توجد كلمة مرور محفوظة سابقًا (تُظهر تعليمًا بدل كشفها). */
    public bool $hasSavedPassword = false;

    public bool $saving = false;

    public bool $testing = false;

    public ?string $testResult = null;

    public bool $testOk = false;

    public function mount(DeploymentFtpSettings $settings): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $creds = $settings->all();

        $this->host = (string) ($creds['host'] ?? '');
        $this->port = (string) $creds['port'];
        $this->username = (string) ($creds['username'] ?? '');
        $this->rootPath = $creds['root_path'] ?: '/';
        $this->hasSavedPassword = $creds['password'] !== null;
    }

    public function render(): View
    {
        return view('livewire.pages.deployments.ftp-settings');
    }

    public function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'rootPath' => ['nullable', 'string', 'max:500', 'starts_with:/'],
        ];
    }

    public function save(DeploymentFtpSettings $settings): void
    {
        $this->validate();

        try {
            $settings->set('ftp_host', trim($this->host));
            $settings->set('ftp_port', trim($this->port));
            $settings->set('ftp_username', trim($this->username));
            $settings->set('ftp_root_path', $this->rootPath ?: '/');

            // فارغ = إبقاء كلمة المرور المحفوظة؛ غير فارغ = تحديثها.
            if ($this->password !== '') {
                $settings->set('ftp_password', $this->password);
                $this->password = '';
                $this->hasSavedPassword = true;
            }

            $this->dispatch('notify', message: 'تم حفظ إعدادات النشر بنجاح.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * Connect with the currently entered values (or saved ones) and report back.
     */
    public function testConnection(DeploymentFtpSettings $settings): void
    {
        $this->validate();

        $this->testing = true;
        $this->testResult = null;
        $this->testOk = false;

        $password = $this->password !== ''
            ? $this->password
            : ($settings->get('ftp_password') ?? '');

        $client = new FtpClient([
            'host' => trim($this->host),
            'port' => (int) $this->port,
            'username' => trim($this->username),
            'password' => $password,
            'root_path' => $this->rootPath ?: '/',
        ], timeout: 8);

        try {
            $client->connect();
            $this->testResult = 'تم الاتصال بنجاح وتم تسجيل الدخول.';
            $this->testOk = true;
        } catch (RuntimeException $e) {
            $this->testResult = $e->getMessage();
        } finally {
            $client->disconnect();
            $this->testing = false;
        }
    }
}
