<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2ListSummaryService;
use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmedStateService;
use App\Services\Admin\V2\Payroll\PayrollV2AllowanceLabelService;
use App\Services\Admin\V2\Payroll\PayrollV2AttendanceReflectService;
use App\Services\Admin\V2\Payroll\PayrollV2CompanyService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateCandidatesService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateService;
use App\Services\Admin\V2\Payroll\PayrollV2DeleteService;
use App\Services\Admin\V2\Payroll\PayrollV2EmploymentInsuranceService;
use App\Services\Admin\V2\Payroll\PayrollV2IncomeTaxService;
use App\Services\Admin\V2\Payroll\PayrollV2KihonService;
use App\Services\Admin\V2\Payroll\PayrollV2MonthService;
use App\Services\Admin\V2\Payroll\PayrollV2OvertimeDeductionService;
use App\Services\Admin\V2\Payroll\PayrollV2RecalculateService;
use App\Services\Admin\V2\Payroll\PayrollV2ResidentService;
use App\Services\Admin\V2\Payroll\PayrollV2ShahoService;
use App\Services\Admin\V2\Payroll\PayrollV2StaffMasterService;
use App\Services\Admin\V2\Payroll\PayrollV2StaffService;
use App\Services\Admin\V2\Payroll\PayrollV2SummaryService;
use App\Services\Admin\V2\Payroll\PayrollV2UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollV2Controller extends Controller
{
    public function __construct(
        private readonly PayrollV2MonthService $monthService,
        private readonly PayrollV2CompanyService $companyService,
        private readonly PayrollV2CreateCandidatesService $createCandidatesService,
        private readonly PayrollV2CreateService $createService,
        private readonly PayrollV2DeleteService $deleteService,
        private readonly PayrollV2StaffService $staffService,
        private readonly PayrollV2SummaryService $summaryService,
        private readonly PayrollV2KihonService $kihonService,
        private readonly PayrollV2StaffMasterService $staffMasterService,
        private readonly PayrollV2ShahoService $shahoService,
        private readonly PayrollV2ResidentService $residentService,
        private readonly PayrollV2AllowanceLabelService $allowanceLabelService,
        private readonly PayrollV2AttendanceReflectService $attendanceReflectService,
        private readonly AttendanceV2ListSummaryService $attendanceListSummaryService,
        private readonly AttendanceV2ConfirmedStateService $confirmedStateService,
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2RecalculateService $recalculateService,
        private readonly PayrollV2EmploymentInsuranceService $employmentInsuranceService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
    ) {
    }

    public function index(Request $request): View
    {
        $pageData = $this->buildPageData($request);
        $availablePaymentDates = $pageData['availablePaymentDates'];
        $selectedPaymentDate = $pageData['selectedPaymentDate'];
        $selectedMonth = $pageData['selectedMonth'];
        $companyOptions = $pageData['companyOptions'];
        $selectedCompanyId = $pageData['selectedCompanyId'];
        $selectedStaffId = $pageData['selectedStaffId'];
        $staffRows = $pageData['staffRows'];
        $rows = $pageData['rows'];

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));
        [$attendanceYear, $attendanceMonth] = $month === 1
            ? [$year - 1, 12]
            : [$year, $month - 1];

        $attendanceSourceMap = $this->attendanceListSummaryService->summaryMap($attendanceYear, $attendanceMonth);
        $attendanceConfirmedMap = $this->confirmedStateService->mapByStaffIds(array_column($rows, 'staff_id'), $attendanceYear, $attendanceMonth);
        $rows = array_map(function (array $row) use ($attendanceSourceMap): array {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $row['attendance_source'] = $attendanceSourceMap[$staffId] ?? [];
            $referenceCalc = $this->overtimeDeductionService->referenceBases(
                (array) ($row['summary'] ?? []),
                (string) ($row['company_name'] ?? '')
            );
            $row['reference_calc'] = $referenceCalc;
            return $row;
            return $row;
        }, $rows);
        $rows = array_map(function (array $row) use ($attendanceConfirmedMap): array {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $row['summary'] = (array) ($row['summary'] ?? []);
            $row['summary']['attendance_checked'] = ((bool) ($attendanceConfirmedMap[$staffId] ?? false)) ? 1 : 0;
            return $row;
        }, $rows);

        $allowanceEntries = $this->allowanceLabelService->entries();
        $labelOverrides = $this->allowanceLabelService->labelMap();

        return view('admin_v2.payroll.index', [
            'availablePaymentDates' => $availablePaymentDates,
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'staffRows' => $staffRows,
            'rows' => $rows,
            'allowanceEntries' => $allowanceEntries,
            'labelOverrides' => $labelOverrides,
        ]);
    }

    public function transferList(Request $request): View
    {
        $pageData = $this->buildPageData($request);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $rows = (array) $pageData['rows'];
        $mayorMetaMap = $this->mayorMetaMap();

        $transferRows = [];
        foreach ($rows as $row) {
            $summary = (array) ($row['summary'] ?? []);
            $staffMaster = (array) ($row['staff_master'] ?? []);

            $bankName = trim((string) ($staffMaster['bank_name_1'] ?? ''));
            $bankBranch = trim((string) ($staffMaster['bank_branch_1'] ?? ''));
            $accountNo = trim((string) ($staffMaster['account_num'] ?? ''));

            if ($bankName === '' && trim((string) ($staffMaster['bank_name_2'] ?? '')) !== '') {
                $bankName = trim((string) ($staffMaster['bank_name_2'] ?? ''));
                $bankBranch = trim((string) ($staffMaster['bank_branch_2'] ?? ''));
                $accountNo = trim((string) ($staffMaster['account_num2'] ?? ''));
            }

            $transferAmount = $this->num($summary['supply_sum'] ?? 0)
                - $this->num($summary['deduction_sum'] ?? 0)
                - $this->num($summary['transfer_balance'] ?? 0);

            $transferRows[] = [
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'division' => trim((string) ($row['division'] ?? '')),
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
                'staff_name_furi' => trim((string) ($staffMaster['staff_name_furi'] ?? '')),
                'bank_name' => $bankName,
                'bank_branch' => $bankBranch,
                'account_no' => $accountNo,
                'transfer_amount' => $transferAmount,
                'city' => $this->resolveMunicipalityLabel(
                    trim((string) ($staffMaster['submission'] ?? '')),
                    trim((string) ($staffMaster['city'] ?? '')),
                    $mayorMetaMap
                ),
                'specified_num' => $this->resolveSpecifiedNum(
                    trim((string) ($staffMaster['submission'] ?? '')),
                    $mayorMetaMap
                ),
                'submission' => trim((string) ($staffMaster['submission'] ?? '')),
                'resident_tax' => $this->num($summary['resident_tax'] ?? 0),
                'taxation_sum' => $this->num($summary['taxation_sum'] ?? 0),
                'income_tax' => $this->num($summary['income_tax'] ?? 0),
                'transfer_purpose' => trim((string) ($staffMaster['transfer_purpose'] ?? '')),
            ];
        }

        usort($transferRows, static function (array $a, array $b): int {
            return [$a['company_name'], $a['transfer_purpose'], $a['bank_name'], $a['bank_branch'], $a['staff_name_furi'], $a['staff_name']]
                <=> [$b['company_name'], $b['transfer_purpose'], $b['bank_name'], $b['bank_branch'], $b['staff_name_furi'], $b['staff_name']];
        });

        $groupedCompanies = [];
        $grandTransfer = 0.0;
        $grandResidentTax = 0.0;
        $grandTaxation = 0.0;
        $grandIncomeTax = 0.0;

        foreach ($transferRows as $row) {
            $companyKey = $row['company_name'] !== '' ? $row['company_name'] : '未設定';
            $bankKey = $row['bank_name'] !== '' ? $row['bank_name'] : '未設定';

            if (!isset($groupedCompanies[$companyKey])) {
                $groupedCompanies[$companyKey] = [
                    'company_name' => $companyKey,
                    'groups' => [],
                    'city_totals' => [],
                    'transfer_total' => 0.0,
                    'resident_tax_total' => 0.0,
                    'taxation_total' => 0.0,
                    'income_tax_total' => 0.0,
                    'row_count' => 0,
                    'non_outsource_count' => 0,
                ];
            }

            if (!isset($groupedCompanies[$companyKey]['groups'][$bankKey])) {
                $groupedCompanies[$companyKey]['groups'][$bankKey] = [
                    'bank_name' => $bankKey,
                    'rows' => [],
                    'transfer_total' => 0.0,
                    'resident_tax_total' => 0.0,
                    'taxation_total' => 0.0,
                    'income_tax_total' => 0.0,
                ];
            }

            $groupedCompanies[$companyKey]['groups'][$bankKey]['rows'][] = $row;
            $groupedCompanies[$companyKey]['groups'][$bankKey]['transfer_total'] += $row['transfer_amount'];
            $groupedCompanies[$companyKey]['groups'][$bankKey]['resident_tax_total'] += $row['resident_tax'];
            $groupedCompanies[$companyKey]['groups'][$bankKey]['taxation_total'] += $row['taxation_sum'];
            $groupedCompanies[$companyKey]['groups'][$bankKey]['income_tax_total'] += $row['income_tax'];
            $cityKey = trim((string) ($row['city'] ?? ''));
            if ($cityKey !== '' && $cityKey !== '-') {
                if (!isset($groupedCompanies[$companyKey]['city_totals'][$cityKey])) {
                    $groupedCompanies[$companyKey]['city_totals'][$cityKey] = [
                        'city' => $cityKey,
                        'specified_num' => trim((string) ($row['specified_num'] ?? '')),
                        'row_count' => 0,
                        'resident_tax_total' => 0.0,
                    ];
                }
                $groupedCompanies[$companyKey]['city_totals'][$cityKey]['row_count']++;
                $groupedCompanies[$companyKey]['city_totals'][$cityKey]['resident_tax_total'] += $row['resident_tax'];
            }
            $groupedCompanies[$companyKey]['transfer_total'] += $row['transfer_amount'];
            $groupedCompanies[$companyKey]['resident_tax_total'] += $row['resident_tax'];
            $groupedCompanies[$companyKey]['taxation_total'] += $row['taxation_sum'];
            $groupedCompanies[$companyKey]['income_tax_total'] += $row['income_tax'];
            $groupedCompanies[$companyKey]['row_count']++;
            if ($row['division'] !== '業務委託') {
                $groupedCompanies[$companyKey]['non_outsource_count']++;
            }
            $grandTransfer += $row['transfer_amount'];
            $grandResidentTax += $row['resident_tax'];
            $grandTaxation += $row['taxation_sum'];
            $grandIncomeTax += $row['income_tax'];
        }

        return view('admin_v2.payroll.transfer_list_clean', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedCompanyId' => $selectedCompanyId,
            'groupedCompanies' => array_map(static function (array $company): array {
                $company['groups'] = array_values($company['groups']);
                $company['city_totals'] = array_values($company['city_totals']);
                return $company;
            }, array_values($groupedCompanies)),
            'grandTransfer' => $grandTransfer,
            'grandResidentTax' => $grandResidentTax,
            'grandTaxation' => $grandTaxation,
            'grandIncomeTax' => $grandIncomeTax,
            'rowCount' => count($transferRows),
            'companyLabel' => $this->resolveCompanyLabel($rows),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'values' => ['required', 'array'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->updateService->save((string) $v['staff_id'], $year, $month, (array) $v['values'], (string) ($v['company_id'] ?? ''));

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function recalculate(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->recalculateService->recalculate(
            (string) $v['staff_id'],
            $year,
            $month,
            (string) ($v['company_id'] ?? '')
        );

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function reflectAttendance(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->attendanceReflectService->reflect((string) $v['staff_id'], $year, $month);
        $updated += (int) $this->employmentInsuranceService->recalculate((string) $v['staff_id'], $year, $month);

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function calcKoyou(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->employmentInsuranceService->recalculate((string) $v['staff_id'], $year, $month);

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function calcOvertimeDeduction(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $companyId = (string) ($v['company_id'] ?? '');

        $updated = 0;
        $updated += (int) $this->overtimeDeductionService->recalculate(
            (string) $v['staff_id'],
            $year,
            $month,
            $companyId
        );
        $updated += (int) $this->employmentInsuranceService->recalculate((string) $v['staff_id'], $year, $month);
        $updated += (int) $this->incomeTaxService->recalculate((string) $v['staff_id'], $year, $month);
        $updated += (int) $this->updateService->refreshTotals((string) $v['staff_id'], $year, $month, $companyId);

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function calcIncomeTax(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $result = $this->incomeTaxService->recalculateWithTrace((string) $v['staff_id'], $year, $month);

        return response()->json([
            'ok' => true,
            'updated' => (int) ($result['updated'] ?? 0),
            'trace' => $result['trace'] ?? [],
        ]);
    }

    public function createCandidates(Request $request): JsonResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $payload = $this->createCandidatesService->payload(
            $year,
            $month,
            (string) ($v['company_id'] ?? ''),
            (string) ($v['payment_date'] ?? '')
        );

        return response()->json([
            'ok' => true,
            'candidates' => $payload['candidates'],
            'suggested_payment_date' => $payload['suggested_payment_date'],
            'payment_date_options' => $payload['payment_date_options'],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['required', 'string', 'max:20'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $result = $this->createService->create(
            $year,
            $month,
            array_map('strval', (array) $v['staff_ids']),
            (string) ($v['company_id'] ?? ''),
            (string) ($v['payment_date'] ?? '')
        );

        if ($result['created'] === 0 && $result['skipped'] === 0) {
            return response()->json([
                'ok' => false,
                'message' => '対象者がありません。表示条件を確認してください。',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'created_ids' => $result['created_ids'],
            'skipped_ids' => $result['skipped_ids'],
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $v = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->deleteService->delete(
            array_map('strval', (array) $v['staff_ids']),
            (string) $v['payment_date']
        );

        if ($result['deleted'] === 0 && $result['skipped'] === 0) {
            return response()->json([
                'ok' => false,
                'message' => '対象者がありません。表示条件を確認してください。',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'deleted' => $result['deleted'],
            'skipped' => $result['skipped'],
            'deleted_ids' => $result['deleted_ids'],
            'skipped_ids' => $result['skipped_ids'],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'checked' => ['required', 'boolean'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $checked = ((int) $v['checked']) === 1;
        $attendanceChecked = $this->updateService->isAttendanceChecked((string) $v['staff_id'], $year, $month);

        if ($checked && !$attendanceChecked) {
            return response()->json([
                'ok' => false,
                'message' => '勤怠未確定のため、給与を確定できません。',
            ], 422);
        }

        $updated = $this->updateService->setPayrollConfirmed((string) $v['staff_id'], $year, $month, $checked);

        return response()->json([
            'ok' => true,
            'updated' => (int) $updated,
            'checked' => $checked,
        ]);
    }

    /** @return array<string, mixed> */
    private function buildPageData(Request $request): array
    {
        $availablePaymentDates = $this->monthService->availablePaymentDates();
        $selectedPaymentDate = $this->monthService->normalizePaymentDate((string) $request->query('payment_date', ''), $availablePaymentDates);
        $selectedMonth = $this->monthService->monthFromPaymentDate($selectedPaymentDate);

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));
        $companyOptions = $this->companyService->companies();
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));

        if ($selectedCompanyId !== '' && !in_array($selectedCompanyId, $companyOptions, true)) {
            $selectedCompanyId = '';
        }

        $staffRows = $this->staffService->staffs($selectedCompanyId);
        $summaryMap = $this->summaryService->summaryMapByPaymentDate($selectedPaymentDate);
        $previousSummaryMap = $this->summaryService->previousSummaryMapByPaymentDate($selectedPaymentDate);
        $kihonMap = $this->kihonService->map($year, $month);
        $staffMasterMap = $this->staffMasterService->map();
        $shahoMap = $this->shahoService->map($year, $month);
        $residentMap = $this->residentService->map($year, $month);

        $staffRows = array_values(array_filter(
            $staffRows,
            static fn (array $staff): bool => isset($summaryMap[$staff['staff_id']])
        ));

        if ($selectedStaffId !== '' && !in_array($selectedStaffId, array_column($staffRows, 'staff_id'), true)) {
            $selectedStaffId = '';
        }

        $rows = $this->summaryService->mergeRows(
            $staffRows,
            $summaryMap,
            $previousSummaryMap,
            $kihonMap,
            $staffMasterMap,
            $shahoMap,
            $residentMap,
            ''
        );

        return [
            'availablePaymentDates' => $availablePaymentDates,
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'staffRows' => $staffRows,
            'rows' => $rows,
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    private function resolveCompanyLabel(array $rows): string
    {
        $companies = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['company_name'] ?? '')),
            $rows
        ), static fn (string $value): bool => $value !== '')));

        if ($companies === []) {
            return '';
        }

        return count($companies) === 1 ? $companies[0] : implode(' / ', $companies);
    }

    private function num(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /** @return array<string, array{mayor:string,specified_num:string}> */
    private function mayorMetaMap(): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_mayor')
            ->select(['mayor_no', 'mayor', 'specified_num'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $mayorName = trim((string) ($row->mayor ?? ''));
            $mayorNo = $this->normalizeMunicipalityKey((string) ($row->mayor_no ?? ''));
            if ($mayorName === '' || $mayorNo === '') {
                continue;
            }

            if (!isset($map[$mayorNo])) {
                $map[$mayorNo] = [
                    'mayor' => $mayorName,
                    'specified_num' => trim((string) ($row->specified_num ?? '')),
                ];
            }
        }

        return $map;
    }

    /** @param array<string, array{mayor:string,specified_num:string}> $mayorMetaMap */
    private function resolveMunicipalityLabel(string $submission, string $city, array $mayorMetaMap): string
    {
        foreach ([$submission, $city] as $candidate) {
            $key = trim($candidate);
            if ($key === '') {
                continue;
            }

            $normalized = $this->normalizeMunicipalityKey($key);
            if ($normalized !== '' && isset($mayorMetaMap[$normalized])) {
                return $mayorMetaMap[$normalized]['mayor'];
            }

            if (preg_match('/[ぁ-んァ-ヶ一-龠]/u', $key) === 1) {
                return $key;
            }
        }

        return '-';
    }

    /** @param array<string, array{mayor:string,specified_num:string}> $mayorMetaMap */
    private function resolveSpecifiedNum(string $submission, array $mayorMetaMap): string
    {
        $normalized = $this->normalizeMunicipalityKey($submission);
        if ($normalized === '' || !isset($mayorMetaMap[$normalized])) {
            return '';
        }

        return $mayorMetaMap[$normalized]['specified_num'];
    }

    private function normalizeMunicipalityKey(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (is_numeric($trimmed)) {
            $normalized = ltrim((string) ((int) $trimmed), '0');
            return $normalized === '' ? '0' : $normalized;
        }

        return $trimmed;
    }
}
