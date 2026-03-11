# Project Guard Rules (Hard Stop)

## 1) Target Lock
- This repository is `tcpg_system_admin_laravel`.
- If request targets another project, STOP immediately.

## 2) No Cross-Project Editing
- Never edit outside this project.

## 3) OK-Wait Mandatory
- Before substantial edits, print target project and wait for explicit `OK`.

## 4) Hard Preflight
- Before edits, run `scripts/guard-project.ps1 -ExpectedProjectName tcpg_system_admin_laravel`.

## 5) Freeze Respect
- Frozen pages/files are read-only until explicit unlock.

## 6) Failure Behavior
- On rule mismatch: STOP and report mismatch.

## 7) Payroll View Split Rule
- Do not implement new UI blocks in `resources/views/admin_v2/payroll/index.blade.php`.
- Implement new UI only under `resources/views/admin_v2/payroll/partials/*.blade.php`.
- Keep `index.blade.php` as layout shell + `@include` calls only (no large HTML blocks).
- Do not use regex/find-replace bulk edits for Blade/HTML structure changes.
- For layout changes, edit block-by-block and verify after each step.
