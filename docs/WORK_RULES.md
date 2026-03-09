# Work Rules

## Scope
- Edit targets are limited to `resources/views/admin/**`, `app/Http/Controllers/Admin/**`, `routes/web.php` (admin routes only), and admin-related docs/scripts.
- Do not edit staff-side pages/routes/controllers.

## Mandatory Start Gate
- Before any change, always output: `TARGET: tcpg_system_admin_laravel`.
- Do not run edit commands until user reply is `OK`.

## Freeze Rule
- Completed screens/files are freeze targets.
- Do not modify freeze targets unless user explicitly approves unfreeze for that file.

## Change Isolation
- One task should touch a minimal set of files (prefer 1-3 files).
- Separate view text fixes and logic fixes into different tasks.

## Verification
- After each change, run:
  - `php artisan view:clear`
  - `php artisan view:cache`
- If either fails, stop and fix before the next change.

## Enforced By Hooks
- Hooks path: `.githooks` (set by `scripts/install-hooks.ps1`)
- Main checker: `scripts/enforce-work-rules.ps1`

### Enforced 7 Rules
1. Admin-scope only: reject staged files outside admin scope.
2. Freeze protection: reject staged files listed in `.freeze-files.txt`.
3. Isolation: reject commit when work-file count exceeds 3.
4. Build check: run `php artisan view:clear` and `php artisan view:cache` before commit.
5. Target declaration: `commit-msg` must include `TARGET: tcpg_system_admin_laravel`.
6. Page-level separation: reject commits that modify multiple admin pages at once.
7. Strict admin-folder rule: reject non-admin views/controllers explicitly.
8. New page structure rule: new admin view files must be
   `resources/views/admin/<page>/index.blade.php`
   or `resources/views/admin/<page>/partials/<name>.blade.php`.

