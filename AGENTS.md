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

## 8) No External File Access
- Never read, inspect, search, or open files/folders outside this project unless the user explicitly names the exact path and explicitly asks for that exact file to be checked.
- Never proactively browse outside-project folders.
- Never write, modify, move, or delete anything outside this project.
- If external file contents are needed, prefer user-pasted content over direct file access.

## 9) Approved External Sample Folder
- Read-only access is allowed for `C:\Users\ryo25\OneDrive\dev\samples`.
- Never write, modify, move, or delete anything in that sample folder.
- Do not browse any other external folders.

## 10) No Unlabeled Approximation In UI
- Never show provisional, approximate, assumed, fallback, or verification-only numbers in the UI unless the user explicitly approves that exact display.
- If a number is not confirmed as the real output value, do not render it as a normal value.
- If temporary verification text is explicitly requested, label it clearly as `仮`, `概算`, or `確認用`.
- Do not mix confirmed values and unconfirmed formula output in the same UI field.
