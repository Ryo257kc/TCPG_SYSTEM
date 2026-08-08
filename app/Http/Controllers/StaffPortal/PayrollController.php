<?php

namespace App\Http\Controllers\StaffPortal;

use Carbon\Carbon;
use DateTimeInterface;
use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Payroll\PayrollV2SummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PayrollController extends Controller
{
    private const PAYROLL_VISIBLE_FROM = '2022-07-01';

    public function __construct(private readonly PayrollV2SummaryService $summaryService)
    {
    }

    public function payslip(Request $request): RedirectResponse|View
    {
        return $this->renderPayrollPage($request, false);
    }

    public function bonus(Request $request): RedirectResponse|View
    {
        return $this->renderPayrollPage($request, true);
    }

    // 給与明細表示
    private function renderPayrollPage(Request $request, bool $isBonus): RedirectResponse|View
    {
        $sessionStaffId = (string) $request->session()->get('staff_id', '');
        if ($sessionStaffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください。');
        }

        $staffRow = $this->getStaffRow($sessionStaffId);
        $isPayrollManager = $this->resolveIsPayrollManager($staffRow);
        $targetStaffId = $this->resolveTargetStaffId($request, $sessionStaffId, $isPayrollManager);

        $connection = DB::connection('sqlsrv_payroll');
        $staffOptions = $isPayrollManager ? $this->getStaffOptions() : [];
        $visibleFrom = $this->payrollVisibleFromDate();
        $today = now('Asia/Tokyo')->toDateString();

        $availableMonths = $this->buildPayrollBaseQuery($connection, $isBonus, $targetStaffId)
            ->where('edit_lock', 1)
            ->where('supply_month', '>=', $visibleFrom)
            ->where('supply_month', '<=', $today)
            ->orderBy('supply_month', 'desc')
            ->pluck('supply_month')
            ->map(function ($v) {
                if ($v instanceof \DateTimeInterface) {
                    return $v->format('Y-m');
                }

                return substr(trim((string) $v), 0, 7);
            })
            ->unique()
            ->values()
            ->all();

        $selectedMonth = $this->resolveMonth((string) $request->input('month', ''), $availableMonths[0] ?? now('Asia/Tokyo')->format('Y-m'));
        [$year, $monthNum] = $this->splitMonth($selectedMonth);

        $rawRows = $this->buildPayrollBaseQuery($connection, $isBonus, $targetStaffId)
            ->where('edit_lock', 1)
            ->where('supply_month', '>=', $visibleFrom)
            ->where('supply_month', '<=', $today)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$monthNum])
            ->orderBy('supply_month', 'desc')
            ->get()
            ->map(fn($r) => $this->normalizePayrollRowForDisplay((array) $r))
            ->values();

        $rows = $rawRows->map(fn(array $row, int $index) => $this->buildDisplayRow($row, $index))->values();
        $selectedRowIndex = $this->resolveSelectedRowIndex((string) $request->query('row', '0'), $rows->count());
        $selectedRow = $rows->get($selectedRowIndex);

        $targetStaffRow = $targetStaffId === $sessionStaffId
            ? $staffRow
            : $this->getStaffRow($targetStaffId);
        $organization = $this->resolveStaffOrganization($targetStaffRow);
        $allowanceLabels = $this->resolveAllowanceLabels($organization['company_code'] ?? null);
        $allowanceAmountKeysBySlot = $this->resolveAllowanceAmountKeysBySlot($organization['company_code'] ?? null);
        $allowanceSlotOrder = $this->resolveAllowanceSlotOrder($organization['company_code'] ?? null);
        $allowanceDefinitionsOrdered = $this->resolveAllowanceDefinitionsOrdered($organization['company_code'] ?? null);

        return view('staff_portal.payroll.list', [
            'pageTitle' => $isBonus ? '賞与明細' : '給与明細',
            'isBonus' => $isBonus,
            'displayName' => $this->resolveDisplayName($staffRow, $sessionStaffId),
            'isAdmin' => $this->resolveIsAdmin($staffRow),
            'isPayrollManager' => $isPayrollManager,
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $availableMonths,
            'staffOptions' => $staffOptions,
            'rawRows' => $rawRows->all(),
            'rows' => $rows->all(),
            'selectedRow' => $selectedRow,
            'selectedRowIndex' => $selectedRowIndex,
            'targetStaffId' => $targetStaffId,
            'targetStaffName' => (string) ($targetStaffRow['staff_name'] ?? $targetStaffId),
            'targetTaxCategory' => (string) ($targetStaffRow['tax_amount'] ?? ''),
            'companyName' => $organization['company_name'],
            'companyCode' => $organization['company_code'],
            'storeName' => $organization['store_name'],
            'allowanceLabels' => $allowanceLabels,
            'allowanceAmountKeysBySlot' => $allowanceAmountKeysBySlot,
            'allowanceSlotOrder' => $allowanceSlotOrder,
            'allowanceDefinitionsOrdered' => $allowanceDefinitionsOrdered,
        ]);
    }

    private function getStaffRow(string $staffId): ?array
    {
        if ($this->useMxStaffTable()) {
            $row = DB::connection('sqlsrv')
                ->table('dbo.mx_staffs')
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
                ->first();

            if ($row !== null) {
                return [
                    'staff_id' => (string) ($row->staff_id ?? ''),
                    'staff_name' => (string) ($row->staff_name ?? ''),
                    'store_code' => (string) ($row->section ?? ''),
                    'tax_category' => (string) ($row->tax_category ?? ''),
                    'tax_amount' => (string) ($row->tax_amount ?? ''),
                    'is_store_manager' => (int) ($row->is_store_management_user ?? 0),
                    'is_admin' => (int) ($row->is_store_management_user ?? 0),
                ];
            }
        }

        return null;
    }

    private function getStaffOptions(): array
    {
        if ($this->useMxStaffTable()) {
            return DB::connection('sqlsrv')
                ->table('dbo.mx_staffs as st')
                ->whereNotNull('st.staff_id')
                ->whereRaw("LTRIM(RTRIM(st.staff_id)) <> ''")
                ->orderByRaw('LTRIM(RTRIM(st.staff_id)) asc')
                ->get([
                    DB::raw('LTRIM(RTRIM(st.staff_id)) as staff_id'),
                    'st.staff_name',
                ])
                ->map(fn($r) => [
                    'staff_id' => (string) ($r->staff_id ?? ''),
                    'staff_name' => (string) ($r->staff_name ?? ''),
                ])
                ->filter(fn($r) => $r['staff_id'] !== '')
                ->values()
                ->all();
        }

        return [];
    }

    private function useMxStaffTable(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::connection('sqlsrv')->hasTable('mx_staffs')
                || Schema::connection('sqlsrv')->hasTable('dbo.mx_staffs');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }

    private function resolveTargetStaffId(Request $request, string $sessionStaffId, bool $isPayrollManager): string
    {
        if (!$isPayrollManager) {
            return $sessionStaffId;
        }

        $queryStaffId = trim((string) $request->query('staff_id', ''));

        return $queryStaffId !== '' ? $queryStaffId : $sessionStaffId;
    }

    private function resolveDisplayName(?array $staffRow, string $fallback): string
    {
        $name = (string) ($staffRow['staff_name'] ?? '');

        return $name !== '' ? $name : $fallback;
    }

    private function resolveIsAdmin(?array $staffRow): bool
    {
        return ((int) ($staffRow['is_admin'] ?? 0)) === 1;
    }

    private function resolveIsPayrollManager(?array $staffRow): bool
    {
        return ((int) ($staffRow['is_store_manager'] ?? 0)) === 1;
    }

    private function resolveMonth(?string $month, ?string $fallback = null): string
    {
        $m = trim((string) ($month ?? ''));
        if ($m !== '' && preg_match('/^\d{4}-\d{2}$/', $m)) {
            return $m;
        }

        $f = (string) ($fallback ?? now('Asia/Tokyo')->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $f) ? $f : now('Asia/Tokyo')->format('Y-m');
    }

    private function splitMonth(string $month): array
    {
        return [(int) substr($month, 0, 4), (int) substr($month, 5, 2)];
    }

    private function resolveSelectedRowIndex(string $rowIndex, int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $idx = ctype_digit($rowIndex) ? (int) $rowIndex : 0;

        return max(0, min($idx, $count - 1));
    }

    private function buildDisplayRow(array $row, int $index): array
    {
        $summaryAmount = $this->resolveSummaryAmount($row);
        $summaryDate = $this->resolveSummaryDate($row);
        $summaryTitle = $summaryDate !== '' ? ($summaryDate . ' 支給分') : ('明細 #' . ($index + 1));

        $details = [];
        foreach ($row as $key => $value) {
            $details[] = [
                'label' => (string) $key,
                'value' => $this->formatCellValue($value, (string) $key),
            ];
        }

        return [
            'index' => $index,
            'summary_title' => $summaryTitle,
            'summary_amount' => $summaryAmount,
            'details' => $details,
        ];
    }

    private function resolveSummaryDate(array $row): string
    {
        if (isset($row['supply_month']) && $row['supply_month'] !== null) {
            try {
                return Carbon::parse($row['supply_month'])->format('Y/m/d');
            } catch (\Throwable) {
                return (string) $row['supply_month'];
            }
        }

        return '';
    }

    private function resolveSummaryAmount(array $row): ?string
    {
        $candidates = [
            'transfer_amount',
            'payment_total',
            'bonus_amount',
            'transfer_amo',
            'sikyu_total',
            'salary_total',
            'pay_total',
            'bonus_amo',
            'basic_salary',
        ];

        foreach ($candidates as $key) {
            if (array_key_exists($key, $row) && is_numeric($row[$key])) {
                return number_format((float) $row[$key]);
            }
        }

        return null;
    }

    private function formatCellValue(mixed $value, string $key): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('Y/m/d');
        }

        if (is_numeric($value)) {
            $number = (float) $value;
            $isRateLike = str_contains(strtolower($key), 'rate') || str_contains($key, '率');

            if ($isRateLike && abs($number) <= 1) {
                return (string) $number;
            }

            if (floor($number) === $number) {
                return number_format((int) $number);
            }

            return number_format($number, 2);
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $text) === 1) {
            try {
                return Carbon::parse($text)->format('Y/m/d');
            } catch (\Throwable) {
                return $text;
            }
        }

        return $text;
    }

    private function normalizePayrollRowForDisplay(array $row): array
    {
        unset($row['raw_payload']);
        $row['transfer_amount'] = $this->summaryService->transferAmount($row);

        return $row;
    }

    private function buildPayrollBaseQuery($connection, bool $isBonus, string $targetStaffId)
    {
        if ($this->useMxPayrollTable()) {
            return $connection->table('dbo.mx_kyuyo_shou')
                ->where('bonus', $isBonus ? 1 : 0)
                ->where('edit_lock', 1)
                ->whereRaw('LTRIM(RTRIM(kyuyo_staff_id)) = ?', [$targetStaffId]);
        }

        return $connection->table('dbo.t_kyuyo_shou')
            ->where('bonus', $isBonus ? 1 : 0)
            ->where('edit_lock', 1)
            ->where('kyuyo_staff_id', $targetStaffId);
    }

    private function useMxPayrollTable(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::connection('sqlsrv_payroll')->hasTable('mx_kyuyo_shou')
                || Schema::connection('sqlsrv_payroll')->hasTable('dbo.mx_kyuyo_shou');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }

    private function resolveStaffOrganization(?array $staffRow): array
    {
        $storeCode = trim((string) ($staffRow['store_code'] ?? ''));
        if ($storeCode === '') {
            return ['company_name' => '-', 'store_name' => '-', 'company_code' => ''];
        }

        if ($this->useMxStoreTable()) {
            $row = DB::connection('sqlsrv')
                ->table('dbo.mx_stores as st')
                ->leftJoin('dbo.mx_companies as c', 'st.company_id', '=', 'c.company_id')
                ->whereRaw('LTRIM(RTRIM(st.store_code)) = ?', [$storeCode])
                ->first();

            if ($row !== null) {
                $companyName = trim((string) ($row->company_name ?? ''));
                $companyCode = trim((string) ($row->company_id ?? ''));

                return [
                    'company_name' => $companyName !== '' ? $companyName : '-',
                    'store_name' => trim((string) ($row->store_name ?? '')) !== '' ? (string) $row->store_name : $storeCode,
                    'company_code' => $companyCode,
                ];
            }
        }

        return ['company_name' => '-', 'store_name' => $storeCode, 'company_code' => ''];
    }

    private function useMxStoreTable(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::connection('sqlsrv')->hasTable('mx_stores')
                || Schema::connection('sqlsrv')->hasTable('dbo.mx_stores');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }

    private function payrollVisibleFromDate(): string
    {
        return self::PAYROLL_VISIBLE_FROM;
    }

    private function resolveAllowanceLabels(?string $companyCode): array
    {
        $companyCode = trim((string) $companyCode);
        if ($companyCode === '') {
            return [];
        }

        $defs = $this->resolveAllowanceDefinitionsBySlot($companyCode);
        if ($defs === []) {
            return [];
        }

        $labels = [];
        foreach ($defs as $slotNo => $def) {
            $name = trim((string) ($def['allowance_name'] ?? ''));
            if ($name !== '') {
                $labels[(int) $slotNo] = $name;
            }
        }

        return $labels;
    }

    private function resolveAllowanceAmountKeysBySlot(?string $companyCode): array
    {
        $companyCode = trim((string) $companyCode);
        if ($companyCode === '') {
            return [];
        }

        $defs = $this->resolveAllowanceDefinitionsBySlot($companyCode);
        if ($defs === []) {
            return [];
        }

        $result = [];
        foreach ($defs as $slotNo => $def) {
            $slotNo = (int) $slotNo;
            if ($slotNo <= 0 || $slotNo > 17) {
                continue;
            }

            $amountKey = trim((string) ($def['amount_column_key'] ?? ''));
            $result[$slotNo] = $amountKey !== '' ? $amountKey : ('allowance_amo_' . $slotNo);
        }

        return $result;
    }

    private function resolveAllowanceSlotOrder(?string $companyCode): array
    {
        $companyCode = trim((string) $companyCode);
        if ($companyCode === '') {
            return [];
        }

        $defs = $this->resolveAllowanceDefinitionsBySlot($companyCode);
        if ($defs === []) {
            return [];
        }

        $slots = [];
        foreach (array_keys($defs) as $slotNo) {
            $slotNo = (int) $slotNo;
            if ($slotNo >= 1 && $slotNo <= 17) {
                $slots[] = $slotNo;
            }
        }

        return $slots;
    }

    private function resolveAllowanceDefinitionsBySlot(string $companyCode): array
    {
        static $cache = [];

        $companyCode = trim($companyCode);
        if ($companyCode === '') {
            return [];
        }

        if (array_key_exists($companyCode, $cache)) {
            return $cache[$companyCode];
        }

        try {
            $hasSlotNo = $this->payrollAllowanceHasColumn('slot_no');
            $hasAmountKey = $this->payrollAllowanceHasColumn('amount_column_key');
            $hasDisplayOrder = $this->payrollAllowanceHasColumn('display_order');

            $rows = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$companyCode])
                ->orderByRaw(($hasDisplayOrder ? 'ISNULL(display_order, 9999)' : '9999') . ' asc')
                ->orderByRaw(($hasSlotNo ? 'ISNULL(slot_no, 9999)' : '9999') . ' asc')
                ->orderBy('allowance_no')
                ->get([
                    'allowance_no',
                    'allowance_name',
                    DB::raw($hasSlotNo ? 'slot_no' : 'NULL as slot_no'),
                    DB::raw($hasAmountKey ? 'amount_column_key' : 'NULL as amount_column_key'),
                ]);

            $defs = [];
            $nextFallbackSlot = 1;
            foreach ($rows as $row) {
                $slotNo = (int) ($row->slot_no ?? 0);
                if ($slotNo <= 0 || $slotNo > 17) {
                    while ($nextFallbackSlot <= 17 && array_key_exists($nextFallbackSlot, $defs)) {
                        $nextFallbackSlot++;
                    }
                    if ($nextFallbackSlot > 17) {
                        continue;
                    }
                    $slotNo = $nextFallbackSlot;
                }

                $defs[$slotNo] = [
                    'allowance_no' => (int) ($row->allowance_no ?? 0),
                    'allowance_name' => (string) ($row->allowance_name ?? ''),
                    'amount_column_key' => (string) ($row->amount_column_key ?? ''),
                ];
            }

            $cache[$companyCode] = $defs;
        } catch (\Throwable) {
            $cache[$companyCode] = [];
        }

        return $cache[$companyCode];
    }

    private function resolveAllowanceDefinitionsOrdered(?string $companyCode): array
    {
        $companyCode = trim((string) $companyCode);
        if ($companyCode === '') {
            return [];
        }

        try {
            $hasSlotNo = $this->payrollAllowanceHasColumn('slot_no');
            $hasAmountKey = $this->payrollAllowanceHasColumn('amount_column_key');
            $hasDisplayOrder = $this->payrollAllowanceHasColumn('display_order');

            $rows = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$companyCode])
                ->orderByRaw(($hasDisplayOrder ? 'ISNULL(display_order, 9999)' : '9999') . ' asc')
                ->orderBy('allowance_no')
                ->get([
                    'allowance_no',
                    'allowance_name',
                    DB::raw($hasSlotNo ? 'slot_no' : 'NULL as slot_no'),
                    DB::raw($hasAmountKey ? 'amount_column_key' : 'NULL as amount_column_key'),
                    DB::raw($hasDisplayOrder ? 'display_order' : 'NULL as display_order'),
                ]);

            $defs = [];
            foreach ($rows as $row) {
                $key = trim((string) ($row->amount_column_key ?? ''));
                if ($key === '') {
                    $slotNo = (int) ($row->slot_no ?? 0);
                    if ($slotNo >= 1 && $slotNo <= 17) {
                        $key = 'allowance_amo_' . $slotNo;
                    } else {
                        continue;
                    }
                }

                $defs[] = [
                    'allowance_no' => (int) ($row->allowance_no ?? 0),
                    'allowance_name' => trim((string) ($row->allowance_name ?? '')),
                    'amount_column_key' => $key,
                    'display_order' => (int) ($row->display_order ?? 9999),
                ];
            }

            return $defs;
        } catch (\Throwable) {
            return [];
        }
    }

    private function payrollAllowanceHasColumn(string $column): bool
    {
        static $cache = [];

        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        try {
            $cache[$column] = Schema::connection('sqlsrv_payroll')->hasColumn('t_allowance', $column)
                || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.t_allowance', $column);
        } catch (\Throwable) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}
