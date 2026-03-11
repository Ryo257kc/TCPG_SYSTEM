# Allowance Freeze v1

- Scope: Admin allowance settings page only
- Route:
  - `/admin/master/allowance`
  - `/admin/master/allowance/ensure-slots`
- Active controller: `App\Http\Controllers\Admin\Master\AllowanceController`
- Frozen files are listed in `.freeze-files.txt`

## Rule

- Changes to allowance settings must be implemented only in the allowance module/files listed above.
- Do not modify payroll/attendance/staff site when fixing allowance settings.
- `t_allowance` is the current source of truth in Payroll DB.
