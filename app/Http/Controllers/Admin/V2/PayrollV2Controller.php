<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2AttendanceStaffService;
use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmedStateService;
use App\Services\Admin\V2\Payroll\PayrollV2AllowanceLabelService;
use App\Services\Admin\V2\Payroll\PayrollV2AttendanceReflectService;
use App\Services\Admin\V2\Payroll\PayrollV2BonusSocialInsuranceService;
use App\Services\Admin\V2\Payroll\PayrollV2CalculationFlowService;
use App\Services\Admin\V2\Payroll\PayrollV2CompanyService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateCandidatesService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateService;
use App\Services\Admin\V2\Payroll\PayrollV2DeleteService;
use App\Services\Admin\V2\Payroll\PayrollV2HomeVisitAllowanceService;
use App\Services\Admin\V2\Payroll\PayrollV2IncomeTaxService;
use App\Services\Admin\V2\Payroll\PayrollV2KihonService;
use App\Services\Admin\V2\Payroll\PayrollV2MonthService;
use App\Services\Admin\V2\Payroll\PayrollV2OvertimeDeductionService;
use App\Services\Admin\V2\Payroll\PayrollV2RecalculateService;
use App\Services\Admin\V2\Payroll\PayrollV2ResidentService;
use App\Services\Admin\V2\Payroll\PayrollV2SalesImportService;
use App\Services\Admin\V2\Payroll\PayrollV2SocialInsuranceAmountService;
use App\Services\Admin\V2\Payroll\PayrollV2ShahoService;
use App\Services\Admin\V2\Payroll\PayrollV2StaffMasterService;
use App\Services\Admin\V2\Payroll\PayrollV2StaffService;
use App\Services\Admin\V2\Payroll\PayrollV2SummaryService;
use App\Services\Admin\V2\Payroll\PayrollV2UpdateService;
use App\Support\JapaneseDate;
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
        private readonly PayrollV2SalesImportService $salesImportService,
        private readonly PayrollV2AllowanceLabelService $allowanceLabelService,
        private readonly PayrollV2CalculationFlowService $calculationFlowService,
        private readonly PayrollV2AttendanceReflectService $attendanceReflectService,
        private readonly AttendanceV2AttendanceStaffService $attendanceStaffService,
        private readonly AttendanceV2ConfirmedStateService $confirmedStateService,
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2RecalculateService $recalculateService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
        private readonly PayrollV2SocialInsuranceAmountService $socialInsuranceAmountService,
        private readonly PayrollV2BonusSocialInsuranceService $bonusSocialInsuranceAmountService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
        private readonly PayrollV2HomeVisitAllowanceService $homeVisitAllowanceService,
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
        $attendanceLinkMonth = '';
        $paymentTimestamp = strtotime($selectedPaymentDate);
        if ($paymentTimestamp !== false) {
            $attendanceLinkMonth = date('Y-m', strtotime('-1 month', $paymentTimestamp));
        }

        return view('admin_v2.work.payroll.index', [
            'availablePaymentDates' => $availablePaymentDates,
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'attendanceLinkMonth' => $attendanceLinkMonth,
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
        $rows = $this->attachBonusCalc($rows, $selectedPaymentDate);

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
        return $this->buildTransferListView($request, false);
    }

    public function bonusTransferList(Request $request): View
    {
        return $this->buildTransferListView($request, true);
    }

    /**
     * 給与・賞与の振込一覧は同じ計算式・同じテンプレートを使う。
     * $isBonus はデータ取得元(mx_kyuyo_shou.bonus)の切り替えだけに使い、
     * 集計ロジックそのものは1箇所にまとめる。
     */
    private function buildTransferListView(Request $request, bool $isBonus): View
    {
        $pageData = $this->buildPageData($request, $isBonus);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $rows = (array) $pageData['rows'];
        $mayorMetaMap = $this->mayorMetaMap();

        $transferRows = [];
        foreach ($rows as $row) {
            $summary = (array) ($row['summary'] ?? []);
            $staffMaster = (array) ($row['staff_master'] ?? []);
            // 住民税の提出先(submission)はmx_staffsではなく、対象年度のmx_residentレコードが
            // 正（PayrollV2ResidentService::map()が支給年月から正しい年度のレコードを解決済み）。
            // mx_staffs.submissionはこの機能移設後は更新されなくなるため使わない。
            $resident = (array) ($row['resident'] ?? []);

            $bankName1Raw = trim((string) ($staffMaster['bank_name_1'] ?? ''));
            $bankName = $bankName1Raw;
            $bankBranch = trim((string) ($staffMaster['bank_branch_1'] ?? ''));
            $accountNo = trim((string) ($staffMaster['account_num'] ?? ''));

            if ($bankName === '' && trim((string) ($staffMaster['bank_name_2'] ?? '')) !== '') {
                $bankName = trim((string) ($staffMaster['bank_name_2'] ?? ''));
                $bankBranch = trim((string) ($staffMaster['bank_branch_2'] ?? ''));
                $accountNo = trim((string) ($staffMaster['account_num2'] ?? ''));
            }

            // $summaryはPayrollV2SummaryService::normalizeSummaryRow()を経由済みで、
            // transfer_amountは保存済みsupply_deduction_sumから既に入っている（帳票側で再計算しない）。
            $transferAmount = (float) ($summary['transfer_amount'] ?? 0);

            // 振込残額(transfer_balance)がある場合、口座1に全額ではなく口座2へその分を
            // 分けて振り込む。第2口座の登録がある時だけ表示を2行に割る（並び替え後に展開する
            // ためソートキーには影響させない）。氏名・部署等は口座1側にだけ表示し、口座2側は
            // 空欄＋網掛けで同一人物の続きとわかるようにする（2026-08-18）。
            $transferBalance = (float) ($summary['transfer_balance'] ?? 0);
            $bankName2 = trim((string) ($staffMaster['bank_name_2'] ?? ''));
            $secondaryAccount = null;
            // bank_name_1が空でbank_name_2にフォールバックした場合、$bankNameは既にbank_name_2に
            // なっている。そのため$bankNameだけで判定すると同じ口座2が「主」「口座2」の両方に
            // 二重表示されてしまう。フォールバック前の生のbank_name_1で口座1が実在するかを見る
            // （2026-08-18、レビューで発覚）。
            if (abs($transferBalance) > 0.0000001 && $bankName1Raw !== '' && $bankName2 !== '') {
                $secondaryAccount = [
                    'bank_name' => $bankName2,
                    'bank_branch' => trim((string) ($staffMaster['bank_branch_2'] ?? '')),
                    'account_no' => trim((string) ($staffMaster['account_num2'] ?? '')),
                    'amount' => $transferBalance,
                ];
            }

            $transferRows[] = [
                'company_name' => trim((string) ($row['company_name'] ?? '')),
                'division' => trim((string) ($row['division'] ?? '')),
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
                'staff_name_furi' => trim((string) ($staffMaster['staff_name_furi'] ?? '')),
                'bank_name' => $bankName,
                'bank_branch' => $bankBranch,
                'account_no' => $accountNo,
                'transfer_amount' => $transferAmount,
                // 口座1側の表示額（帳票は保存値・計算済み値のみ表示する原則のため、ここで
                // 確定しておく。bladeで再計算しない、2026-08-18）。
                'primary_amount' => $transferAmount - ($secondaryAccount['amount'] ?? 0.0),
                'secondary_account' => $secondaryAccount,
                'city' => $this->resolveMunicipalityLabel(
                    trim((string) ($resident['submission'] ?? '')),
                    trim((string) ($staffMaster['city'] ?? '')),
                    $mayorMetaMap
                ),
                'specified_num' => $this->resolveSpecifiedNum(
                    trim((string) ($resident['submission'] ?? '')),
                    $mayorMetaMap
                ),
                'submission' => trim((string) ($resident['submission'] ?? '')),
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
            // 業務委託は事業所得扱いで給与所得の源泉徴収・住民税特別徴収の対象外のため、
            // 「会社合計」サマリー（課税対象額合計・所得税額・住民税・営業店在籍者人数）だけ
            // 除く。市町村別サマリー・振込支払額・個別行は業務委託も含めたまま（2026-08-18）。
            $isOutsourceRow = $row['division'] === '業務委託';

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
            // 口座2に分割してる分は別の銀行への振込なので、この銀行（口座1）の小計には
            // 口座1側の額（primary_amount）だけを乗せる。口座2側の額は口座2の銀行の小計へ
            // 別途加算する（2026-08-18、口座1・口座2が別銀行の場合に小計が実際より多く
            // 出ていた不具合を修正）。
            $groupedCompanies[$companyKey]['groups'][$bankKey]['transfer_total'] += $row['primary_amount'];
            $groupedCompanies[$companyKey]['groups'][$bankKey]['resident_tax_total'] += $row['resident_tax'];
            $groupedCompanies[$companyKey]['groups'][$bankKey]['taxation_total'] += $row['taxation_sum'];
            $groupedCompanies[$companyKey]['groups'][$bankKey]['income_tax_total'] += $row['income_tax'];

            if (!empty($row['secondary_account'])) {
                $secondaryBankKey = $row['secondary_account']['bank_name'] !== ''
                    ? $row['secondary_account']['bank_name']
                    : '未設定';
                if (!isset($groupedCompanies[$companyKey]['groups'][$secondaryBankKey])) {
                    $groupedCompanies[$companyKey]['groups'][$secondaryBankKey] = [
                        'bank_name' => $secondaryBankKey,
                        'rows' => [],
                        'transfer_total' => 0.0,
                        'resident_tax_total' => 0.0,
                        'taxation_total' => 0.0,
                        'income_tax_total' => 0.0,
                    ];
                }
                $groupedCompanies[$companyKey]['groups'][$secondaryBankKey]['transfer_total'] += $row['secondary_account']['amount'];
            }
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
            $groupedCompanies[$companyKey]['row_count']++;
            if (!$isOutsourceRow) {
                $groupedCompanies[$companyKey]['resident_tax_total'] += $row['resident_tax'];
                $groupedCompanies[$companyKey]['taxation_total'] += $row['taxation_sum'];
                $groupedCompanies[$companyKey]['income_tax_total'] += $row['income_tax'];
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
            'isBonus' => $isBonus,
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
        return $this->buildWageLedgerView($request, false);
    }

    public function bonusWageLedger(Request $request): View
    {
        return $this->buildWageLedgerView($request, true);
    }

    /**
     * 給与・賞与の賃金台帳は同じ計算式・同じテンプレートを使う。
     * $isBonus はデータ取得元(mx_kyuyo_shou.bonus)の切り替えだけに使い、
     * 集計ロジックそのものは1箇所にまとめる。
     */
    private function buildWageLedgerView(Request $request, bool $isBonus): View
    {
        $pageData = $this->buildPageData($request, $isBonus);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $rows = (array) $pageData['rows'];
        $allowanceEntries = $this->allowanceLabelService->entries();

        $ledgerRows = [];
        foreach ($rows as $row) {
            if (!$this->shouldIncludeWageLedgerRow($row)) {
                continue;
            }

            $summary = (array) ($row['summary'] ?? []);
            $kihon = (array) ($row['kihon'] ?? []);
            $shaho = (array) ($row['shaho'] ?? []);

            // $summaryはPayrollV2SummaryService::normalizeSummaryRow()を経由済みで、
            // transfer_amountは保存済みsupply_deduction_sumから既に入っている（帳票側で再計算しない）。
            $transferAmount = (float) ($summary['transfer_amount'] ?? 0);

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
                'allowance_amo_2' => $this->num($summary['allowance_amo_2'] ?? 0),
                'position_allowance' => $this->num($summary['position_allow'] ?? 0),
                'qualification_allowance' => $this->num($summary['qualification_allow'] ?? 0),
                'duties_allowance' => $this->num($kihon['duties_allow'] ?? 0),
                'request_allowance' => $this->num($summary['claim_allow'] ?? 0),
                'family_allowance' => $this->num($summary['rent_subsidies'] ?? 0),
                'adjust_allowance' => $this->num($summary['adjustment_add'] ?? 0),
                'fixed_overtime_allowance' => $this->num($kihon['fixed_overtime'] ?? 0),
                'taxation_sum' => $this->num($summary['taxation_sum'] ?? 0),
                'not_taxation_sum' => $this->num($summary['not_taxation_sum'] ?? 0),
                'supply_sum' => $this->num($summary['supply_sum'] ?? 0),
                'kenpo' => $this->num($summary['kenpo'] ?? 0),
                'kaigo' => $this->num($summary['kaigo'] ?? 0),
                'child_support_funds' => $this->num($summary['child_support_funds'] ?? 0),
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
                'work_in_num_net' => $this->num($summary['work_in_num_net'] ?? 0),
                'work_time' => $this->num($summary['work_time'] ?? 0),
                'work_time_net' => $this->num($summary['work_time_net'] ?? 0),
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

            $ledgerRow = $this->mergeTrafficAdditionIntoNonTaxableCommuting($ledgerRow, $summary);

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
            'isBonus' => $isBonus,
            'groupedCompanies' => array_values($groupedCompanies),
            'companyLabel' => $this->resolveCompanyLabel($rows),
            'allowanceEntries' => $allowanceEntries,
            'allowanceLabelMap' => $this->allowanceLabelService->labelMap(),
        ]);
    }

    public function companyBurdenPrint(Request $request): View
    {
        return $this->buildCompanyBurdenPrintView($request, false);
    }

    public function bonusCompanyBurdenPrint(Request $request): View
    {
        return $this->buildCompanyBurdenPrintView($request, true);
    }

    /**
     * 会社負担一覧は給与・賞与で保険料の計算経路が違うため（賞与は標準賞与額＋上限キャップ）、
     * $isBonusで金額の算出元だけ切り替える。帳票の様式・集計ロジックは1箇所にまとめる
     * （bonusWageLedger/buildWageLedgerViewと同じ方針）。
     */
    private function buildCompanyBurdenPrintView(Request $request, bool $isBonus): View
    {
        $pageData = $this->buildPageData($request, $isBonus);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $selectedMonth = (string) $pageData['selectedMonth'];
        $rows = (array) $pageData['rows'];

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));
        $amountKeys = [
            'kenpo_self',
            'kenpo_office',
            'kenpo_total',
            'kaigo_self',
            'kaigo_office',
            'kaigo_total',
            'kounen_self',
            'kounen_office',
            'kounen_total',
            'child_support_self',
            'child_support_funds',
            'child_support_total',
            'jidou_office',
            'koyou_self',
            'koyou_office',
            'koyou_total',
            'rousai_office',
            'self_total',
            'office_total',
            'grand_total',
            'transfer_amount',
            'total_with_burden',
        ];

        $groupedStores = [];
        $grandTotals = array_fill_keys($amountKeys, 0.0);

        foreach ($rows as $row) {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }

            if (trim((string) ($row['division'] ?? '')) === '業務委託') {
                continue;
            }

            $summary = (array) ($row['summary'] ?? []);
            if ($summary === []) {
                continue;
            }

            $staffMaster = (array) ($row['staff_master'] ?? []);

            if ($isBonus) {
                $kyuyoShoNo = (int) ($summary['kyuyo_sho_no'] ?? 0);
                $amounts = $this->bonusSocialInsuranceAmountService->statementAmounts(
                    $staffId,
                    $selectedPaymentDate,
                    $kyuyoShoNo,
                    $summary
                );
            } else {
                $shaho = array_merge(
                    (array) ($row['shaho'] ?? []),
                    $this->socialInsuranceAmountService->loadRatesForStaff($staffId, $selectedPaymentDate)
                );
                $amounts = $this->socialInsuranceAmountService->statementAmounts(
                    $summary,
                    $shaho,
                    $this->socialInsuranceAmountService->toDate($staffMaster['birthday'] ?? null),
                    $year,
                    $month
                );
            }

            // 要確認：雇用保険料・労災保険はPayrollV2EmploymentInsuranceServiceが計算して
            // mx_kyuyo_shou（koyou/koyou_office/rousai_office）に保存済みの値をそのまま使う
            // （帳票側で再計算しない、他の項目と同じ方針）。差引支給合計も同様に保存値
            // （summary.transfer_amount、PayrollV2SummaryServiceで算出済み）を使う。
            $koyouSelf = $this->num($summary['koyou'] ?? 0);
            $koyouOffice = $this->num($summary['koyou_office'] ?? 0);
            $rousaiOffice = $this->num($summary['rousai_office'] ?? 0);
            $transferAmount = $this->num($summary['transfer_amount'] ?? 0);

            $amounts['koyou_self'] = $koyouSelf;
            $amounts['koyou_office'] = $koyouOffice;
            $amounts['koyou_total'] = $koyouSelf + $koyouOffice;
            $amounts['rousai_office'] = $rousaiOffice;
            $amounts['self_total'] = $this->num($amounts['self_total'] ?? 0) + $koyouSelf;
            $amounts['office_total'] = $this->num($amounts['office_total'] ?? 0) + $koyouOffice + $rousaiOffice;
            $amounts['grand_total'] = $this->num($amounts['grand_total'] ?? 0) + $koyouSelf + $koyouOffice + $rousaiOffice;
            $amounts['transfer_amount'] = $transferAmount;
            $amounts['total_with_burden'] = $transferAmount + $amounts['office_total'];

            if (
                $this->num($amounts['self_total'] ?? 0) <= 0
            ) {
                continue;
            }

            $companyName = trim((string) ($row['company_name'] ?? ''));
            $storeCode = trim((string) ($row['store_code'] ?? ''));
            $storeName = trim((string) ($row['store_name'] ?? ''));
            $groupKey = $companyName . "\n" . $storeCode . "\n" . $storeName;

            if (!isset($groupedStores[$groupKey])) {
                $groupedStores[$groupKey] = [
                    'company_name' => $companyName,
                    'store_code' => $storeCode,
                    'store_name' => $storeName,
                    'rows' => [],
                    'totals' => array_fill_keys($amountKeys, 0.0),
                ];
            }

            $printRow = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
                'kenpo_standard' => $amounts['kenpo_standard'],
                'kounen_standard' => $amounts['kounen_standard'],
            ] + $amounts;

            $groupedStores[$groupKey]['rows'][] = $printRow;

            foreach ($amountKeys as $key) {
                $value = $this->num($amounts[$key] ?? 0);
                $groupedStores[$groupKey]['totals'][$key] += $value;
                $grandTotals[$key] += $value;
            }
        }

        uasort($groupedStores, static function (array $a, array $b): int {
            return [$a['company_name'], $a['store_code'], $a['store_name']]
                <=> [$b['company_name'], $b['store_code'], $b['store_name']];
        });

        return view('admin_v2.work.payroll.company_burden_print', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedMonth' => $selectedMonth,
            'isBonus' => $isBonus,
            'companyLabel' => $this->resolveCompanyLabel($rows),
            'groupedStores' => array_values($groupedStores),
            'grandTotals' => $grandTotals,
        ]);
    }

    public function homeVisitSalesPrint(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $selectedMonth = (string) $pageData['selectedMonth'];

        [$payrollYear, $payrollMonth] = array_map('intval', explode('-', $selectedMonth));
        [$salesYear, $salesMonth] = $this->previousYearMonth($payrollYear, $payrollMonth);

        $staffMap = [];
        foreach ($this->allStaffOptions() as $staff) {
            $staffId = str_pad(trim((string) ($staff['staff_id'] ?? '')), 3, '0', STR_PAD_LEFT);
            if ($staffId === '') {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) (($staff['staff_name'] ?? '') ?: ($staff['display_name_ja'] ?? ''))),
                'sakura_private' => 0.0,
                'sakura_insurance' => 0.0,
                'sakura_insurance_count' => 0,
                'sakura_private_count' => 0,
                'hinata_private' => 0.0,
                'hinata_insurance' => 0.0,
                'hinata_insurance_count' => 0,
                'hinata_private_count' => 0,
                'home_visit_count' => 0,
                'home_visit_sales' => 0.0,
            ];
        }

        $clinicRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as p')
            ->join('dbo.T_先生別日報 as d', 'p.患者No', '=', 'd.患者No_t')
            ->selectRaw("
            RIGHT('000' + LTRIM(RTRIM(CAST(d.[担当者ID] as nvarchar(20)))), 3) as staff_id,
            SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'さくら鍼灸整骨院' THEN ISNULL(d.[自費], 0) ELSE 0 END) as sakura_private,
            SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'さくら鍼灸整骨院' THEN ISNULL(d.[請求金額], 0) ELSE 0 END) as sakura_insurance,
            SUM(CASE WHEN (ISNULL(d.[請求金額], 0) + ISNULL(d.[保険負担], 0)) <> 0 AND p.[店舗] = N'さくら鍼灸整骨院' THEN 1 ELSE 0 END) as sakura_insurance_count,
            SUM(CASE WHEN p.[割合] = N'自' AND p.[店舗] = N'さくら鍼灸整骨院' THEN 1 ELSE 0 END) as sakura_private_count,
            SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'ひなた鍼灸整骨院' THEN ISNULL(d.[自費], 0) ELSE 0 END) as hinata_private,
            SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'ひなた鍼灸整骨院' THEN ISNULL(d.[請求金額], 0) ELSE 0 END) as hinata_insurance,
            SUM(CASE WHEN (ISNULL(d.[請求金額], 0) + ISNULL(d.[保険負担], 0)) <> 0 AND p.[店舗] = N'ひなた鍼灸整骨院' THEN 1 ELSE 0 END) as hinata_insurance_count,
            SUM(CASE WHEN p.[割合] = N'自' AND p.[店舗] = N'ひなた鍼灸整骨院' THEN 1 ELSE 0 END) as hinata_private_count
        ")
            ->whereRaw('YEAR(p.[日付]) = ?', [$salesYear])
            ->whereRaw('MONTH(p.[日付]) = ?', [$salesMonth])
            ->groupByRaw("RIGHT('000' + LTRIM(RTRIM(CAST(d.[担当者ID] as nvarchar(20)))), 3)")
            ->get();

        foreach ($clinicRows as $clinicRow) {
            $staffId = trim((string) ($clinicRow->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            if (!isset($staffMap[$staffId])) {
                $staffMap[$staffId] = [
                    'staff_id' => $staffId,
                    'staff_name' => '',
                    'sakura_private' => 0.0,
                    'sakura_insurance' => 0.0,
                    'sakura_insurance_count' => 0,
                    'sakura_private_count' => 0,
                    'hinata_private' => 0.0,
                    'hinata_insurance' => 0.0,
                    'hinata_insurance_count' => 0,
                    'hinata_private_count' => 0,
                    'home_visit_count' => 0,
                    'home_visit_sales' => 0.0,
                ];
            }

            $staffMap[$staffId]['sakura_private'] = $this->num($clinicRow->sakura_private ?? 0);
            $staffMap[$staffId]['sakura_insurance'] = $this->num($clinicRow->sakura_insurance ?? 0);
            $staffMap[$staffId]['sakura_insurance_count'] = (int) $this->num($clinicRow->sakura_insurance_count ?? 0);
            $staffMap[$staffId]['sakura_private_count'] = (int) $this->num($clinicRow->sakura_private_count ?? 0);
            $staffMap[$staffId]['hinata_private'] = $this->num($clinicRow->hinata_private ?? 0);
            $staffMap[$staffId]['hinata_insurance'] = $this->num($clinicRow->hinata_insurance ?? 0);
            $staffMap[$staffId]['hinata_insurance_count'] = (int) $this->num($clinicRow->hinata_insurance_count ?? 0);
            $staffMap[$staffId]['hinata_private_count'] = (int) $this->num($clinicRow->hinata_private_count ?? 0);
        }

        foreach ($this->salesImportService->totalsByStaff(array_keys($staffMap), $payrollYear, $payrollMonth) as $homeVisitRow) {
            $staffId = str_pad(trim((string) ($homeVisitRow['staff_id'] ?? '')), 3, '0', STR_PAD_LEFT);
            if ($staffId === '' || !isset($staffMap[$staffId])) {
                continue;
            }

            $staffMap[$staffId]['home_visit_count'] = (int) ($homeVisitRow['people_count'] ?? 0);
            $staffMap[$staffId]['home_visit_sales'] = $this->salesImportService->homeVisitSalesTotal($homeVisitRow);
        }

        $rows = [];
        foreach ($staffMap as $row) {
            $storeInsuranceTotal = $row['sakura_insurance'] + $row['hinata_insurance'];
            $storePrivateTotal = $row['sakura_private'] + $row['hinata_private'];
            $storeSalesTotal = $storeInsuranceTotal + $storePrivateTotal;
            $salesTotal = $storeSalesTotal + $row['home_visit_sales'];

            if (
                abs($salesTotal) < 0.0000001
                && $row['sakura_insurance_count'] === 0
                && $row['sakura_private_count'] === 0
                && $row['hinata_insurance_count'] === 0
                && $row['hinata_private_count'] === 0
            ) {
                continue;
            }

            $row['store_insurance_total'] = $storeInsuranceTotal;
            $row['store_private_total'] = $storePrivateTotal;
            $row['store_sales_total'] = $storeSalesTotal;
            $row['sales_total'] = $salesTotal;
            $rows[] = $row;
        }

        usort($rows, static fn(array $a, array $b): int => $b['sales_total'] <=> $a['sales_total']);

        $totals = [];
        foreach (['sakura_private', 'sakura_insurance', 'sakura_insurance_count', 'sakura_private_count', 'hinata_private', 'hinata_insurance', 'hinata_insurance_count', 'hinata_private_count', 'store_insurance_total', 'store_private_total', 'home_visit_count', 'home_visit_sales', 'sales_total'] as $key) {
            $totals[$key] = array_sum(array_map(static fn(array $row): float|int => $row[$key], $rows));
        }

        return view('admin_v2.work.payroll.home_visit_sales_print', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedCompanyId' => $selectedCompanyId,
            'salesYear' => $salesYear,
            'salesMonth' => $salesMonth,
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }


    // 委託メニュー売上

    public function outsourceMenuSalesPrint(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $selectedMonth = (string) $pageData['selectedMonth'];
        $payrollRows = (array) $pageData['rows'];

        [$payrollYear, $payrollMonth] = array_map('intval', explode('-', $selectedMonth));
        [$salesYear, $salesMonth] = $this->previousYearMonth($payrollYear, $payrollMonth);
        $fromDate = sprintf('%04d-%02d-01', $salesYear, $salesMonth);
        $toDate = date('Y-m-t', strtotime($fromDate));

        $staffMap = [];
        foreach ($payrollRows as $row) {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $division = trim((string) ($row['division'] ?? ''));
            if ($staffId === '' || $division !== '業務委託') {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
            ];
        }

        $staffIds = array_keys($staffMap);
        $rows = [];
        if ($staffIds !== []) {
            $queryRows = DB::connection('sqlsrv_dailyreport')
                ->table('dbo.T_施術メニュー as menu')
                ->join('dbo.T_先生別日報 as teacher', 'menu.メニュー', '=', 'teacher.メニュー')
                ->join('dbo.T_患者名日報 as patient', 'patient.患者No', '=', 'teacher.患者No_t')
                ->selectRaw("
                    CONVERT(date, patient.[日付]) as sales_date,
                    teacher.[メニュー] as menu_name,
                    LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20)))) as staff_id,
                    SUM(ISNULL(teacher.[自費], 0)) as private_total,
                    COUNT(patient.[患者No]) as patient_count,
                    menu.[歩合割合] as commission_rate,
                    COUNT(CASE WHEN ISNULL(teacher.[請求金額], 0) <> 0 THEN 1 ELSE NULL END) as insurance_count,
                    SUM(ISNULL(teacher.[請求金額], 0)) as claim_total
                ")
                ->whereBetween(DB::raw('CONVERT(date, patient.[日付])'), [$fromDate, $toDate])
                ->whereIn(DB::raw('LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))'), $staffIds)
                ->whereNotNull('teacher.メニュー')
                ->whereNotNull('menu.歩合割合')
                ->where('teacher.先生別外', 0)
                ->groupByRaw('CONVERT(date, patient.[日付]), teacher.[メニュー], LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20)))), menu.[歩合割合], teacher.[先生別外]')
                ->orderByRaw('LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))')
                ->orderByRaw('CONVERT(date, patient.[日付])')
                ->orderBy('teacher.メニュー')
                ->get();

            foreach ($queryRows as $queryRow) {
                $staffId = trim((string) ($queryRow->staff_id ?? ''));
                if ($staffId === '' || !isset($staffMap[$staffId])) {
                    continue;
                }

                $insuranceCount = (int) $this->num($queryRow->insurance_count ?? 0);
                $privateTotal = $this->num($queryRow->private_total ?? 0);
                $insuranceCommission = $insuranceCount * 700;
                $privateCommission = $privateTotal / 2;

                $rows[] = [
                    'sales_date' => date('m/d', strtotime((string) $queryRow->sales_date)),
                    'staff_id' => $staffId,
                    'staff_name' => $staffMap[$staffId]['staff_name'],
                    'menu_name' => trim((string) ($queryRow->menu_name ?? '')),
                    'insurance_count' => $insuranceCount,
                    'claim_total' => $this->num($queryRow->claim_total ?? 0),
                    'insurance_commission' => $insuranceCommission,
                    'patient_count' => (int) $this->num($queryRow->patient_count ?? 0),
                    'private_total' => $privateTotal,
                    'private_commission' => $privateCommission,
                    'row_total' => $insuranceCommission + $privateCommission,
                ];
            }
        }

        $groupedRows = [];
        foreach ($rows as $row) {
            $staffId = (string) $row['staff_id'];
            if (!isset($groupedRows[$staffId])) {
                $groupedRows[$staffId] = [
                    'staff_id' => $staffId,
                    'staff_name' => (string) $row['staff_name'],
                    'rows' => [],
                    'totals' => [
                        'insurance_count' => 0,
                        'claim_total' => 0.0,
                        'insurance_commission' => 0,
                        'patient_count' => 0,
                        'private_total' => 0.0,
                        'private_commission' => 0.0,
                        'row_total' => 0.0,
                    ],
                ];
            }

            $groupedRows[$staffId]['rows'][] = $row;
            foreach (array_keys($groupedRows[$staffId]['totals']) as $key) {
                $groupedRows[$staffId]['totals'][$key] += $row[$key];
            }
        }

        $totals = [];
        foreach (['insurance_count', 'claim_total', 'insurance_commission', 'patient_count', 'private_total', 'private_commission', 'row_total'] as $key) {
            $totals[$key] = array_sum(array_map(static fn(array $row): float|int => $row[$key], $rows));
        }

        return view('admin_v2.work.payroll.outsource_menu_sales_print', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedCompanyId' => $selectedCompanyId,
            'salesYear' => $salesYear,
            'salesMonth' => $salesMonth,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'rows' => $rows,
            'groupedRows' => array_values($groupedRows),
            'totals' => $totals,
        ]);
    }

    // 往診売上詳細

    public function homeVisitSalesDetailPrint(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];
        $selectedMonth = (string) $pageData['selectedMonth'];
        $payrollRows = (array) $pageData['rows'];

        [$payrollYear, $payrollMonth] = array_map('intval', explode('-', $selectedMonth));
        [$salesYear, $salesMonth] = $this->previousYearMonth($payrollYear, $payrollMonth);
        $fromDate = sprintf('%04d-%02d-01', $salesYear, $salesMonth);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $nextDate = date('Y-m-d', strtotime($toDate . ' +1 day'));

        $staffMap = [];
        foreach ($payrollRows as $row) {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
            ];
        }

        foreach ($this->allStaffOptions() as $row) {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            if ($staffId === '' || isset($staffMap[$staffId])) {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) (($row['staff_name'] ?? '') ?: ($row['display_name_ja'] ?? ''))),
            ];
        }

        $staffIds = array_keys($staffMap);
        $rows = [];
        if ($staffIds !== []) {
            foreach ($this->salesImportService->detailRows($staffIds, $payrollYear, $payrollMonth) as $queryRow) {
                $staffId = trim((string) ($queryRow['staff_id'] ?? ''));
                if ($staffId === '' || !isset($staffMap[$staffId])) {
                    continue;
                }

                $rows[] = [
                    'staff_id' => $staffId,
                    'staff_name' => $staffMap[$staffId]['staff_name'],
                    'treatment_date' => JapaneseDate::monthDayWithWeekday($queryRow['treatment_date'] ?? null),
                ] + $queryRow;
            }
        }

        $groupedRows = [];
        foreach ($rows as $row) {
            $staffId = (string) $row['staff_id'];
            if (!isset($groupedRows[$staffId])) {
                $groupedRows[$staffId] = [
                    'staff_id' => $staffId,
                    'staff_name' => (string) $row['staff_name'],
                    'rows' => [],
                    'totals' => [
                        'people_count' => 0,
                        'distance_total' => 0.0,
                        'kitazaike_total' => 0.0,
                        'higashi_kakogawa_total' => 0.0,
                        'harima_total' => 0.0,
                        'sakura_total' => 0.0,
                        'orita_total' => 0.0,
                        'miyamoto_total' => 0.0,
                        'yokoi_total' => 0.0,
                        'private_fee_total' => 0.0,
                        'uncollected_total' => 0.0,
                        'store_total' => 0.0,
                        'store_total_with_hari' => 0.0,
                    ],
                ];
            }

            $groupedRows[$staffId]['rows'][] = $row;
            foreach (array_keys($groupedRows[$staffId]['totals']) as $key) {
                $groupedRows[$staffId]['totals'][$key] += $row[$key];
            }
        }

        $totals = [];
        foreach (['people_count', 'distance_total', 'kitazaike_total', 'higashi_kakogawa_total', 'harima_total', 'sakura_total', 'orita_total', 'miyamoto_total', 'yokoi_total', 'private_fee_total', 'uncollected_total', 'store_total', 'store_total_with_hari'] as $key) {
            $totals[$key] = array_sum(array_map(static fn(array $row): float|int => $row[$key], $rows));
        }

        return view('admin_v2.work.payroll.home_visit_sales_detail_print', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedCompanyId' => $selectedCompanyId,
            'salesYear' => $salesYear,
            'salesMonth' => $salesMonth,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'groupedRows' => array_values($groupedRows),
            'totals' => $totals,
        ]);
    }

    public function outsourceRewardLedgerPrint(Request $request): View
    {
        $pageData = $this->buildPageData($request, false);
        $selectedPaymentDate = (string) $pageData['selectedPaymentDate'];
        $selectedMonth = (string) $pageData['selectedMonth'];
        $selectedCompanyId = (string) $pageData['selectedCompanyId'];

        $rows = [];
        foreach ((array) $pageData['rows'] as $row) {
            $summary = (array) ($row['summary'] ?? []);
            $salesTotal = $this->num($summary['kitazaike'] ?? 0)
                + $this->num($summary['higashi_kakogawa'] ?? 0)
                + $this->num($summary['tsubasa_harima'] ?? 0)
                + $this->num($summary['sakura_hari'] ?? 0)
                + $this->num($summary['orita_hari'] ?? 0)
                + $this->num($summary['miyamoto_hari'] ?? 0)
                + $this->num($summary['yokoi_hari'] ?? 0)
                + $this->num($summary['own_cost'] ?? 0)
                + $this->num($summary['unpaid_amo'] ?? 0);

            $hasHomeVisit = abs($salesTotal) > 0.0000001
                || abs($this->num($summary['peple_num'] ?? 0)) > 0.0000001
                || abs($this->num($summary['km'] ?? 0)) > 0.0000001;

            if (trim((string) ($row['division'] ?? '')) !== '業務委託' && !$hasHomeVisit) {
                continue;
            }

            // $summaryはPayrollV2SummaryService::normalizeSummaryRow()を経由済みで、
            // transfer_amountは保存済みsupply_deduction_sumから既に入っている（帳票側で再計算しない）。
            $transferAmount = (float) ($summary['transfer_amount'] ?? 0);

            // 課税/非課税を問わず交通費として1行に合算する（賃金台帳とは別基準、2026-08-18）。
            $commutingTotal = $this->num($summary['allowance_amo_10'] ?? 0)
                + $this->num($summary['allowance_amo_6'] ?? 0)
                + $this->num($summary['traffic_addition'] ?? 0);

            $rows[] = array_merge($summary, [
                'staff_id' => trim((string) ($row['staff_id'] ?? '')),
                'staff_name' => trim((string) ($row['staff_name'] ?? '')),
                'store_name' => trim((string) ($row['store_name'] ?? '')),
                'division' => trim((string) ($row['division'] ?? '')),
                'transfer_amount' => $transferAmount,
                'sales_total' => $salesTotal,
                'commuting_total' => $commutingTotal,
            ]);
        }

        usort($rows, static function (array $a, array $b): int {
            return [$a['store_name'] ?? '', $a['staff_id'] ?? '', $a['staff_name'] ?? '']
                <=> [$b['store_name'] ?? '', $b['staff_id'] ?? '', $b['staff_name'] ?? ''];
        });

        $totals = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                if (is_numeric($value)) {
                    $totals[$key] = ($totals[$key] ?? 0) + (float) $value;
                }
            }
        }

        return view('admin_v2.work.payroll.outsource_reward_ledger_print', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedMonth' => $selectedMonth,
            'selectedCompanyId' => $selectedCompanyId,
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }

    public function personalWageLedger(Request $request): View
    {
        $availablePaymentDates = $this->monthService->availablePaymentDates(false);
        $selectedPaymentDate = $this->monthService->normalizePaymentDate((string) $request->query('payment_date', ''), $availablePaymentDates);
        $selectedStartDate = date('Y-m-01', strtotime($selectedPaymentDate));
        $selectedStartMonthText = date('Y/m', strtotime($selectedStartDate));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $allowanceEntries = $this->allowanceLabelService->entries();

        $staffInfo = [
            'staff_id' => $selectedStaffId,
            'staff_name' => '',
            'division' => '',
            'store_code' => '',
            'store_name' => '',
            'company_name' => '',
        ];

        foreach ($this->staffService->staffs('') as $staff) {
            if ($selectedStaffId !== '' && trim((string) ($staff['staff_id'] ?? '')) === $selectedStaffId) {
                $staffInfo = $staff;
                break;
            }
        }

        $ledgerRows = [];
        if ($selectedStaffId !== '') {
            $records = DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->whereRaw('LTRIM(RTRIM(CAST(kyuyo_staff_id as nvarchar(50)))) = ?', [$selectedStaffId])
                ->whereIn('bonus', [0, 1])
                ->where('edit_lock', 1)
                ->whereNotNull('supply_month')
                ->whereRaw('CONVERT(date, [supply_month]) >= ?', [$selectedStartDate])
                ->orderByRaw('CONVERT(date, [supply_month]) asc')
                ->orderBy('bonus')
                ->orderBy('kyuyo_sho_no')
                ->get();

            $shahoMaps = [];
            foreach ($records as $record) {
                $summary = $this->normalizePayrollSummaryRow((array) $record);
                $paymentDate = trim((string) ($summary['supply_month'] ?? ''));
                $paymentTime = strtotime($paymentDate);
                if ($paymentTime === false) {
                    continue;
                }

                $year = (int) date('Y', $paymentTime);
                $month = (int) date('n', $paymentTime);
                $monthKey = sprintf('%04d-%02d', $year, $month);
                if (!isset($shahoMaps[$monthKey])) {
                    $shahoMaps[$monthKey] = $this->shahoService->map($year, $month);
                }

                $ledgerRows[] = $this->personalWageLedgerRow(
                    $summary,
                    $staffInfo,
                    $shahoMaps[$monthKey][$selectedStaffId] ?? [],
                    $allowanceEntries
                );
            }
        }

        return view('admin_v2.work.wage_ledger.personal', [
            'selectedPaymentDate' => $selectedPaymentDate,
            'selectedStartDate' => $selectedStartDate,
            'selectedStartMonthText' => $selectedStartMonthText,
            'selectedStaffId' => $selectedStaffId,
            'staffInfo' => $staffInfo,
            'ledgerRows' => $ledgerRows,
            'allowanceEntries' => $allowanceEntries,
        ]);
    }

    /**
     * @param array<string,mixed> $summary
     * @param array<string,mixed> $staffInfo
     * @param array<string,mixed> $shaho
     * @param list<array<string,mixed>> $allowanceEntries
     * @return array<string,mixed>
     */
    private function personalWageLedgerRow(array $summary, array $staffInfo, array $shaho, array $allowanceEntries): array
    {
        $isBonus = ((int) ($summary['bonus'] ?? 0)) === 1;
        // 差引支給額は保存値をそのまま表示する（帳票側で計算し直さない）。
        $transferAmount = (float) ($summary['supply_deduction_sum'] ?? 0);

        $paymentDate = trim((string) ($summary['supply_month'] ?? ''));
        $paymentTime = strtotime($paymentDate);
        $paymentMonth = $paymentTime === false ? '' : date('Y/m', $paymentTime);
        $paymentDateText = $paymentTime === false ? $paymentDate : date('Y/m/d', $paymentTime);

        $row = [
            'payment_date' => $paymentDateText,
            'payment_month' => $paymentMonth,
            'payment_type' => $isBonus ? '賞与' : '給与',
            'column_label' => trim($paymentMonth . ' ' . ($isBonus ? '賞与' : '給与')),
            'company_name' => trim((string) ($staffInfo['company_name'] ?? '')),
            'store_code' => trim((string) ($staffInfo['store_code'] ?? '')),
            'store_name' => trim((string) ($staffInfo['store_name'] ?? '')),
            'staff_id' => trim((string) ($staffInfo['staff_id'] ?? '')),
            'staff_name' => trim((string) ($staffInfo['staff_name'] ?? '')),
            'division' => trim((string) ($staffInfo['division'] ?? '')),
            'bonus_amount' => $this->num($summary['bonus_amo'] ?? 0),
            'basic_salary' => $this->num($summary['basic_salary'] ?? 0),
            'allowance_amo_2' => $this->num($summary['allowance_amo_2'] ?? 0),
            'taxation_sum' => $this->num($summary['taxation_sum'] ?? 0),
            'not_taxation_sum' => $this->num($summary['not_taxation_sum'] ?? 0),
            'supply_sum' => $this->num($summary['supply_sum'] ?? 0),
            'kenpo' => $this->num($summary['kenpo'] ?? 0),
            'kaigo' => $this->num($summary['kaigo'] ?? 0),
            'child_support_funds' => $this->num($summary['child_support_funds'] ?? 0),
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
            'work_in_num_net' => $this->num($summary['work_in_num_net'] ?? 0),
            'work_time' => $this->num($summary['work_time'] ?? 0),
            'work_time_net' => $this->num($summary['work_time_net'] ?? 0),
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
            if ($entryKey !== '') {
                $row[$entryKey] = $this->num($summary[$entryKey] ?? 0);
            }
        }

        $row = $this->mergeTrafficAdditionIntoNonTaxableCommuting($row, $summary);

        return $row;
    }

    /**
     * 非課税通勤費加算(traffic_addition)は行を分けず非課税通勤費(allowance_amo_6)に合算して
     * 表示する（給与明細と同じ扱い）。行自体はwage_ledger blade側のexcludedAllowanceKeysで
     * 非表示にする。賃金台帳一覧・個人賃金台帳の両方から呼ぶ、合算ルールの正本（2026-08-18、
     * 2箇所に同じ処理が別々に書かれていたのを統合）。
     *
     * @param array<string,mixed> $ledgerRow
     * @param array<string,mixed> $summary
     * @return array<string,mixed>
     */
    private function mergeTrafficAdditionIntoNonTaxableCommuting(array $ledgerRow, array $summary): array
    {
        if (array_key_exists('allowance_amo_6', $ledgerRow)) {
            $ledgerRow['allowance_amo_6'] += $this->num($summary['traffic_addition'] ?? 0);
        }

        return $ledgerRow;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function normalizePayrollSummaryRow(array $row): array
    {
        $aliases = [
            'work_holiday_num' => 'work_horiday_num',
            'holiday_true' => 'horiday_true',
            'holiday_true_num' => 'horiday_true_num',
        ];

        foreach ($aliases as $canonical => $legacy) {
            if (!array_key_exists($canonical, $row) && array_key_exists($legacy, $row)) {
                $row[$canonical] = $row[$legacy];
            }
        }

        return $row;
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
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $paymentDate = trim((string) ($v['payment_date'] ?? ''));
        if ($paymentDate === '') {
            $paymentDate = sprintf('%04d-%02d-01', $year, $month);
        }
        if ($this->isBonusLocked((string) $v['staff_id'], $paymentDate)) {
            return response()->json([
                'ok' => false,
                'message' => '賞与確定済のため変更できません。',
            ], 409);
        }

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
        $updated = $this->calculationFlowService->recalculateMonthly(
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

    public function calcHomeVisitAllowance(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $result = $this->homeVisitAllowanceService->calculate(
            (string) $v['staff_id'],
            $year,
            $month,
            (string) ($v['company_id'] ?? '')
        );

        return response()->json(array_merge(['ok' => true], $result));
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
        if ($this->isBonusLocked((string) $v['staff_id'], $paymentDate)) {
            return response()->json([
                'ok' => false,
                'message' => '賞与確定済のため再計算できません。',
            ], 409);
        }

        $updated = $this->calculationFlowService->recalculateBonus(
            (string) $v['staff_id'],
            $year,
            $month,
            $paymentDate
        );

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function salesSummary(Request $request): JsonResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        [$salesYear, $salesMonth] = $this->previousYearMonth($year, $month);
        $company = trim((string) ($v['company_id'] ?? ''));
        $staffRows = $this->staffService->staffs('');
        $summaryMap = $this->summaryService->summaryMapByPaymentDate((string) $v['payment_date'], false);
        $staffMap = [];
        foreach ($staffRows as $staff) {
            $staffId = trim((string) ($staff['staff_id'] ?? ''));
            if ($staffId !== '') {
                $staffMap[$staffId] = $staff;
            }
        }
        $staffIds = array_column($staffRows, 'staff_id');
        $salesMap = $this->salesImportService->summaries($staffIds, $year, $month);
        $storeSalesMap = $this->storeSalesByStaff([], $salesYear, $salesMonth);

        $displayStaffIds = array_values(array_unique(array_merge($staffIds, array_keys($storeSalesMap))));
        sort($displayStaffIds);

        $rows = [];
        foreach ($displayStaffIds as $staffId) {
            $homeVisitSales = isset($salesMap[$staffId])
                ? $this->salesImportService->homeVisitSalesTotal($salesMap[$staffId])
                : 0.0;
            $storeSales = $this->num($storeSalesMap[$staffId] ?? 0);

            if (abs($homeVisitSales + $storeSales) < 0.0000001) {
                continue;
            }

            $staff = (array) ($staffMap[$staffId] ?? []);
            $summary = (array) ($summaryMap[$staffId] ?? []);

            $rows[] = [
                'staff_id' => $staffId,
                'staff_name' => (string) ($staff['staff_name'] ?? ''),
                'has_payroll' => isset($summaryMap[$staffId]),
                'edit_lock' => (int) ($summary['edit_lock'] ?? 0),
                'sales' => $salesMap[$staffId] ?? null,
            ];
        }

        return response()->json([
            'ok' => true,
            'rows' => $rows,
        ]);
    }

    public function reflectSales(Request $request): JsonResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'staff_ids' => ['required', 'array'],
            'staff_ids.*' => ['string', 'max:20'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $company = trim((string) ($v['company_id'] ?? ''));
        $requestedStaffIds = array_values(array_filter(array_map(
            static fn($value): string => trim((string) $value),
            (array) $v['staff_ids']
        ), static fn(string $value): bool => $value !== ''));

        $summaryMap = $this->summaryService->summaryMapByPaymentDate((string) $v['payment_date'], false);
        $availableStaffIds = array_values(array_filter(
            $requestedStaffIds,
            static fn($staffId): bool => isset($summaryMap[$staffId])
        ));
        $staffIds = $availableStaffIds;

        $result = $this->salesImportService->reflect($staffIds, $year, $month, $company);

        return response()->json([
            'ok' => true,
            'updated' => (int) ($result['updated'] ?? 0),
            'locked' => (int) ($result['locked'] ?? 0),
            'missing' => (int) ($result['missing'] ?? 0),
            'no_data' => (int) ($result['no_data'] ?? 0),
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
        $updated += (int) $this->calculationFlowService->recalculateAfterAttendanceReflect((string) $v['staff_id'], $year, $month);

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
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->calculationFlowService->recalculateEmploymentInsurance(
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

    public function calcOvertimeDeduction(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->calculationFlowService->recalculateAmountsAfterInputChange(
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

    public function calcIncomeTax(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $result = $this->calculationFlowService->recalculateIncomeTaxWithTrace(
            (string) $v['staff_id'],
            $year,
            $month,
            (string) ($v['company_id'] ?? '')
        );

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

    public function bonusConfirm(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [(string) $v['staff_id']])
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [(string) $v['payment_date']])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no', 'edit_lock']);

        if (!$row) {
            return response()->json(['ok' => false, 'message' => '対象の賞与データがありません。'], 404);
        }

        $next = ((int) ($row->edit_lock ?? 0)) === 1 ? 0 : 1;

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update(['edit_lock' => $next]);

        return response()->json(['ok' => true, 'edit_lock' => $next]);
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
        $attendanceRecordExistsMap = $this->attendanceRecordExistsMap(
            [(string) $v['staff_id']],
            $year,
            $month,
            ''
        );
        $attendanceRecordExists = (bool) ($attendanceRecordExistsMap[(string) $v['staff_id']] ?? false);

        if ($checked && $attendanceRecordExists && !$attendanceChecked) {
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

        $attendanceRecordExistsMap = $this->attendanceRecordExistsMap(
            array_column($staffRows, 'staff_id'),
            $year,
            $month,
            $selectedCompanyId
        );
        [$attendanceYear, $attendanceMonth] = $month <= 1
            ? [$year - 1, 12]
            : [$year, $month - 1];
        $attendanceCheckedMap = $this->confirmedStateService->mapByStaffIds(
            array_column($staffRows, 'staff_id'),
            $attendanceYear,
            $attendanceMonth
        );

        $rows = array_map(static function (array $row) use ($attendanceRecordExistsMap, $attendanceCheckedMap): array {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $row['attendance_record_exists'] = (bool) ($attendanceRecordExistsMap[$staffId] ?? false);
            $row['summary'] = (array) ($row['summary'] ?? []);
            $row['summary']['attendance_checked'] = !empty($attendanceCheckedMap[$staffId]) ? 1 : 0;
            return $row;
        }, $rows);

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

    /** @param list<string> $staffIds @return array<string,bool> */
    private function attendanceRecordExistsMap(array $staffIds, int $year, int $month, string $company): array
    {
        $staffIds = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $staffIds
        ), static fn (string $value): bool => $value !== ''));

        if ($staffIds === []) {
            return [];
        }

        [$attendanceYear, $attendanceMonth] = $month <= 1
            ? [$year - 1, 12]
            : [$year, $month - 1];

        $fromDate = sprintf('%04d-%02d-01', $attendanceYear, $attendanceMonth);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $attendanceStaffRows = $this->attendanceStaffService->staffs($fromDate, $toDate, $company);

        $existsMap = [];
        foreach ($staffIds as $staffId) {
            $existsMap[$staffId] = false;
        }

        foreach ($attendanceStaffRows as $attendanceStaffRow) {
            $staffId = trim((string) ($attendanceStaffRow['staff_id'] ?? ''));
            if ($staffId === '' || !array_key_exists($staffId, $existsMap)) {
                continue;
            }

            $existsMap[$staffId] = !empty($attendanceStaffRow['time_card_keys']);
        }

        return $existsMap;
    }

    /** @param list<array<string, mixed>> $rows */
    private function previousYearMonth(int $year, int $month): array
    {
        if ($month <= 1) {
            return [$year - 1, 12];
        }

        return [$year, $month - 1];
    }

    /** @param list<array<string, mixed>> $rows */

    /** @return list<array<string,mixed>> */
    private function storeSalesByStaff(array $staffIds, int $salesYear, int $salesMonth): array
    {
        $staffIds = array_values(array_filter(array_map(
            static fn($value): string => trim((string) $value),
            $staffIds
        ), static fn(string $value): bool => $value !== ''));

        $rows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as p')
            ->join('dbo.T_先生別日報 as d', 'p.患者No', '=', 'd.患者No_t')
            ->selectRaw("
                LTRIM(RTRIM(CAST(d.[担当者ID] as nvarchar(20)))) as staff_id,
                SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'さくら鍼灸整骨院' THEN ISNULL(d.[自費], 0) ELSE 0 END) as sakura_private,
                SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'さくら鍼灸整骨院' THEN ISNULL(d.[請求金額], 0) ELSE 0 END) as sakura_insurance,
                SUM(CASE WHEN (ISNULL(d.[請求金額], 0) + ISNULL(d.[保険負担], 0)) <> 0 AND p.[店舗] = N'さくら鍼灸整骨院' THEN 1 ELSE 0 END) as sakura_insurance_count,
                SUM(CASE WHEN p.[割合] = N'自' AND p.[店舗] = N'さくら鍼灸整骨院' THEN 1 ELSE 0 END) as sakura_private_count,
                SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'ひなた鍼灸整骨院' THEN ISNULL(d.[自費], 0) ELSE 0 END) as hinata_private,
                SUM(CASE WHEN d.[先生別外] = 0 AND p.[店舗] = N'ひなた鍼灸整骨院' THEN ISNULL(d.[請求金額], 0) ELSE 0 END) as hinata_insurance,
                SUM(CASE WHEN (ISNULL(d.[請求金額], 0) + ISNULL(d.[保険負担], 0)) <> 0 AND p.[店舗] = N'ひなた鍼灸整骨院' THEN 1 ELSE 0 END) as hinata_insurance_count,
                SUM(CASE WHEN p.[割合] = N'自' AND p.[店舗] = N'ひなた鍼灸整骨院' THEN 1 ELSE 0 END) as hinata_private_count
            ")
            ->whereRaw('YEAR(p.[日付]) = ?', [$salesYear])
            ->whereRaw('MONTH(p.[日付]) = ?', [$salesMonth]);

        if ($staffIds !== []) {
            $rows->whereIn(DB::raw('LTRIM(RTRIM(CAST(d.[担当者ID] as nvarchar(20))))'), $staffIds);
        }

        $rows = $rows
            ->groupByRaw('LTRIM(RTRIM(CAST(d.[担当者ID] as nvarchar(20))))')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->staff_id ?? ''));
            if ($staffId !== '') {
                $map[$staffId] =
                    $this->num($row->sakura_private ?? 0)
                    + $this->num($row->sakura_insurance ?? 0)
                    + $this->num($row->hinata_private ?? 0)
                    + $this->num($row->hinata_insurance ?? 0);
            }
        }

        return $map;
    }

    /** @return array{0:int,1:int} */

    private function allStaffOptions(): array
    {
        return $this->staffService->staffs('');
    }

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
            ->select(['kyuyo_sho_no', 'kyuyo_staff_id', 'supply_month', 'bonus_amo'])
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
                'kyuyo_sho_no' => (int) ($historyRow->kyuyo_sho_no ?? 0),
                'date' => date('Y-m-d', $paymentTs),
                'gross' => $gross,
                'standard' => $standard,
            ];
        }

        foreach ($rows as &$row) {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $summary = (array) ($row['summary'] ?? []);
            $currentRowId = (int) ($summary['kyuyo_sho_no'] ?? 0);
            $currentGross = $this->num($summary['bonus_amo'] ?? 0);
            $currentStandard = floor($currentGross / 1000) * 1000;

            $ownHistory = array_values(array_filter(
                $historyMap[$staffId] ?? [],
                static fn (array $history): bool => (int) ($history['kyuyo_sho_no'] ?? 0) !== $currentRowId
            ));

            // 上限判定（区分Ⅰと同じで、金額表を持つ側が正本）はPayrollV2BonusSocialInsuranceService
            // ::computeTargetStandards()に合わせる。以前はここで別の式（自分より前の日付だけを
            // 合算する、という違う境界条件）を独自に持っていた。
            $targets = \App\Services\Admin\V2\Payroll\PayrollV2BonusSocialInsuranceService::computeTargetStandards(
                array_map(static fn (array $history): array => [
                    'supply_month' => $history['date'],
                    'bonus_amo' => $history['gross'],
                ], $ownHistory),
                $selectedPaymentDate,
                $currentStandard
            );
            $kenpoTargetStandard = $targets['kenpo_target_standard'];
            $kounenTargetStandard = $targets['kounen_target_standard'];

            // 以下は画面の内訳表示専用（この賞与より前の分だけの参考累計）。上限判定そのものには使わない。
            $sameMonthPrior = 0.0;
            $sameMonthOtherGross = 0.0;
            $fiscalPrior = 0.0;

            foreach ($ownHistory as $history) {
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

            $fiscalAfter = $fiscalPrior + $currentStandard;
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

    /** @param array<string,mixed> $row */
    private function shouldIncludeWageLedgerRow(array $row): bool
    {
        return trim((string) ($row['division'] ?? '')) !== '業務委託';
    }

    private function isBonusLocked(string $staffId, string $paymentDate): bool
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['edit_lock']);

        return ((int) ($row->edit_lock ?? 0)) === 1;
    }

    /** @param array<string,mixed> $values */
    private function updateBonusSummary(string $staffId, int $year, int $month, array $values): int
    {
        return $this->updateService->saveBonus($staffId, $year, $month, $values);
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
