<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BonusController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = (string) $request->query('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $selectedRowKey = trim((string) $request->query('row', ''));

        $monthStart = CarbonImmutable::createFromFormat('Y-m-d', $selectedMonth . '-01')->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.m_companies')
            ->select(['company_id', 'company_name'])
            ->orderBy('company_id')
            ->get()
            ->map(fn ($r) => [
                'company_id' => trim((string) ($r->company_id ?? '')),
                'company_name' => trim((string) ($r->company_name ?? '')),
            ])
            ->filter(fn ($r) => $r['company_id'] !== '')
            ->values()
            ->all();

        $staffOptions = $this->loadStaffOptions($selectedCompanyId);

        $rowQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries as pe')
            ->where('pe.is_bonus', 1)
            ->whereBetween(DB::raw('CONVERT(date, pe.supply_month)'), [$monthStart->toDateString(), $monthEnd->toDateString()]);

        if ($selectedStaffId !== '') {
            $rowQuery->where('pe.staff_code', $selectedStaffId);
        }

        $rawRows = $rowQuery
            ->orderBy('pe.staff_code')
            ->get([
                'pe.payroll_entry_id',
                'pe.staff_code',
                'pe.supply_month',
                'pe.is_edit_locked',
                'pe.bonus_amount',
                'pe.salary_total',
                'pe.payment_total',
                'pe.transfer_amount',
                'pe.raw_payload',
            ]);

        $staffMap = DB::connection('sqlsrv')
            ->table('dbo.m_staffs')
            ->select(['staff_id', 'staff_name', 'staff_division'])
            ->get()
            ->keyBy(fn ($r) => trim((string) ($r->staff_id ?? '')));

        $summaryRows = $rawRows->map(function ($r) use ($staffMap): array {
            $staffCode = trim((string) ($r->staff_code ?? ''));
            $meta = $staffMap->get($staffCode);
            $name = trim((string) ($meta->staff_name ?? ''));
            $division = trim((string) ($meta->staff_division ?? ''));
            $supplyMonth = '';
            try {
                $supplyMonth = CarbonImmutable::parse((string) $r->supply_month)->format('Y/m');
            } catch (\Throwable) {
            }

            return [
                'key' => (string) ($r->payroll_entry_id ?? ''),
                'entry_id' => (int) ($r->payroll_entry_id ?? 0),
                'staff_id' => $staffCode,
                'staff_name' => $name !== '' ? $name : '-',
                'division' => $division !== '' ? $division : '-',
                'supply_month' => $supplyMonth !== '' ? $supplyMonth : '-',
                'is_locked' => ((int) ($r->is_edit_locked ?? 0)) === 1,
            ];
        })->values()->all();

        if ($selectedRowKey === '' && !empty($summaryRows)) {
            $selectedRowKey = (string) $summaryRows[0]['key'];
        }

        $selectedEntry = null;
        if ($selectedRowKey !== '') {
            $selectedEntry = $rawRows->first(fn ($r) => (string) ($r->payroll_entry_id ?? '') === $selectedRowKey);
        }

        $selectedSummary = collect($summaryRows)->firstWhere('key', $selectedRowKey) ?? [];
        $payload = $this->decodePayload((string) ($selectedEntry->raw_payload ?? ''));

        $bonusAmount = $this->pickNumber($selectedEntry, $payload, ['bonus_amount', 'bonus_amo']);
        $adjustmentAllowance = $this->pickNumber($selectedEntry, $payload, ['adjustment_allowance']);

        $deductions = [
            'kenpo' => $this->pickNumber($selectedEntry, $payload, ['kenpo']),
            'kaigo' => $this->pickNumber($selectedEntry, $payload, ['kaigo']),
            'kounen' => $this->pickNumber($selectedEntry, $payload, ['kounen']),
            'koyou' => $this->pickNumber($selectedEntry, $payload, ['koyou']),
            'income_tax' => $this->pickNumber($selectedEntry, $payload, ['income_tax']),
            'resident_tax' => $this->pickNumber($selectedEntry, $payload, ['resident_tax']),
        ];

        $others = [
            'adjustment_year_end' => $this->pickNumber($selectedEntry, $payload, ['adjustment_year_end']),
            'adjustment_cost' => $this->pickNumber($selectedEntry, $payload, ['adjustment_cost']),
        ];

        $payTotal = $bonusAmount + $adjustmentAllowance;
        $deductionTotal = array_sum($deductions);
        $othersTotal = array_sum($others);
        $netPay = $payTotal - $deductionTotal + $othersTotal;

        $detailRows = [
            'earnings' => [
                ['label' => '賞与額', 'key' => 'bonus_amount', 'value' => $bonusAmount],
                ['label' => '調整手当', 'key' => 'adjustment_allowance', 'value' => $adjustmentAllowance],
            ],
            'deductions' => [
                ['label' => '健康保険料', 'key' => 'kenpo', 'value' => $deductions['kenpo']],
                ['label' => '介護保険料', 'key' => 'kaigo', 'value' => $deductions['kaigo']],
                ['label' => '厚生年金保険', 'key' => 'kounen', 'value' => $deductions['kounen']],
                ['label' => '雇用保険料', 'key' => 'koyou', 'value' => $deductions['koyou']],
                ['label' => '所得税', 'key' => 'income_tax', 'value' => $deductions['income_tax']],
                ['label' => '住民税', 'key' => 'resident_tax', 'value' => $deductions['resident_tax']],
            ],
            'others' => [
                ['label' => '年調過不足', 'key' => 'adjustment_year_end', 'value' => $others['adjustment_year_end']],
                ['label' => '立替清算', 'key' => 'adjustment_cost', 'value' => $others['adjustment_cost']],
            ],
            'totals' => [
                'pay_total' => $payTotal,
                'deduction_total' => $deductionTotal,
                'others_total' => $othersTotal,
                'net_pay' => $netPay,
            ],
        ];

        $seedStaffOptions = $staffOptions;

        return view('admin.bonus.index', [
            'selectedMonth' => $selectedMonth,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'selectedRowKey' => $selectedRowKey,
            'companyOptions' => $companyOptions,
            'staffOptions' => $staffOptions,
            'summaryRows' => $summaryRows,
            'selectedSummary' => $selectedSummary,
            'detailRows' => $detailRows,
            'seedStaffOptions' => $seedStaffOptions,
        ]);
    }

    public function seedBulkCreate(Request $request): RedirectResponse
    {
        $month = (string) $request->input('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        $companyId = trim((string) $request->input('company_id', ''));
        $targetStaff = collect((array) $request->input('target_staff_ids', []))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();

        $monthDate = CarbonImmutable::createFromFormat('Y-m-d', $month . '-20')->toDateString();
        $staffRows = collect($this->loadStaffOptions($companyId));
        if (!empty($targetStaff)) {
            $staffRows = $staffRows->filter(fn ($r) => in_array($r['staff_id'], $targetStaff, true))->values();
        }
        if ($staffRows->isEmpty()) {
            return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => $companyId])
                ->with('status', '対象スタッフがありません。');
        }

        $existing = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 1)
            ->whereDate('supply_month', $monthDate)
            ->pluck('staff_code')
            ->map(fn ($v) => trim((string) $v))
            ->all();
        $existingMap = array_fill_keys($existing, true);

        $created = 0;
        foreach ($staffRows as $staff) {
            $staffId = $staff['staff_id'];
            if (isset($existingMap[$staffId])) {
                continue;
            }
            DB::connection('sqlsrv_payroll')->table('dbo.m_payroll_entries')->insert([
                'staff_code' => $staffId,
                'supply_month' => $monthDate,
                'is_bonus' => 1,
                'is_edit_locked' => 0,
                'bonus_amount' => 0,
                'salary_total' => 0,
                'payment_total' => 0,
                'transfer_amount' => 0,
                'raw_payload' => json_encode(['staff_name' => $staff['staff_name']], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => $companyId])
            ->with('status', "賞与データを作成しました（{$created}件）。");
    }

    public function seedBulkDelete(Request $request): RedirectResponse
    {
        $month = (string) $request->input('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        $companyId = trim((string) $request->input('company_id', ''));
        $targetStaff = collect((array) $request->input('target_staff_ids', []))
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
        $monthDate = CarbonImmutable::createFromFormat('Y-m-d', $month . '-20')->toDateString();

        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 1)
            ->whereDate('supply_month', $monthDate)
            ->where('is_edit_locked', 0);

        if (!empty($targetStaff)) {
            $query->whereIn('staff_code', $targetStaff);
        }
        $deleted = $query->delete();

        return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => $companyId])
            ->with('status', "賞与データを削除しました（{$deleted}件）。");
    }

    public function save(Request $request): RedirectResponse
    {
        $entryId = (int) $request->input('entry_id', 0);
        $month = (string) $request->input('month', now()->format('Y-m'));
        $companyId = trim((string) $request->input('company_id', ''));
        $staffId = trim((string) $request->input('staff_id', ''));
        $row = trim((string) $request->input('row', ''));

        if ($entryId <= 0) {
            return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => $companyId])->with('status', '保存対象がありません。');
        }

        $entry = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', $entryId)
            ->where('is_bonus', 1)
            ->first();
        if (!$entry) {
            return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => $companyId])->with('status', '対象データが見つかりません。');
        }
        if (((int) ($entry->is_edit_locked ?? 0)) === 1) {
            return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => $companyId])->with('status', '確定済みのため編集できません。');
        }

        $fields = (array) $request->input('fields', []);
        $bonusAmount = $this->toNumber($fields['bonus_amount'] ?? $entry->bonus_amount ?? 0);
        $adjustmentAllowance = $this->toNumber($fields['adjustment_allowance'] ?? 0);
        $kenpo = $this->toNumber($fields['kenpo'] ?? 0);
        $kaigo = $this->toNumber($fields['kaigo'] ?? 0);
        $kounen = $this->toNumber($fields['kounen'] ?? 0);
        $koyou = $this->toNumber($fields['koyou'] ?? 0);
        $incomeTax = $this->toNumber($fields['income_tax'] ?? 0);
        $residentTax = $this->toNumber($fields['resident_tax'] ?? 0);
        $adjustmentYearEnd = $this->toNumber($fields['adjustment_year_end'] ?? 0);
        $adjustmentCost = $this->toNumber($fields['adjustment_cost'] ?? 0);

        $payTotal = $bonusAmount + $adjustmentAllowance;
        $deductionTotal = $kenpo + $kaigo + $kounen + $koyou + $incomeTax + $residentTax;
        $othersTotal = $adjustmentYearEnd + $adjustmentCost;
        $netPay = $payTotal - $deductionTotal + $othersTotal;

        $payload = $this->decodePayload((string) ($entry->raw_payload ?? ''));
        $payload = array_merge($payload, [
            'bonus_amo' => $bonusAmount,
            'adjustment_allowance' => $adjustmentAllowance,
            'kenpo' => $kenpo,
            'kaigo' => $kaigo,
            'kounen' => $kounen,
            'koyou' => $koyou,
            'income_tax' => $incomeTax,
            'resident_tax' => $residentTax,
            'adjustment_year_end' => $adjustmentYearEnd,
            'adjustment_cost' => $adjustmentCost,
            'deduction_sum' => $deductionTotal,
            'others_total' => $othersTotal,
            'supply_deduction_sum' => $netPay,
        ]);

        DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', $entryId)
            ->update([
                'bonus_amount' => $bonusAmount,
                'salary_total' => $payTotal,
                'payment_total' => $netPay,
                'transfer_amount' => $netPay,
                'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        return redirect()->route('admin.bonus.index', [
            'month' => $month,
            'company_id' => $companyId,
            'staff_id' => $staffId,
            'row' => $row !== '' ? $row : (string) $entryId,
        ])->with('status', '賞与データを保存しました。');
    }

    public function lock(Request $request): RedirectResponse
    {
        return $this->setLock($request, 1, '賞与を確定しました。');
    }

    public function unlock(Request $request): RedirectResponse
    {
        return $this->setLock($request, 0, '賞与を未確定に戻しました。');
    }

    private function setLock(Request $request, int $lockValue, string $message): RedirectResponse
    {
        $entryId = (int) $request->input('entry_id', 0);
        $month = (string) $request->input('month', now()->format('Y-m'));
        $companyId = trim((string) $request->input('company_id', ''));
        $staffId = trim((string) $request->input('staff_id', ''));
        $row = trim((string) $request->input('row', ''));

        if ($entryId > 0) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.m_payroll_entries')
                ->where('payroll_entry_id', $entryId)
                ->where('is_bonus', 1)
                ->update([
                    'is_edit_locked' => $lockValue,
                    'updated_at' => now(),
                ]);
        }

        return redirect()->route('admin.bonus.index', [
            'month' => $month,
            'company_id' => $companyId,
            'staff_id' => $staffId,
            'row' => $row,
        ])->with('status', $message);
    }

    private function loadStaffOptions(string $companyId): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.m_staffs as ms')
            ->leftJoin('dbo.m_stores as st', function ($join): void {
                $join->on('st.store_code', '=', 'ms.store_code');
            })
            ->select([
                'ms.staff_id',
                'ms.staff_name',
                'ms.staff_division',
                'st.company_id',
            ])
            ->whereNotNull('ms.staff_id');

        if ($companyId !== '') {
            $query->where('st.company_id', $companyId);
        }

        return $query
            ->orderBy('ms.staff_id')
            ->get()
            ->map(function ($r): array {
                return [
                    'staff_id' => trim((string) ($r->staff_id ?? '')),
                    'staff_name' => trim((string) ($r->staff_name ?? '')),
                    'staff_division' => trim((string) ($r->staff_division ?? '')),
                ];
            })
            ->filter(fn ($r) => $r['staff_id'] !== '' && $r['staff_division'] !== '業務委託')
            ->values()
            ->all();
    }

    private function decodePayload(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function pickNumber($entry, array $payload, array $keys): float
    {
        foreach ($keys as $k) {
            if ($entry && isset($entry->{$k})) {
                return $this->toNumber($entry->{$k});
            }
            if (array_key_exists($k, $payload)) {
                return $this->toNumber($payload[$k]);
            }
        }
        return 0.0;
    }

    private function toNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = str_replace([',', ' '], '', trim((string) $value));
        if ($s === '' || !is_numeric($s)) {
            return 0.0;
        }
        return (float) $s;
    }
}

