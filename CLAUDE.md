# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

`tcpg_system_laravel` is a Laravel 12 (PHP 8.2) rewrite of a legacy Microsoft Access system for a home-visit nursing/medical care business. It covers HR/payroll/bonus, attendance, paid leave, year-end tax adjustment, sales/accounting (journal entries, receipts/insurance billing, store daily reports), and staff self-service. The migration from Access is ongoing and several `docs/MIGRATION/*` files record the old-to-new column/table mappings — treat them as reference, not as current schema.

The application is split into two independently-run sides that share one codebase:

- **`admin_v2`** — back-office/management screens (HR, payroll, master data, reports).
- **`staff_portal`** — staff self-service screens (attendance punch, applications, mypage, office/home-visit operations).

These two sides must be treated as separate projects even though they live in the same repo (see "admin_v2 vs staff_portal separation" below).

## Commands

```bash
# Install
composer install
npm install
cp .env.example .env && php artisan key:generate

# Local dev (server + queue listener + log tail + vite, all at once)
composer run dev

# Run the full test suite
composer test
# equivalent to:
php artisan config:clear && php artisan test

# Run a single test
php artisan test --filter=TestClassName
php artisan test tests/Feature/ExampleTest.php

# Lint / format PHP (Laravel Pint)
vendor/bin/pint          # fix
vendor/bin/pint --test   # check only

# Frontend build
npm run dev     # vite dev server
npm run build    # production build

# View cache — required after Blade changes touching admin_v2, per project convention
php artisan view:clear
php artisan view:cache
```

Test suite is currently minimal (`tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php` are still framework stubs) — there is no meaningful automated coverage of business logic yet. Manual verification against `docs/rules/{domain}/` and, where noted, against the legacy Access output is the real safety net for calculation changes.

## Architecture

### Request flow

`routes/web.php` (plus `routes/admin/*.php` for larger admin domains: `attendance_v2.php`, `paid_leave_v2.php`, `payroll_v2.php`, `report_v2.php`) wires URLs to controllers under `app/Http/Controllers/Admin/V2/**` or `app/Http/Controllers/StaffPortal/**`. Controllers stay thin — they handle auth/session concerns and delegate calculation, aggregation, and persistence to `app/Services/**`. There is no repository layer; services talk to the database directly via Eloquent/Query Builder (and some raw SQL Server queries where unavoidable).

- `App\Http\Middleware\AdminAuthenticate` — gates `admin_v2` routes on `session('admin_logged_in')`.
- `App\Http\Middleware\StaffPortalAuthenticate` — gates `staff_portal` routes.
- `App\Http\Middleware\ForceUtf8Response` — forces `text/html; charset=UTF-8` on responses (this app is Japanese-text-heavy; encoding bugs are a recurring failure mode, see below).
- `app/Models` is intentionally small (`Company`, `Store`, `User`) — most domain data is read/written through services rather than Eloquent models, because much of the schema is still shaped by the Access-era SQL Server database.

### Naming conventions

Admin-side classes are consistently suffixed `V2` (`PayrollV2Controller`, `PayrollV2Service`, `AttendanceV2MonthlySummaryService`, `resources/views/admin_v2/...`), reflecting a prior `admin` (v1) structure that has been superseded. When searching for "the" implementation of something, prefer the `V2` version and treat non-`V2` admin code as legacy unless a file says otherwise.

Business domains get one controller and a cluster of single-purpose services, e.g. `app/Services/Admin/V2/Payroll/` contains ~24 services (`PayrollV2IncomeTaxService`, `PayrollV2SocialInsuranceAmountService`, `PayrollV2RecalculateService`, ...) behind one `PayrollV2Controller`. Follow this pattern: split calculation/aggregation/PDF-layout concerns into separate services under the domain folder rather than growing one controller or one god-service. Each split-out file should open with a short comment stating what it owns (e.g. "calculation only" / "PDF coordinates only") so the owner is discoverable without tracing callers.

### View structure

Views live under `resources/views/admin_v2/{master,report,work}/<page>/index.blade.php` and `resources/views/staff_portal/<domain>/`. Each page should be understandable from its folder's `index.blade.php` plus its page CSS, without hunting across many small partials. Avoid splitting a normal page into many tiny Blade partials (`header`, `filter_form`, `list`, ...); only pull out a partial for genuine multi-page reuse or a large content unit (e.g. a `tabs/` subfolder). Do not create `partials/` folders for admin_v2/staff_portal pages unless multiple pages genuinely share the file.

CSS is split strictly by side: `public/css/admin_v2/` and `public/css/staff_portal/`. There is no cross-side shared CSS folder. Shared chrome (nav, header frame, common buttons/spacing) lives in that side's shared CSS; page-specific look lives in one page CSS file named after the page (`payroll.css`, `staff.css`, ...). Never write CSS inline as a `page_style.blade.php` partial.

### admin_v2 vs staff_portal separation

Even though both sides live in this one repo, do not mix them:
- No shared route names, layouts, headers, footers, CSS, or controller assumptions between `admin_v2` and `staff_portal`.
- A view/controller/service written for one side cannot be assumed reusable by the other.
- **Calculation parity is still required**: the same underlying data must produce the same computed values on both sides. The staff side may choose to *hide* fields, but it must never compute a different result for a field it does show. New screens/APIs must call the existing canonical service (e.g. `AttendanceV2MonthlySummaryService`) rather than re-implementing the calculation in a controller or Blade file.

### Multiple database connections

`config/database.php` defines several SQL Server connections beyond the Laravel default:
- `sqlsrv` — main application database.
- `sqlsrv_payroll` — payroll-specific database (`PAYROLL_DB_*` env vars).
- `sqlsrv_dailyreport` — store daily report database (`DAILYREPORT_DB_*` env vars).

Local/dev/test default to `sqlite` (see `.env.example`, `phpunit.xml`) so the app and tests can run without a live SQL Server instance. Production runs on SQL Server, which is a holdover from the Access-era system. Per project policy: prefer Eloquent/Query Builder over raw SQL; centralize any DB-specific logic (raw `whereRaw`, `CONVERT`, `NVARCHAR`, etc.) inside services; don't introduce new `DB::connection('sqlsrv')` calls when an existing service/repository already covers that area; don't add new `dbo.` prefixes; do not expand this project into a database-engine migration unless explicitly asked — the current priority is finishing the Laravel rewrite on top of the existing SQL Server schema.

### PDF / report generation

`phpoffice/phpspreadsheet`, `setasign/fpdf`+`fpdi`, and `tecnickcom/tcpdf` are used for report/ledger/payslip output (wage ledgers, transfer lists, payslips, etc). These are considered "reports" in the rules below: they must render DB-stored values as-is, not re-derive/re-calculate figures independently of the owning service.

## Critical engineering rules (from `AGENTS.md` and `docs/rules/00_global.md`)

These are hard constraints distilled from actual incidents in this codebase, not stylistic preferences. Read `docs/rules/00_global.md` before touching any page, then the relevant `docs/rules/{domain}/` folder (e.g. `payroll_bonus/`, `attendance/`, `year_end_adjustment/`, `sales_accounting/`, `receipt/`) — especially each domain's `99_do_not_touch.md`, which lists things that must not change without explicit confirmation and documents the incident that led to the rule.

- **Never swallow schema/query errors.** Do not wrap DB/schema access in `try { ... } catch (\Throwable) { return []; }`-style fallbacks — this has previously hidden broken table/column references (and disabled a customer-facing feature) for months. A genuinely empty result set (e.g. "this company has zero master rows") is fine to return as-is; what's forbidden is masking a failed query/schema check as if it were empty data.
- **One canonical calculation per concept.** Calculation logic (payroll, bonus, attendance, sales, etc.) belongs in exactly one service. Year-dependent formula changes should branch inside that service (e.g. by `targetYear`), not be copied into a second implementation for a new report or screen.
- **Reports display saved values, they don't recompute.** Ledgers/payslips/printouts read the DB-persisted value; recomputing "live" at render time has previously caused screens to silently diverge from what was actually saved (see the `supply_deduction_sum` incident referenced in `docs/rules/00_global.md`).
- **Read request input by field name, never by array position.** Don't do `array_values($request->except([...]))`-style positional destructuring — a reordered form previously caused overtime hours and remarks to be saved into the wrong fields.
- **Validation `max:` lengths must match the actual DB column length**, especially for import paths (CSV, etc.) that don't go through normal Laravel validation — those need their own row/column/value-level checks.
- **Confirmed ("確定") data is immutable except through the confirm/unlock workflow.** Payroll-confirmed, attendance-confirmed, and year-end-locked data must not be silently recalculated or overwritten; any bulk recompute must check and skip confirmed records. Once confirmed, the staff-portal side must not offer an edit path until admin unlocks it.
- **Don't guess statutory figures.** Tax/insurance rate tables (income tax, social insurance, basic deduction, etc.) must be sourced from the current year's official government publication and cross-checked against existing confirmed data before implementing — not filled in from general knowledge.
- **Column hygiene:** don't delete possibly-used columns outright; rename to an `x_` prefix to retire them once confirmed unused in code. Columns already prefixed `old` are cleanup candidates but confirm usage first. Prefer a label change over a column rename when only the display text needs to change.
- **Dead file convention:** a `zzz_`-prefixed file/folder means "confirmed unused, cleanup candidate" — use rename-only (never leave both the original and a `zzz_` copy simultaneously).
- **Scope discipline:** one edit turn, one purpose. Don't do "while I'm here" cleanup, don't widen scope for consistency, don't touch unrelated Japanese UI text while fixing something else nearby — existing Japanese labels/wording are treated as intentionally authored and must be preserved exactly unless the change was explicitly requested.

## Stale/legacy process docs

`docs/ADMIN_GUARDRAILS.md`, `docs/WORK_RULES.md`, `.githooks/`, and the `scripts/*.ps1` helpers describe a Windows/PowerShell-based workflow (freeze files, page-scope files, a `commit-msg` hook requiring `TARGET: tcpg_system_admin_laravel`) built around an older `resources/views/admin/<page>/` (v1, non-`V2`) structure. The live code has since moved to `admin_v2`/`staff_portal`. Per `AGENTS.md`'s own stated priority order, `docs/rules/` is the current source of truth and `AGENTS.md` itself is a temporary guard file being phased out in favor of it — treat the PowerShell/hook tooling and the `admin/` (non-v2) references as historical context, not as commands to actually run in this environment, unless a task specifically concerns that legacy tooling.

`.freeze-files.txt` and `.page-scope.txt` in the repo root are per-task working files from that legacy workflow (currently scoped to payroll work) — don't treat their current contents as a durable rule, but do respect the general "freeze" concept: files a domain's `99_do_not_touch.md` calls out should not be changed without the confirmation that document asks for.
