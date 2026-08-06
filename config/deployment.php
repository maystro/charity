<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment Environments
    |--------------------------------------------------------------------------
    |
    | Define the allowed deployment environments and their configuration.
    | Commands and allowed paths are reviewed before enabling production.
    | Never allow user-supplied paths or commands to reach the Process layer.
    |
    */

    'environments' => [
        'testing' => [
            'label' => 'اختباري',
            'commands' => ['upload', 'migrate', 'cache'],
        ],
        'staging' => [
            'label' => 'تجريبي',
            'commands' => ['upload', 'migrate', 'cache'],
        ],
        // Production: allowlist معتمد من المالك (2026-08-04): migrate + cache فقط.
        // Queue Worker يعمل على جهاز Herd المحلي (php artisan queue:work).
        'production' => [
            'label' => 'إنتاجي',
            'commands' => ['upload', 'migrate', 'cache'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | FTP Upload Settings
    |--------------------------------------------------------------------------
    |
    | Connection details for the shared host (cPanel). These values can be
    | overridden per-environment from the superadmin settings page (stored
    | encrypted in the deployment_settings table).
    |
    */

    'ftp' => [
        'host' => env('DEPLOY_FTP_HOST'),
        'port' => (int) env('DEPLOY_FTP_PORT', 21),
        'username' => env('DEPLOY_FTP_USERNAME'),
        'password' => env('DEPLOY_FTP_PASSWORD'),
        'root_path' => env('DEPLOY_FTP_ROOT_PATH', '/'),
        'passive' => true,
        'timeout' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Paths
    |--------------------------------------------------------------------------
    |
    | The only paths the deployment process may touch. Any path outside this
    | list is rejected before a job starts. Symlinks escaping the project
    | directory are always rejected.
    |
    */

    'allowed_paths' => [
        'app',
        'config',
        'database/migrations',
        'database/seeders',
        'lang',
        'resources',
        'routes',
        'composer.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Change Detection Fallback
    |--------------------------------------------------------------------------
    |
    | When no previous release snapshot exists (e.g. right after a database
    | reset), the auto-import button falls back to listing files whose
    | modification time is within this window (in days).
    |
    */

    'detection_window_days' => (int) env('DEPLOYMENT_DETECTION_WINDOW_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Command Map
    |--------------------------------------------------------------------------
    |
    | Whitelisted command keys mapped to executable command lines. The runner
    | only executes commands referenced by these keys, never raw input.
    |
    */

    'commands' => [
        'install' => 'composer install --no-dev --optimize-autoloader',
        'migrate' => 'php artisan migrate --force',
        'cache' => 'php artisan optimize',
        'config-cache' => 'php artisan config:cache',
        'route-cache' => 'php artisan route:cache',
        'view-cache' => 'php artisan view:cache',
        'event-cache' => 'php artisan event:cache',
        'build' => 'npm run build',
    ],

    /*
    |--------------------------------------------------------------------------
    | Step Labels
    |--------------------------------------------------------------------------
    |
    | Human-readable Arabic labels shown in the progress indicator for each
    | whitelisted command key.
    |
    */

    'step_labels' => [
        'upload' => 'رفع الملفات ومسح كاش السيرفر',
        'install' => 'تثبيت الاعتماديات',
        'migrate' => 'تشغيل الهجرات',
        'cache' => 'تحسين أداء التطبيق',
        'config-cache' => 'تخزين الإعدادات',
        'route-cache' => 'تخزين المسارات',
        'view-cache' => 'تخزين العروض',
        'event-cache' => 'تخزين الأحداث',
        'build' => 'بناء الواجهة',
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Tuning
    |--------------------------------------------------------------------------
    |
    | Queue job behaviour: number of attempts, timeout in seconds, and backoff
    | delays. The job timeout must exceed the sum of expected step timeouts.
    |
    */

    'job' => [
        'tries' => 1,
        'timeout' => 600,
        'backoff' => 30,
        'step_timeout' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stale Deployment Cleanup
    |--------------------------------------------------------------------------
    |
    | If a worker dies without updating the deployment status, a cleanup
    | mechanism marks stuck records as failed after this many minutes.
    |
    */

    'stale_after_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Smart Deployment
    |--------------------------------------------------------------------------
    |
    | The smart deployment page uploads only the changed files. The local
    | manifest (storage/app/deployment_manifest.json) records what was last
    | deployed so a local scan can detect differences instantly.
    |
    | `server_url` points to the public deployer.php script on the server;
    | when empty, server comparison falls back to FTP directory listing.
    |
    */

    'smart' => [
        'server_url' => env('DEPLOY_SERVER_URL', ''),
        'secret_key' => env('DEPLOY_SECRET_KEY', ''),
        'manifest_path' => storage_path('app/deployment_manifest.json'),

        // Paths included in the local scan (relative to project root).
        'include' => [
            'app',
            'config',
            'database/migrations',
            'database/seeders',
            'lang',
            'public',
            'resources',
            'routes',
            'bootstrap',
            'composer.json',
            'composer.lock',
            'package.json',
            'vite.config.js',
            'artisan',
        ],

        // Paths excluded within the included folders.
        'exclude_within' => [
            'public/storage',
            'public/hot',
            'bootstrap/cache',
            'public/build/.vite',
            'node_modules',
            'vendor',
            'storage',
        ],
    ],
];
