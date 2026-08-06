# Hostinger / LiteSpeed Constraints — Verified Findings

## Server facts (verified 2026-08-06)

| Fact | Value |
|---|---|
| Production URL | `https://charity.phantom-tech.site` |
| Server software | LiteSpeed (`server: LiteSpeed`) |
| Panel | Hostinger (`platform: hostinger`, `panel: hpanel`) |
| PHP | 8.5.4 (`x-powered-by: PHP/8.5.4`) |

## Diagnostics that prove the constraints

Probe commands (run from local terminal against the production URL):

```bash
# Does /storage/ reach Laravel? (should be 404 if it reaches the framework)
curl -s -o /dev/null -w "%{http_code}\n" -L "https://charity.phantom-tech.site/storage/anything"
#   → 403  (blocked by LiteSpeed BEFORE the app)

# Even through index.php?
curl -s -o /dev/null -w "%{http_code}\n" -L "https://charity.phantom-tech.site/index.php/storage/test.png"
#   → 403  (still blocked)

# Neutral prefixes reach the app (404 = app handled it, no such route/file)
for p in "media/test.png" "uploads/test.png" "random-nonexistent-path-xyz"; do
  curl -s -o /dev/null -w "%{http_code}  /$p\n" -L "https://charity.phantom-tech.site/$p"
done
#   → 404 for all three (reaches Laravel)

# Symlink failure on server (what the admin saw)
#   "Call to undefined function Illuminate\Filesystem\exec()"
#   → Artisan storage:link → Filesystem::link() → exec('ln -s ...') → exec disabled
```

## What is disabled / blocked

- `exec()` — disabled (PHP `disable_functions`). Laravel's
  `Illuminate\Filesystem\Filesystem::link()` falls back to `exec('ln -s …')`
  which throws `Call to undefined function Illuminate\Filesystem\exec()`.
- `symlink()` — blocked by the host even when called directly (`@symlink()`).
- Any URL containing `/storage/` — hard 403 from LiteSpeed regardless of
  whether the file exists, whether a symlink exists, or whether you go through
  `index.php`.

## Practical consequences

1. `php artisan storage:link` can NEVER work on this server.
2. Even a manually-created `public/storage` symlink would not help: the URLs
   `/storage/...` are blocked before the web server resolves them.
3. `Storage::url()` output is unreliable: it is derived from `APP_URL` in
   `.env`, which on the server still contains the stale local
   `http://charity.test`. All CSS/JS worked because Vite/manifest URLs use the
   request host; the logo failed because its URL was built from `APP_URL`.
4. `Process::run()` (Symfony Process → `proc_open`/`exec`) is unavailable for
   package updates / composer runs. Use pure-PHP alternatives or report the
   limitation gracefully.

## The correct mental model

Never assume a host feature works "just like Herd". Verify with curl probes
before writing code that depends on `exec`, `symlink`, shell commands, or any
`/storage/` URL. When in doubt, serve files through Laravel with a neutral
prefix (see `file-serving-without-symlink.md`).
