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
