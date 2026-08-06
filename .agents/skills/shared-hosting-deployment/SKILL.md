---
name: shared-hosting-deployment
description: "Apply this skill whenever working on Laravel deployment, file/media serving, storage links, or performance issues for this application's production server (charity.phantom-tech.site — Hostinger/LiteSpeed shared hosting). Activates when the user mentions production errors, 403 on /storage, symlink, storage:link, exec() disabled, shared hosting, cPanel, Hostinger, LiteSpeed, APP_URL issues, or files not displaying on the server. Covers serving public files without symlinks, generating correct asset URLs independent of APP_URL, and avoiding disabled PHP functions (exec, shell_exec, symlink)."
license: MIT
metadata:
  author: laravel
---

# Shared Hosting Deployment (Hostinger / LiteSpeed)

Production-specific knowledge for deploying and running this Laravel app on
shared hosting. The production server behaves very differently from local
Herd (PHP 8.4 / macOS): PHP 8.5.4 on LiteSpeed with many functions disabled.

## The Three Hard Constraints (verified 2026-08-06)

1. **`exec()` is disabled** — `Call to undefined function Illuminate\Filesystem\exec()`.
   Anything that shells out (Artisan `storage:link`, `Process::run`, composer
   via shell) fails.
2. **`symlink()` is blocked** — cannot create the `public/storage` link, even
   with native PHP calls.
3. **LiteSpeed blocks ANY URL containing `/storage/`** with 403 BEFORE reaching
   Laravel. Verified:
   - `/media/test.png` → 404 (reaches Laravel ✅)
   - `/storage/anything` → 403 (blocked ❌)
   - `/index.php/storage/test.png` → 403 (blocked even through index.php ❌)

**Conclusion: on this host, files under `storage/app/public` can NEVER be
served via the standard `/storage/` symlink URL. Period.**

## The Working Solution: serve via `/media` through Laravel

Files are served by Laravel itself from `storage/app/public` using a neutral
URL prefix that reaches the framework:

- Route: `GET /media/{path}` → `App\Http\Controllers\StorageFileController`
  (single-action controller, no auth middleware — it serves the public logo
  on the guest login page).
- Controller does `realpath()` + `str_starts_with()` containment check
  (blocks `../` traversal), files only, `immutable` cache header.
- All generated URLs use `asset('media/'.ltrim($path, '/'))` instead of
  `Storage::url()`.

## APP_URL Pitfall

`Storage::url()` builds URLs from `config('filesystems.disks.public.url')`
which reads `APP_URL` — on the server `.env` still contains the stale local
`http://charity.test`, so images point to the wrong host and get blocked.
`asset()` uses the **current request root**, so it works on any host without
touching `.env`. Never use `Storage::url()` in views for this app.

## Where the /media URLs are generated

- `resources/views/livewire/shared/sidebar.blade.php` — org logo
- `resources/views/livewire/pages/login.blade.php` — login page logo
- `resources/views/livewire/pages/aid-requests/show.blade.php` — print logo
- `resources/views/livewire/pages/fieldworkers/index.blade.php` — photos
- `app/Livewire/Organization/Index.php` — logo preview

## Maintenance Page

The "رابط التخزين" button in the maintenance page is now **optional/legacy**:
error messages tell the admin to ignore it because `/media` works regardless.
Do not reintroduce `Artisan::call('storage:link')` or `symlink()` expectations.

## Deployment Reminder

After deploying `routes/web.php` or new controllers to the server, the route
cache must be cleared on the server (maintenance page "حذف الكاش" →
`optimize:clear`) or new routes return 404.

## Rule Files

- `rules/hostinger-constraints.md` — full verified findings & diagnostics
- `rules/file-serving-without-symlink.md` — the controller + route implementation
