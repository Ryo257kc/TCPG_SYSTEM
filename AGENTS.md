# Project Guard Rules (Hard Stop)

# Rule Source Priority
- `docs/rules/` is the current source of truth for project rules.
- `AGENTS.md` is a temporary guard file kept only until the Japanese docs rules fully replace it.
- If `AGENTS.md` conflicts with `docs/rules/`, follow `docs/rules/`.
- For page-specific work, read `docs/rules/00_global.md` first, then the relevant `docs/rules/{domain}/` files.
## 1) Target Lock
- This repository is `tcpg_system_laravel`.
- If request targets another project, STOP immediately.

## 2) No Cross-Project Editing
- Never edit outside this project.

## 3) OK-Wait Mandatory
- Before substantial edits, print target project and wait for explicit `OK`.

## 4) Hard Preflight
- Before edits, run `scripts/guard-project.ps1 -ExpectedProjectName tcpg_system_laravel`.

## 5) Freeze Respect
- Frozen pages/files are read-only until explicit unlock.

## 6) Failure Behavior
- On rule mismatch: STOP and report mismatch.

## 7) View Structure Rule
- Follow the current grouped structure under `resources/views/admin_v2/`:
  - `master/<page>/`
  - `report/<page>/`
  - `work/<page>/`
- Keep each page's main visible structure in that page folder's `index.blade.php`.
- A page should usually be understandable by opening:
  - the page folder's `index.blade.php`
  - the page CSS in `public/css/admin_v2/`
  - and, only when needed, one small script or one subfolder such as `tabs/`
- Do not split normal page layout into many small Blade partials such as `header`, `filter_form`, `content`, `list`, or `page_style`.
- Use extra Blade files only when there is real multi-page reuse, clearly separate business logic, or a large content unit such as `tabs/`.
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

## 11) No Fallback For Missing Data
- Do not use fallback values in UI or business logic as a substitute for missing source data.
- Use only confirmed data values from the actual source tables/fields.
- If required data is missing, leave it blank or surface the true error and investigate the root cause.
- Do not introduce fallback logic unless the user explicitly approves that exact fallback.

## 12) Shared Frame Rule
- The outer page shell must stay shared. If a common frame changes, all pages using it should change together.
- Place `global_nav` directly under `<body>`.
- Common outer structure such as header, title, width, frame, and spacing must be controlled from shared CSS under `public/css/admin_v2/`.
- Do not create per-page outer layout CSS when the layout is meant to look the same.
- If a page needs a different inner composition, keep the outer shell shared and separate only the inner content structure.
- Do not reuse the class name `wrap` for inner table/list wrappers inside page content.

## 13) CSS Ownership Rule
- Shared button styles, font sizes, frame styles, tabs, form controls, and common spacing must live in shared CSS under `public/css/admin_v2/`.
- Do not create page-specific CSS for UI that should look the same across pages.
- Page-specific CSS should exist only for truly unique inner layouts or unique data presentation.
- Before adding new CSS, check whether it belongs in shared CSS first.

## 14) CSS File Rule
- Styles must be created as real CSS files under `public/css/admin_v2/`, not as Blade style partials.
- Shared frame and shared UI belong in shared CSS files in that folder.
- Page-specific CSS should use one page CSS file such as `staff.css`, `payroll.css`, `rishoku.css`, etc.
- Do not add new `page_style.blade.php` files.
- If a Blade file is saved as `UTF-8 with BOM`, save it again as plain `UTF-8`.

## 15) Page Composition Rule
- A page should be editable by reading as few files as possible.
- For a normal page, the target is:
  - one page folder
  - one `index.blade.php`
  - one page CSS file
  - and, when needed, one small `page_script.blade.php` or one meaningful subfolder such as `tabs/`
- If a file is page-specific, keep it in that page folder rather than a generic `partials/` folder.
- Generic `partials/` folders should not be recreated for new page structure unless the files are genuinely shared by multiple pages.

## 16) Change Scope Rule
- Before a substantial edit, state the exact target files or folders and wait for `OK`.
- Do not expand the requested scope on your own.
- Do not make “while we are here” changes outside the agreed target.
- Do not create new files or folders unless their necessity has been stated before editing.
- Do not apply the same change broadly across other pages until the user has checked the first target and agreed to continue.

## 17) Japanese File Editing Rule
- Do not rewrite Japanese-heavy Blade, PHP, or SQL files through PowerShell inline text output or `Set-Content`.
- For Japanese UI text changes, use `apply_patch` or save from the editor as plain `UTF-8`.
- If a file is shown as `UTF-8 with BOM`, re-save it as `UTF-8`.
- Do not use PowerShell output as the source of truth for Japanese text appearance. PowerShell display may look garbled even when the file itself is correct.
- Use the editor as the source of truth for Japanese text confirmation.
- Use PowerShell only for mechanical checks such as command execution, row counts, `php -l`, `php artisan view:clear`, and similar non-visual verification.
- Do not judge Japanese text corruption unless the user explicitly reports that the text is garbled.

## 18) In-Repo Project Separation Rule
- Treat `resources/views/admin_v2`, `public/css/admin_v2`, and related `Admin/V2` routes/controllers/services as the admin-side project.
- Treat `resources/views/staff`, `public/css/staff`, and related `Staff` routes/controllers/services as the staff-side project.
- Even inside the same repository, `admin_v2` and `staff` must be handled as separate projects.
- Do not mix route names, shared layouts, headers, footers, CSS, or controller assumptions between `admin_v2` and `staff`.
- Do not assume a file under `admin_v2` can be reused by `staff` as-is, or vice versa.
- When working on one side, do not pull in the other side's shared parts unless the user explicitly asks for a planned integration.

## 19) DB Migration Posture Rule
- Prioritize completing the Laravel migration first. Do not expand current work into a DB engine migration unless the user explicitly asks.
- Treat SQL Server as the current runtime DB for now, but avoid increasing SQL Server lock-in in new code.
- Prefer Eloquent or Query Builder over raw SQL when feasible for new or modified code.
- Centralize DB-specific logic in services or repositories to contain future migration impact.
- In new or changed code, do not add unnecessary new `DB::connection('sqlsrv')` usage when an existing repository/service pattern can be reused.
- Do not add new `dbo.` prefixes unless required by the existing surrounding implementation.
- Keep `whereRaw()` and DB-specific functions such as `CONVERT`, `LTRIM/RTRIM`, `YEAR`, `MONTH`, and `NVARCHAR` to the minimum necessary.
- Do not add controller-level ad hoc SQL when the query belongs in a service or existing query layer.
- When touching existing SQL Server-dependent code, prefer small improvements that reduce future migration cost, but do not rewrite large working areas just for abstraction.

## 19) `zzz_` File Rule
- A file or folder prefixed with `zzz_` means:
  - it is not part of the live/current reference path
  - it is considered a confirmed cleanup candidate
  - it may be deleted later when noticed
- Use `zzz_` only for files that are already unused, not for active files under review.
- Do not treat `zzz_` as a temporary work file prefix.
- When a new source-of-truth file replaces an older file, mark the replaced old file with `zzz_` at that time rather than leaving both names active.
- Do not postpone `zzz_` marking for replaced files to a later cleanup pass.
- `zzz_` conversion must be rename-only. Never create a copied `zzz_` file while leaving the original file in place. Never leave both the original name and the `zzz_` name at the same time.

## 20) No Unrequested Link Or Menu Changes
- Do not add, remove, rename, reroute, or repurpose links, buttons, menus, tabs, quick-links, or navigation items unless the user explicitly asked for that exact change.
- Do not change a displayed menu label or link destination based on your own judgment.
- Do not replace one navigation meaning with another, such as changing `打刻一覧` to `給与管理`, unless the user explicitly requested it.
- If a page must be made visible for verification, do not alter existing user-facing navigation to do it. Use a separate temporary route or wait for explicit instruction.
- If a link or menu change seems helpful, propose it first and wait for approval. Do not implement it proactively.

## 21) No Unrequested Label/Text Changes
- Do not change existing labels, headings, captions, button text, menu text, section titles, helper text, or other user-written UI wording unless the user explicitly asked for that exact text change.
- When adding a new page or a brand-new UI block, only write the new labels needed for that new block. Do not rewrite nearby existing wording.
- If the user has manually edited wording in a file, treat that wording as locked and do not overwrite or normalize it unless explicitly asked.
- If a structural change is needed around existing text, preserve the existing text exactly as written.

## 22) Existing Rules Are Hard Stop Rules
- Every rule in this file must be treated as a hard stop rule, not as guidance or preference.
- If a planned action would violate any existing rule in this file, stop before editing and report the conflict.
- Never create your own temporary exception, “just this once” exception, or convenience-based exception to a written rule.

## 23) Temporary Script Rule
- One-time temporary scripts are allowed when they reduce breakage risk, especially for narrow mechanical edits that are hard to apply safely by hand.
- Temporary scripts must be created only under `C:\dev\tcpg_system_laravel\_tmp_codex`.
- Temporary scripts must be removed after execution.
- Do not create temporary scripts in another project folder or in `C:\tmp`.
- Do not ask the user again for ordinary `_tmp_codex` temporary scripts after the user has already approved this workflow.
- Still confirm before adding permanent files, new folders, libraries, composer changes, migrations, DB structure changes, or any external-folder access.
- Prefer direct minimal edits first, then `_tmp_codex` temporary scripts, then user confirmation only when genuinely blocked or risky.

## 24) Existing Japanese UI Must Use Minimal Direct Edits Only
- For existing Japanese UI files, edit only the agreed block or lines needed for the requested change.
- Do not rewrite the entire file when only one section is requested.
- Do not replace, normalize, or “clean up” surrounding Japanese text while touching another area.

## 25) One Turn One Purpose Rule
- Keep each edit turn to one clear purpose such as one link addition, one route addition, one layout change, or one shared-item extraction.
- Do not mix unrelated purposes in the same edit turn.
- If solving the request requires a second purpose, stop after the first and wait for the user to confirm continuation.

## 26) No Scope Expansion By Convenience
- Do not widen an edit because it seems faster, cleaner, more complete, or easier to do all at once.
- “While here”, “for consistency”, “to help”, or “to save time” are not valid reasons to expand scope.

## 27) No Edit Without Recovery Source
- If the original wording, structure, or expected state cannot be confirmed from the current file, git history, or user-provided source, do not overwrite that part.
- Never replace existing UI text with guessed text.
- If the original cannot be recovered with confidence, stop and report that limitation before editing.

## 28) `admin_v2` And `staff_portal` Frame Rule
- Treat `admin_v2` as the system-master side.
- Treat `staff_portal` as the staff side.
- These two sides must keep separate shared headers, frames, containers, and frame CSS.
- When creating a new page, always use the shared header and shared frame for that side.
- Common frame CSS for each side must live in that side's shared CSS, not be recreated per page.

## 29) New Page Folder Rule
- Within `admin_v2` or `staff_portal`, create views per page in that page's own folder.
- A new page should be organized so that its own folder clearly contains that page's `index.blade.php` and related files.
- Shared header and shared frame must still come from the side's shared frame structure and shared frame CSS.

## 30) Shared Item Coupling Rule
- If a page uses a shared item, changes to that shared item must make every page using that shared item change together in the same way.
- Do not create page-specific visual divergence inside a shared item CSS file.
- Shared item HTML and shared item CSS must represent one common source of truth for every page that uses that item.

## 31) Item CSS Naming Rule
- If a UI block is split into an item, create and maintain a separate CSS file for that item.
- The item CSS file should use the same page or item naming that matches the shared item it belongs to.
- Do not put item-only CSS into a side-wide frame CSS file such as `app-shell.css` or other shared frame CSS.

## 32) Page CSS Naming Rule
- If CSS is for a page and not for a shared item, change it in that page's own CSS file with the same page name.
- Do not change unrelated shared CSS when the request is only about one page's own CSS.

## 33) `OK` Means Proceed Rule
- After the user gives explicit `OK`, proceed with the agreed task without asking the same confirmation again.
- Do not pause again for the same scope unless a real new risk or scope change appears.

## 34) CSS Folder Rule
- Split CSS folders only by side: `public/css/admin_v2/` and `public/css/staff_portal/`.
- Do not create or keep cross-side CSS folders such as `public/css/shared/` for live page or item CSS.
- If an item is used on the staff side, its item CSS must live under `public/css/staff_portal/`.
- If an item is used on the admin side, its item CSS must live under `public/css/admin_v2/`.
- Keep CSS discoverable by folder path first, not by searching across extra shared CSS folders.

## 35) Route Ownership Rule
- Split routes by business domain, not by one-screen-per-route-file and not by tiny responsibility slices.
- Use route prefixes such as `attendance.*`, `office.*`, and `shift.*` to keep one business flow grouped together.
- Keep list, detail, edit, update, and related actions for the same business flow under the same route domain unless the user explicitly wants a different split.
- Do not reuse another business domain's route names just because the target page looks similar.

## 36) Controller Ownership Rule
- Split controllers by business domain as the default rule.
- Prefer one controller per business area such as `AttendanceController`, `OfficeController`, or `ShiftController`.
- Do not create one controller per screen by default.
- Do not split controllers into many small classes unless there is a clear difference in responsibility such as CRUD versus export/aggregation, or the file has become too large to understand.
- Keep authentication/session checks, routing decisions, and business-area ownership in the controller for that same business domain unless the user explicitly asks for a different architecture.

## 37) View Folder Ownership Rule
- A view folder should match the responsibility of its controller and route domain.
- Keep one business area's pages together under that side's folder structure, for example `resources/views/staff_portal/attendance/`, `resources/views/staff_portal/office/`, or `resources/views/staff_portal/shift/`.
- Avoid leaving view files in a folder that no longer matches the owning controller or route domain.
- A normal page should remain understandable by opening that page folder and its main `index.blade.php`, without hunting across many unrelated folders.
