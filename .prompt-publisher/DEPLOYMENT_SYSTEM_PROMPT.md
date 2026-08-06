🚀 للاستخدام في مشروع جديد:
فقط اعطيني محتوى الملف وقولي "عايز نظام نشر الملفات" وهينفذ من أول مرة صح!


# 🚀 Smart File Deployment System - Complete Implementation Prompt

## Overview
This prompt creates a complete smart file deployment system for Laravel + Filament projects. 
Use this to implement the system from scratch in any new project.

---

## 📋 Requirements

- Laravel 11/12
- Filament 4.x (Admin Panel)
- Shared hosting compatible (no exec, no symlink)

---

## 🎯 Features to Implement

1. **Smart deployment** - Upload only changed files (not entire project)
2. **Two comparison modes**:
   - **Local scan** - Fast, compares with local manifest
   - **Server comparison** - Slower but accurate, compares with actual server files
3. **Progress bar** with real-time updates
4. **Deployment history** stored in database
5. **Dashboard** with sync status and statistics
6. **File preview** before deployment
7. **Reset manifest** option

---

## 📁 Files to Create

### 1. Migration: `database/migrations/XXXX_create_deployments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'deploying', 'success', 'failed'])->default('pending');
            $table->integer('files_count')->default(0);
            $table->bigInteger('total_size')->default(0); // bytes
            $table->text('files_list')->nullable(); // JSON array of deployed files
            $table->text('notes')->nullable();
            $table->longText('server_response')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
```

---

### 2. Model: `app/Models/Deployment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $fillable = [
        'user_id', 'status', 'files_count', 'total_size',
        'files_list', 'notes', 'server_response',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'files_list' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        $seconds = $this->completed_at->diffInSeconds($this->started_at);
        return $seconds < 60 ? "{$seconds} ثانية" : round($seconds / 60, 1) . ' دقيقة';
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->total_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' bytes';
    }
}
```

---

### 3. Config: `config/deployment.php`

```php
<?php

return [
    'server_url' => env('DEPLOY_SERVER_URL', ''),
    'secret_key' => env('DEPLOY_SECRET_KEY', 'MySecurePass2025'),

    // Paths to include in deployment
    'include' => [
        'app',
        'config',
        'database',
        'public',
        'resources',
        'routes',
        'bootstrap',
        'composer.json',
        'composer.lock',
        'package.json',
        'vite.config.js',
        'tailwind.config.js',
        'postcss.config.js',
        'artisan',
    ],

    // Paths to exclude within included folders
    'exclude_within' => [
        'public/storage',
        'public/hot',
        'bootstrap/cache',
        'public/build/.vite',
    ],
];
```

---

### 4. Service: `app/Services/DeploymentService.php`

Key methods to implement:
- `scanLocalFiles()` - Scan included paths and calculate checksums
- `scanDirectory()` - Recursive directory scanning
- `shouldIncludeFile()` - Check if file should be included (exclude .gitignore)
- `getLocalChanges()` - Compare with local manifest
- `deploy()` - Create ZIP and upload to server
- `getRecentDeployments()` - Get deployment history
- `getStats()` - Get sync statistics
- `resetManifest()` - Delete local manifest

**Important implementation notes:**
- Use `config('deployment.include')` for paths
- Use `config('deployment.exclude_within')` for exclusions
- Exclude `.gitignore` files: `basename($path) === '.gitignore'`
- Store manifest at `storage/app/deployment_manifest.json`
- Use `Http::asForm()->attach()` for file upload

---

### 5. Filament Page: `app/Filament/{Panel}/Pages/FileDeployment.php`

Key properties:
```php
public bool $isScanning = false;
public bool $isDeploying = false;
public int $uploadProgress = 0;
public string $currentStatus = 'idle';
public array $changedFiles = [];
public array $newFiles = [];
public array $deletedFiles = [];
public int $totalSize = 0;
public ?string $notes = '';
public ?string $errorMessage = null;
public ?string $successMessage = null;
public array $settings = [];
public array $stats = [];
public ?Collection $recentDeployments = null;
```

Key methods:
- `scanChanges()` - Local comparison
- `scanServerChanges()` - Server comparison (calls get_manifest action)
- `startDeployment()` - Start deployment process
- `executeDeployment()` - Execute actual deployment with files from newFiles/changedFiles
- `resetManifest()` - Reset local manifest

**Important:** `executeDeployment()` must use `$this->deploymentService->deploy()` with the collected files, NOT `deployChanges()`.

---

### 6. Blade View: `resources/views/filament/{panel}/pages/file-deployment.blade.php`

Include:
- Status cards (sync status, last deployment, total files)
- Two scan buttons: "فحص محلي" and "مقارنة مع السيرفر"
- Deploy button (shows when files are pending)
- Reset manifest button
- File lists (new files, changed files)
- Progress bar with animation
- Recent deployments table
- Setup warning if server_url is empty

---

### 7. Server Script: `public/deployer.php`

Actions to implement:
1. `get_manifest` - Scan server files and return checksums (for server comparison)
2. `deploy` - Receive ZIP file, extract, create required directories, clear cache
3. `status` - Return server status

**Key features:**
- No `exec()` - clear cache manually by deleting files
- No `symlink()` - skip storage link
- Create required directories automatically:
  - `bootstrap/cache`
  - `storage/app/public`
  - `storage/framework/cache`
  - `storage/framework/sessions`
  - `storage/framework/views`
  - `storage/logs`
- Exclude `.gitignore` files from manifest
- Use same include/exclude paths as local

---

## 🔧 Environment Variables

```env
DEPLOY_SERVER_URL=https://your-domain.com/deployer.php
DEPLOY_SECRET_KEY=YourSecureKey123
```

---

## ⚠️ Important Implementation Notes

1. **For server comparison**: Server sends its manifest to us, we compare locally (avoids WAF blocking large POST)
2. **Use `asForm()` for HTTP requests** to shared hosting
3. **Increase Livewire payload limit** in `config/livewire.php`:
   ```php
   'payload' => ['max_size' => 1024 * 1024 * 5], // 5MB
   ```
4. **Publish Livewire config first**: `php artisan livewire:publish --config`
5. **For Filament 4**: Use `string|BackedEnum|null` for navigationIcon type

---

## 🚀 Setup Steps for New Project

1. Create migration and run it
2. Create Deployment model
3. Create config/deployment.php
4. Create DeploymentService
5. Create Filament Page
6. Create Blade view
7. Upload deployer.php to production server
8. Add env variables
9. Publish and configure Livewire
10. Test with "مقارنة مع السيرفر" button

---

## 🔐 Security Notes

- Change `DEPLOY_SECRET_KEY` to a strong random value
- The deployer.php validates the key before any action
- Consider adding IP whitelist in production
