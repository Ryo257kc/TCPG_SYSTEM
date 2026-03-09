# Admin Guardrails

## Purpose
Prevent mojibake/parse breakage and cross-page side effects in `tcpg_system_admin_laravel`.

## Always-on checks (pre-commit)
- Scope check: only admin-related paths can be committed.
- Freeze check: files listed in `.freeze-files.txt` cannot be committed.
- Isolation check: max 3 work files and no mixed admin pages in one commit.
- Structure check: new admin views must use `resources/views/admin/<page>/index.blade.php` and `partials/`.
- Encoding check: staged text files must be valid UTF-8.
- BOM check: BOM is forbidden for `*.php` and `*.blade.php`.
- Mojibake check: suspicious mojibake patterns are blocked.
- Syntax check: `php -l` runs on all staged `*.php`.
- View check: `php artisan view:clear && php artisan view:cache` must pass.
- Legacy DB deny: references to `t_time_card` are blocked. Use `m_time_cards` only.

## Full repository guard (recommended before push)
```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/check-repo-encoding.ps1 -Root .
```

This scans the whole repository (except ignored dirs) for:
- invalid UTF-8
- BOM in PHP/Blade files
- mojibake patterns

## One-time setup (local)
```powershell
git config core.hooksPath .githooks
```

## Emergency recovery
If a file is already corrupted, do not append.
- Recreate the file from a clean source.
- Save as UTF-8 (no BOM).
- Run:
```powershell
php -l <target.php>
php artisan view:cache
```
