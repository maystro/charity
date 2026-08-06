# Serving Public Files Without a Symlink (`/media`)

## Why

Shared hosting (Hostinger/LiteSpeed) blocks `/storage/` URLs with 403 and
disables `exec()`/`symlink()`. The standard Laravel pattern
(`php artisan storage:link` → `public/storage` → `/storage/...`) is impossible.
Solution: serve files from `storage/app/public` **through Laravel** using a
neutral prefix (`/media`) that reaches the framework.

## Implementation

### 1. Controller — `app/Http/Controllers/StorageFileController.php`

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $root = realpath(storage_path('app/public'));
        $file = realpath(storage_path('app/public/'.$path));

        if ($root === false
            || $file === false
            || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            || ! is_file($file)) {
            abort(404);
        }

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
```

Key points:
- `realpath()` resolves `..` so traversal cannot escape the root.
- The `str_starts_with($file, $root.DIRECTORY_SEPARATOR)` check is the
  containment guard.
- `is_file()` rejects directories (avoids listing / weird responses).
- No auth middleware — must be public because the login page logo is served
  to guests.
- `Cache-Control: immutable` because files are content-addressed in practice.

### 2. Route — `routes/web.php`

```php
use App\Http\Controllers\StorageFileController;

Route::get('/media/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('media.file');
```

Place it in the **public** section (before or outside the `auth` group) so the
guest login page can load the logo. The `.*` where constraint allows
sub-directories (`media/organization/logo.png`).

### 3. URL generation — use `asset()`, never `Storage::url()`

```blade
{{-- Wrong: Storage::url() uses APP_URL from .env (stale on server) --}}
<img src="{{ Storage::url($logoPath) }}">

{{-- Right: asset() uses the current request host --}}
<img src="{{ asset('media/'.ltrim($logoPath, '/')) }}">
```

`ltrim($path, '/')` protects against stored paths that start with `/`.

## Testing (feature test)

`tests/Feature/StorageFileServingTest.php` covers:

- serves existing file with correct `Content-Type` + `Cache-Control`
  (use a **real 1×1 PNG** via `base64_decode` so `finfo` detects
  `image/png`; a truncated PNG comes back as `application/octet-stream`).
- `404` for missing files.
- path traversal rejection (`/media/../../../.env`, URL-encoded `%2e%2e`,
  and nested `..` inside the path).
- `404` for bare directories (`/media`, `/media/__media_test__`).

Note on assertion: `BinaryFileResponse` streams content — use
`$response->streamedContent()` (not `getContent()`) and expect
`Cache-Control` re-ordered alphabetically by Symfony
(`immutable, max-age=31536000, public`).

## Deployment checklist

1. `routes/web.php` changed → deploy.
2. `app/Http/Controllers/StorageFileController.php` is new → deploy.
3. After deploy, clear caches on the server (maintenance page "حذف الكاش" →
   `optimize:clear`) so the new route is registered — a stale route cache
   returns 404 for `/media/...`.
