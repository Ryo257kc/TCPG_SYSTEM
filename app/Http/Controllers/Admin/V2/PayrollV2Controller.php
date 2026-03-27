<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2ListSummaryService;
use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmedStateService;
use App\Services\Admin\V2\Payroll\PayrollV2AllowanceLabelService;
use App\Services\Admin\V2\Payroll\PayrollV2AttendanceReflectService;
use App\Services\Admin\V2\Payroll\PayrollV2BonusIncomeTaxCalcService;
use App\Services\Admin\V2\Payroll\PayrollV2BonusSocialInsuranceService;
use App\Services\Admin\V2\Payroll\PayrollV2CompanyService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateCandidatesService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateService;
use App\Services\Admin\V2\Payroll\PayrollV2DeleteService;
use App\Services\Admin\V2\Payroll\PayrollV2EmploymentInsuranceService;
use App\Services\Admin\V2\Payroll\PayrollV2FuyoService;
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
        private readonly PayrollV2BonusIncomeTaxCalcService $bonusIncomeTaxCalcService,
        private readonly PayrollV2BonusSocialInsuranceService $bonusSocialInsuranceService,
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
        private readonly PayrollV2FuyoService $fuyoService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
    ) {
    }

    public function index(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $availablePaymentDates = $pageData['availablePaymentDates'];
        $selectedPaymentDate = $pageData['selectedPaymentDate'];
        $selectedMonth = $pageData['selectedMonth'];
        $companyOptions = $pageData['companyOptions'];
        $selectedCompanyId = $pageData['selectedCompanyId'];
        $selectedStaffId = $pageData['selectedStaffId'];
        $staffRows = $pageData['staffRows'];
        $rows = $pageData['rows'];

        $allowanceEntries = $this->allowanceLabelService->entries();
        $labelOverrides = $this->allowanceLabelService->labelMap();

        return view('admin_v2.work.payroll.index', [
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

    public function bonusIndex(Request $request): View
    {
        $pageData = $this->buildPageData($request, true);
        $availablePaymentDates = $pageData['availablePaymentDates'];
        $selectedPaymentDate = $pageData['selectedPaymentDate'];
        $selectedMonth = $pageData['selectedMonth'];
        $companyOptions = $pageData['companyOptions'];
        $selectedCompanyId = $pageData['selectedCompanyId'];
        $selectedStaffId = $pageData['selectedStaffId'];
        $staffRows = $pageData['staffRows'];
        $rows = (array) $pageData['rows'];

        return view('admin_v2.work.bonus.index', [
            'availablePaymentDates' => $availablePaymentDates,
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'staffRows' => $staffRows,
            'rows' => $rows,
        ]);
    }

    public function transferList(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $rows = (array) $pageData['rows'];
        $mayorMetaMap = $this->mayorMetaMap();

        $transferRows = [];
        foreach ($rows as $row) {
            $summary = (array) ($row['summary'] ?? []);
            $kihon = (array) ($row['kihon'] ?? []);
            $shaho = (array) ($row['shaho'] ?? []);
            $kihon = (array) ($row['kihon'] ?? []);
            $shaho = (array) ($row['shaho'] ?? []);
            $kihon = (array) ($row['kihon'] ?? []);
            $shaho = (array) ($row['shaho'] ?? []);
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

        return view('admin_v2.work.transfer_list.index', [
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

    public function wageLedger(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $rows = (array) $pageData['rows'];
        $allowanceEntries = $this->allowanceLabelService->entries();

        $ledgerRows = [];
        foreach ($rows as $row) {
            if (trim((string) ($row['division'] ?? '')) === '業務委託') {
                continue;
            }

            $summary = (array) ($row['summary'] ?? []);
            $kihon = (array) ($row['kihon'] ?? []);
            $shaho = (array) ($row['shaho'] ?? []);

            $transferAmount = $this->num($summary['supply_sum'] ?? 0)
                - $this->num($summary['deduction_sum'] ?? 0)
                - $this->num($summary['transfer_balance'] ?? 0);

            $ledgerRow = [
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'store_code' => trim((string) ($row['store_code'] ?? '')),
                'store_name' => trim((string) ($row['store_name'] ?? '')),
                'staff_id' => trim((string) ($row['staff_id'] ?? '')),
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
                'division' => trim((string) ($row['division'] ?? '')),
                'shaho' => $shaho,
                'bonus_amount' => $this->num($summary['bonus_amo'] ?? 0),
                'basic_salary' => $this->num($summary['basic_salary'] ?? 0),
                'officer_com' => $this->num($summary['officer_com'] ?? 0),
                'position_allowance' => $this->num($summary['position_allow'] ?? 0),
                'qualification_allowance' => $this->num($summary['qualification_allow'] ?? 0),
                'duties_allowance' => $this->num($kihon['duties_allow'] ?? 0),
                'request_allowance' => $this->num($summary['claim_allow'] ?? 0),
                'family_allowance' => $this->num($summary['rent_subsidies'] ?? 0),
                'adjust_allowance' => $this->num($summary['adjustment_add'] ?? 0),
                'fixed_overtime_allowance' => $this->num($kihon['fixed_overtime'] ?? 0),
                'taxable_commuting' => $this->num($kihon['traffic_pay'] ?? 0),
                'non_taxable_commuting' => $this->num($kihon['rent_pay'] ?? 0),
                'taxation_sum' => $this->num($summary['taxation_sum'] ?? 0),
                'not_taxation_sum' => $this->num($summary['not_taxation_sum'] ?? 0),
                'supply_sum' => $this->num($summary['supply_sum'] ?? 0),
                'kenpo' => $this->num($summary['kenpo'] ?? 0),
                'kaigo' => $this->num($summary['kaigo'] ?? 0),
                'kounen' => $this->num($summary['kounen'] ?? 0),
                'koyou' => $this->num($summary['koyou'] ?? 0),
                'kenpo_monthly_amo' => $shaho['kenpo_monthly_amo'] ?? null,
                'kounen_monthly_amo' => $shaho['kounen_monthly_amo'] ?? null,
                'syaho_sum' => $this->num($summary['syaho_sum'] ?? 0),
                'income_tax' => $this->num($summary['income_tax'] ?? 0),
                'resident_tax' => $this->num($summary['resident_tax'] ?? 0),
                'koujyo_1' => $this->num($summary['koujyo_1'] ?? 0),
                'late_deduction' => $this->num($summary['late_deduction'] ?? 0),
                'absence_deduction' => $this->num($summary['absence_deduction'] ?? 0),
                'deduction_sum' => $this->num($summary['deduction_sum'] ?? 0),
                'adjustment_year_end' => $this->num($summary['adjustment_year_end'] ?? 0),
                'cost_liquidation' => $this->num($summary['cost_liquidation'] ?? 0),
                'transfer_amount' => $transferAmount,
                'work_in_num' => $this->num($summary['work_in_num'] ?? 0),
                'work_time' => $this->num($summary['work_time'] ?? 0),
                'late_time' => $this->num($summary['late_time'] ?? 0),
                'overtime' => $this->num($summary['overtime'] ?? 0),
                'work_holiday_num' => $this->num($summary['work_holiday_num'] ?? ($summary['work_horiday_num'] ?? 0)),
                'holiday_work_time' => $this->num($summary['work_time_num'] ?? 0),
                'holiday_true' => $this->num($summary['holiday_true'] ?? 0),
                'holiday_true_num' => $this->num($summary['holiday_true_num'] ?? ($summary['horiday_true_num'] ?? 0)),
                'absence_num' => $this->num($summary['absence_num'] ?? 0),
            ];

            foreach ($allowanceEntries as $entry) {
                $entryKey = trim((string) ($entry['key'] ?? ''));
                if ($entryKey === '') {
                    continue;
                }
                $ledgerRow[$entryKey] = $this->num($summary[$entryKey] ?? 0);
            }

            $ledgerRows[] = $ledgerRow;
        }

        usort($ledgerRows, static function (array $a, array $b): int {
            return [$a['company_name'], $a['store_code'], $a['store_name'], $a['staff_id'], $a['staff_name']]
                <=> [$b['company_name'], $b['store_code'], $b['store_name'], $b['staff_id'], $b['staff_name']];
        });

        $groupedCompanies = [];
        foreach ($ledgerRows as $row) {
            $companyKey = $row['company_name'] !== '' ? $row['company_name'] : '未設定';

            if (!isset($groupedCompanies[$companyKey])) {
                $groupedCompanies[$companyKey] = [
                    'company_name' => $companyKey,
                    'rows' => [],
                    'totals' => [
                        'taxation_sum' => 0.0,
                        'supply_sum' => 0.0,
                        'deduction_sum' => 0.0,
                        'transfer_amount' => 0.0,
                        'resident_tax' => 0.0,
                        'income_tax' => 0.0,
                    ],
                ];
            }

            $groupedCompanies[$companyKey]['rows'][] = $row;
            $groupedCompanies[$companyKey]['totals']['taxation_sum'] += $row['taxation_sum'];
            $groupedCompanies[$companyKey]['totals']['supply_sum'] += $row['supply_sum'];
            $groupedCompanies[$companyKey]['totals']['deduction_sum'] += $row['deduction_sum'];
            $groupedCompanies[$companyKey]['totals']['transfer_amount'] += $row['transfer_amount'];
            $groupedCompanies[$companyKey]['totals']['resident_tax'] += $row['resident_tax'];
            $groupedCompanies[$companyKey]['totals']['income_tax'] += $row['income_tax'];
        }

        return view('admin_v2.work.wage_ledger.index', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedCompanyId' => $selectedCompanyId,
            'groupedCompanies' => array_values($groupedCompanies),
            'companyLabel' => $this->resolveCompanyLabel($rows),
            'allowanceEntries' => $allowanceEntries,
            'allowanceLabelMap' => $this->allowanceLabelService->labelMap(),
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

    public function bonusUpdate(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'values' => ['required', 'array'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->updateBonusSummary(
            (string) $v['staff_id'],
            $year,
            $month,
            (array) $v['values']
        );

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

    public function bonusRecalculate(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $paymentDate = trim((string) ($v['payment_date'] ?? ''));
        if ($paymentDate === '') {
            $paymentDate = sprintf('%04d-%02d-01', $year, $month);
        }

        $updated = 0;
        $updated += (int) $this->updateBonusSummary(
            (string) $v['staff_id'],
            $year,
            $month,
            ['fuyo_sum' => $this->fuyoService->resolveByPaymentDate((string) $v['staff_id'], $paymentDate)]
        );
        $updated += (int) $this->employmentInsuranceService->recalculateBonus(
            (string) $v['staff_id'],
            $paymentDate
        );
        $updated += (int) $this->bonusSocialInsuranceService->recalculate(
            (string) $v['staff_id'],
            $paymentDate
        );
        $updated += (int) $this->bonusIncomeTaxCalcService->recalculate(
            (string) $v['staff_id'],
            $paymentDate
        );
        $updated += (int) $this->updateBonusSummary(
            (string) $v['staff_id'],
            $year,
            $month,
            []
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
            (string) ($v['payment_date'] ?? ''),
            false
        );

        return response()->json([
            'ok' => true,
            'candidates' => $payload['candidates'],
            'suggested_payment_date' => $payload['suggested_payment_date'],
            'payment_date_options' => $payload['payment_date_options'],
        ]);
    }

    public function bonusCreateCandidates(Request $request): JsonResponse
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
            (string) ($v['payment_date'] ?? ''),
            true
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
            (string) ($v['payment_date'] ?? ''),
            false
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

    public function bonusCreate(Request $request): JsonResponse
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
            (string) ($v['payment_date'] ?? ''),
            true
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
            (string) $v['payment_date'],
            false
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

    public function bonusDelete(Request $request): JsonResponse
    {
        $v = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->deleteService->delete(
            array_map('strval', (array) $v['staff_ids']),
            (string) $v['payment_date'],
            true
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
    private function buildPageData(Request $request, bool $bonus = false): array
    {
        $availablePaymentDates = $this->monthService->availablePaymentDates($bonus);
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
        $summaryMap = $this->summaryService->summaryMapByPaymentDate($selectedPaymentDate, $bonus);
        $previousSummaryMap = $this->summaryService->previousSummaryMapByPaymentDate($selectedPaymentDate, $bonus);
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
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', ' '], '', $text);
        return is_numeric($text) ? (float) $text : 0.0;
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function attachBonusCalc(array $rows, string $selectedPaymentDate): array
    {
        if ($rows === [] || $selectedPaymentDate === '') {
            return $rows;
        }

        $staffIds = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['staff_id'] ?? '')),
            $rows
        ), static fn (string $id): bool => $id !== ''));

        if ($staffIds === []) {
            return $rows;
        }

        $selectedTs = strtotime($selectedPaymentDate);
        if ($selectedTs === false) {
            return $rows;
        }

        $sameMonthStart = date('Y-m-01', $selectedTs);
        $sameMonthEnd = date('Y-m-t', $selectedTs);
        $fiscalStart = ((int) date('n', $selectedTs) >= 4)
            ? date('Y-04-01', $selectedTs)
            : date('Y-04-01', strtotime('-1 year', $selectedTs));

        $historyRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select(['kyuyo_staff_id', 'supply_month', 'bonus_amo'])
            ->where('bonus', 1)
            ->whereIn('kyuyo_staff_id', $staffIds)
            ->whereNotNull('supply_month')
            ->whereRaw('CONVERT(date, [supply_month]) >= ?', [$fiscalStart])
            ->whereRaw('CONVERT(date, [supply_month]) <= ?', [$sameMonthEnd])
            ->get();

        $historyMap = [];
        foreach ($historyRows as $historyRow) {
            $staffId = trim((string) ($historyRow->kyuyo_staff_id ?? ''));
            $paymentDate = trim((string) ($historyRow->supply_month ?? ''));
            if ($staffId === '' || $paymentDate === '') {
                continue;
            }

            $paymentTs = strtotime($paymentDate);
            if ($paymentTs === false) {
                continue;
            }

            $gross = $this->num($historyRow->bonus_amo ?? 0);
            $standard = floor($gross / 1000) * 1000;

            $historyMap[$staffId][] = [
                'date' => date('Y-m-d', $paymentTs),
                'gross' => $gross,
                'standard' => $standard,
            ];
        }

        foreach ($rows as &$row) {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $summary = (array) ($row['summary'] ?? []);
            $currentGross = $this->num($summary['bonus_amo'] ?? 0);
            $currentStandard = floor($currentGross / 1000) * 1000;

            $sameMonthPrior = 0.0;
            $sameMonthOtherGross = 0.0;
            $fiscalPrior = 0.0;

            foreach (($historyMap[$staffId] ?? []) as $history) {
                $historyDate = (string) ($history['date'] ?? '');
                $historyGross = $this->num($history['gross'] ?? 0);
                $historyStandard = $this->num($history['standard'] ?? 0);

                if ($historyDate < $selectedPaymentDate) {
                    $fiscalPrior += $historyStandard;
                }

                if ($historyDate >= $sameMonthStart && $historyDate < $selectedPaymentDate) {
                    $sameMonthOtherGross += $historyGross;
                    $sameMonthPrior += $historyStandard;
                }
            }

            $kenpoCapRemainingBefore = max(5730000 - $fiscalPrior, 0);
            $kenpoTargetStandard = min($currentStandard, $kenpoCapRemainingBefore);
            $fiscalAfter = $fiscalPrior + $currentStandard;

            $kounenCapRemainingBefore = max(1500000 - $sameMonthPrior, 0);
            $kounenTargetStandard = min($currentStandard, $kounenCapRemainingBefore);
            $sameMonthAfter = $sameMonthPrior + $currentStandard;

            $row['bonus_calc'] = [
                'bonus_gross_amount' => $currentGross,
                'bonus_standard_amount' => $currentStandard,
                'same_month_other_bonus' => $sameMonthOtherGross,
                'same_month_standard_before' => $sameMonthPrior,
                'same_month_standard_after' => $sameMonthAfter,
                'kenpo_fiscal_standard_before' => $fiscalPrior,
                'kenpo_fiscal_standard_after' => $fiscalAfter,
                'kenpo_target_standard' => $kenpoTargetStandard,
                'kounen_target_standard' => $kounenTargetStandard,
                'kenpo_cap_hit' => $kenpoTargetStandard < $currentStandard ? 1 : 0,
                'kounen_cap_hit' => $kounenTargetStandard < $currentStandard ? 1 : 0,
            ];
        }
        unset($row);

        return $rows;
    }

    /** @param array<string,mixed> $values */
    private function updateBonusSummary(string $staffId, int $year, int $month, array $values): int
    {
        $row = $this->loadBonusSummaryRow($staffId, $year, $month);
        if ($row === null) {
            return 0;
        }

        $current = (array) $row;
        $payload = $this->sanitizeBonusValues($values);
        $merged = array_merge($current, $payload);

        $bonusAmount = $this->num($merged['bonus_amo'] ?? 0);
        $taxationSum = max(0.0, round($bonusAmount, 0));
        $notTaxationSum = 0.0;
        $supplySum = $taxationSum + $notTaxationSum;
        $rouhoTargetSum = $taxationSum;
        $syahoSum = $this->num($merged['kenpo'] ?? 0)
            + $this->num($merged['kaigo'] ?? 0)
            + $this->num($merged['kounen'] ?? 0)
            + $this->num($merged['koyou'] ?? 0);
        $deductionSum = $syahoSum
            + $this->num($merged['income_tax'] ?? 0)
            + $this->num($merged['resident_tax'] ?? 0)
            + $this->num($merged['rent_cost'] ?? 0)
            + $this->num($merged['adjustment_cost'] ?? 0)
            + $this->num($merged['koujyo_1'] ?? 0);

        $payload['taxation_sum'] = (int) $taxationSum;
        $payload['not_taxation_sum'] = (int) $notTaxationSum;
        $payload['supply_sum'] = (int) $supplySum;
        $payload['rouho_target_sum'] = (int) $rouhoTargetSum;
        $payload['syaho_sum'] = (int) round($syahoSum, 0);
        $payload['deduction_sum'] = (int) round($deductionSum, 0);
        $payload['syaho_deduction_sum'] = (int) round(max(0.0, $taxationSum - $syahoSum), 0);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update($payload);
    }

    private function loadBonusSummaryRow(string $staffId, int $year, int $month): ?object
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [trim($staffId)])
            ->orderByDesc('kyuyo_sho_no')
            ->first();
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function sanitizeBonusValues(array $values): array
    {
        $numericColumns = [
            'bonus_amo',
            'kenpo',
            'kaigo',
            'kounen',
            'koyou',
            'income_tax',
            'resident_tax',
            'rent_cost',
            'adjustment_cost',
            'koujyo_1',
            'transfer_balance',
            'fuyo_sum',
            'rouho_target_sum',
        ];
        $textColumns = ['kyuyo_memo'];
        $payload = [];

        foreach ($values as $key => $raw) {
            $column = trim((string) $key);
            if ($column === '') {
                continue;
            }

            if (in_array($column, $textColumns, true)) {
                $payload[$column] = trim((string) $raw);
                continue;
            }

            if (!in_array($column, $numericColumns, true)) {
                continue;
            }

            $value = trim((string) $raw);
            if ($value === '') {
                $payload[$column] = null;
                continue;
            }

            $value = str_replace([',', ' '], '', $value);
            if (!is_numeric($value)) {
                continue;
            }

            $payload[$column] = str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $payload;
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
