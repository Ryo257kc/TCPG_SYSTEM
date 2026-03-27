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

## 7) Blade Structure Rule
- Do not split page layout into many small Blade partials unless there is clear multi-page reuse or clearly separate business logic.
- Prefer keeping each page's visible structure in a single page Blade so the layout can be understood in one place.
- Use `@include` mainly for truly shared frame/style pieces, not to fragment one page's normal form/table structure.
- Split out only what is genuinely shared across pages, or what is complex enough to deserve isolated maintenance.
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

## 12) Shared App Shell And CSS Rule
- The outer page shell must be shared. If a common shell/style changes, all pages using it should change together.
- Place `global_nav` directly under `<body>`.
- Use shared shell/layout CSS for outer structure such as header, title, width, frame, and spacing.
- Do not create per-page outer layout CSS when the layout is meant to look the same.
- If a page needs a different inner composition, keep the outer shell shared and separate only the inner content structure.
- Do not reuse the class name `wrap` for inner table/list wrappers inside the page content.
- When changing shared navigation or outer frame styling, update the shared file first rather than adding page-specific copies.

## 13) CSS Ownership Rule
- Do not create page-specific CSS for UI that should look the same across pages.
- Shared button styles, font sizes, frame styles, tabs, form controls, and common spacing must live in shared CSS.
- Page-specific CSS should exist only for truly unique inner content layouts or unique data presentation needs.
- Before adding new CSS, check whether it belongs in shared UI/common frame styles instead.

## 14) CSS File Rule
- Shared styles must be created as CSS files under `public/css/admin_v2/`, not as Blade style partials.
- Use shared CSS files for common frame and common UI before adding any page-specific CSS.
- Page-specific CSS should also prefer real CSS files when possible.
- Do not add new `page_style.blade.php` files for new pages.

## 15) Page Composition Rule
- A page should be editable by reading as few files as possible.
- Prefer keeping page composition in the page's own `index.blade.php`.
- Use partials only when there is real multi-page reuse or the content block is large enough to justify separation.
- For a normal page, the target should be one page Blade and, when needed, one page CSS file.
