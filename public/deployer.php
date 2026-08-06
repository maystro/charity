<?php

/**
 * Smart Deployment server script.
 *
 * Receives two actions from the local SmartDeployment page:
 *
 *   - get_manifest : scans the deployed project files and returns their
 *                    checksums so the app can compare against its local tree.
 *   - deploy       : receives a zip archive, extracts it, creates required
 *                    directories and clears Laravel caches (no exec(), no
 *                    symlink — shared-hosting friendly).
 *   - status       : returns a simple ok with the PHP version.
 *
 * POST with: action, secret, and (for deploy) the zip in the "archive" field.
 */

declare(strict_types=1);

const DEPLOY_SECRET = '4c24c1b4ef548bf710e8518ed63891fe0660e9f5c005bacc';

// Same include/exclude rules as config/deployment.php -> smart
$INCLUDE_PATHS = [
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
];

$EXCLUDE_WITHIN = [
    'public/storage',
    'public/hot',
    'bootstrap/cache',
    'public/build/.vite',
    'node_modules',
    'vendor',
    'storage',
];

function project_root(): string
{
    return dirname(__DIR__);
}

function send_json(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_required_directories(string $root): void
{
    $dirs = [
        'bootstrap/cache',
        'storage/app/public',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
    ];

    foreach ($dirs as $dir) {
        $path = $root.'/'.$dir;

        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }
}

function path_is_included(string $relative): bool
{
    global $INCLUDE_PATHS, $EXCLUDE_WITHIN;

    if (basename($relative) === '.gitignore') {
        return false;
    }

    foreach ($EXCLUDE_WITHIN as $excluded) {
        if ($relative === $excluded || str_starts_with($relative, $excluded.'/')) {
            return false;
        }
    }

    foreach ($INCLUDE_PATHS as $include) {
        if ($relative === $include || str_starts_with($relative, $include.'/')) {
            return true;
        }
    }

    return false;
}

function build_manifest(string $root): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');

        if (! path_is_included($relative)) {
            continue;
        }

        $files[$relative] = md5_file($file->getPathname());
    }

    ksort($files);

    return $files;
}

function clear_caches(string $root): void
{
    $cacheDirs = [
        $root.'/bootstrap/cache',
        $root.'/storage/framework/cache/data',
        $root.'/storage/framework/views',
    ];

    foreach ($cacheDirs as $dir) {
        if (! is_dir($dir)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
    }
}

function extract_archive(string $root, string $archivePath): void
{
    $zip = new ZipArchive();

    if ($zip->open($archivePath) !== true) {
        send_json(['success' => false, 'error' => 'تعذر فتح الأرشيف.'], 400);
    }

    ensure_required_directories($root);

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);

        if ($name === false || str_contains($name, '..')) {
            continue;
        }

        $target = $root.'/'.$name;

        if (substr($name, -1) === '/') {
            @mkdir($target, 0775, true);
        } elseif (! path_is_included($name)) {
            continue;
        } else {
            $dir = dirname($target);

            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $stream = $zip->getStream($name);

            if ($stream === false) {
                continue;
            }

            $fp = @fopen($target, 'wb');

            if ($fp === false) {
                fclose($stream);
                continue;
            }

            while (! feof($stream)) {
                $chunk = fread($stream, 8192);

                if ($chunk === false) {
                    break;
                }

                fwrite($fp, $chunk);
            }

            fclose($fp);
            fclose($stream);
        }
    }

    $zip->close();
    @unlink($archivePath);
}

// --- Router -------------------------------------------------------------

$action = (string) ($_POST['action'] ?? '');
$secret = (string) ($_POST['secret'] ?? '');

if ($action !== 'status' && ! hash_equals(DEPLOY_SECRET, $secret)) {
    send_json(['success' => false, 'error' => 'مفتاح سري غير صحيح.'], 403);
}

$root = project_root();

switch ($action) {
    case 'status':
        send_json([
            'success' => true,
            'php' => PHP_VERSION,
            'root' => $root,
        ]);

    case 'get_manifest':
        send_json([
            'success' => true,
            'files' => build_manifest($root),
        ]);

    case 'deploy':
        if (empty($_FILES['archive']['tmp_name'])) {
            send_json(['success' => false, 'error' => 'لا يوجد ملف أرشيف.'], 400);
        }

        $uploaded = (string) $_FILES['archive']['tmp_name'];
        $tmp = $root.'/storage/app/deploy_'.bin2hex(random_bytes(6)).'.zip';

        if (! is_dir(dirname($tmp))) {
            @mkdir(dirname($tmp), 0775, true);
        }

        if (! move_uploaded_file($uploaded, $tmp)) {
            send_json(['success' => false, 'error' => 'تعذر حفظ الأرشيف.'], 400);
        }

        extract_archive($root, $tmp);
        clear_caches($root);

        send_json([
            'success' => true,
            'message' => 'تم النشر بنجاح.',
            'files' => build_manifest($root),
        ]);

    default:
        send_json(['success' => false, 'error' => 'إجراء غير معروف.'], 400);
}
