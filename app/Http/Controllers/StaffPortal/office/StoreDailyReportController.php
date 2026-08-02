<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreDailyReportController extends Controller
{
    use HandlesStaffPortalContext;

    private const DAILY_SUMMARY_MONTHLY_CLOSING_AUTHORITY = '日報';
    private const DAILY_SUMMARY_MONTHLY_INPUT_AUTHORITY = '日報入力';

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        return view('staff_portal.office.store_daily_report.index', $this->commonViewData($request, []));
    }

    // 日報集計
    public function dailySummary(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }
        $staffRow = $this->staffPortalStaffRow($staffId);
        $is_admin = $this->isAdmin($staffRow);


        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        [$targetYear, $targetMonthNo] = $this->parseTargetMonth($targetMonth);
        $selectedStore = trim((string) $request->query('日報集計店舗', ''));
        $selectedPrintType = trim((string) $request->query('print_type', 'monthly_daily_report'));
        $targetMonthStart = sprintf('%04d-%02d-01', $targetYear, $targetMonthNo);
        $targetMonthEnd = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));
        $targetMonthStartDate = Carbon::createFromFormat('Y-m-d', $targetMonthStart)->startOfDay();
        $monthlyClosingRow = $this->dailySummaryMonthlyClosingRow($targetMonthStartDate);
        $storeOptions = $this->storeDailyReportStoreOptions();

        $rowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->select([
                '日報集計No',
                '日付',
                'レセコン',
                '先生別計',
                '差額',
                '交通事故',
                '備考',
                'レジ',
                '銀行預入金額',
                'カード',
                '来院人数',
                '予約人数',
                '院内人数',
                '確定',
                '確定日',
                '日報集計店舗',
                '誤差チェック',
            ])
            ->where('日付', '>=', $targetMonthStart)
            ->where('日付', '<', $targetMonthEnd);

        if ($selectedStore !== '') {
            $rowsQuery->where('日報集計店舗', $selectedStore);
        }

        $dailySummaryRows = $rowsQuery
            ->orderByDesc('日付')
            ->get()
            ->map(fn($row): array => [
                'daily_summary_id' => (string) ($row->{'日報集計No'} ?? ''),
                '日報集計No' => (string) ($row->{'日報集計No'} ?? ''),
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                'レセコン' => $this->formatMoneyValue($row->{'レセコン'} ?? null),
                '先生別計' => $this->formatMoneyValue($row->{'先生別計'} ?? null),
                '差額' => $this->formatMoneyValue($row->{'差額'} ?? null),
                '交通事故' => $this->formatMoneyValue($row->{'交通事故'} ?? null),
                '備考' => trim((string) ($row->{'備考'} ?? '')),
                'レジ' => $this->formatMoneyValue($row->{'レジ'} ?? null),
                '銀行預入金額' => $this->formatMoneyValue($row->{'銀行預入金額'} ?? null),
                '来院人数' => $this->formatNumberValue($row->{'来院人数'} ?? null),
                '予約人数' => $this->formatNumberValue($row->{'予約人数'} ?? null),
                '院内人数' => $this->formatNumberValue($row->{'院内人数'} ?? null),
                '確定' => (bool) ($row->{'確定'} ?? false) ? '済' : '',
                '確定日' => $this->formatDateValue($row->{'確定日'} ?? null, 'Y/m/d H:i'),
                '日報集計店舗' => trim((string) ($row->{'日報集計店舗'} ?? '')),
                '誤差チェック' => $this->formatMoneyValue($row->{'誤差チェック'} ?? null),
            ])
            ->all();

        $targetMonthNextDate = $targetMonthStartDate->copy()->addMonthNoOverflow();
        $monthlyInputRow = $this->dailySummaryMonthlyInputRow($targetMonthStartDate);
        $autoInsuranceBurdenTotals = $this->dailySummaryMonthlyWindowInsuranceTotals(
            $targetMonthStartDate,
            $targetMonthNextDate
        );
        $sakuraInsuranceBurdenTotal = $this->dailySummaryStoreTotalByName($autoInsuranceBurdenTotals, 'さくら');
        $hinataInsuranceBurdenTotal = $this->dailySummaryStoreTotalByName($autoInsuranceBurdenTotals, 'ひなた');
        $monthlyWindowSourceRow = $monthlyClosingRow ?? $monthlyInputRow;

        $monthlyWindowInput = [
            'closing_month' => $targetMonthStartDate->format('Y/m/d'),
            'processed_at' => $this->formatDateValue($monthlyWindowSourceRow?->processed_at ?? now(), 'Y/m/d'),
            'sakura_receipt_burden_amount' => $this->formatMoneyValue($monthlyWindowSourceRow?->sakura_receipt_burden_amount ?? $request->query('sakura_receipt_burden_amount')),
            'hinata_receipt_burden_amount' => $this->formatMoneyValue($monthlyWindowSourceRow?->hinata_receipt_burden_amount ?? $request->query('hinata_receipt_burden_amount')),
            'sakura_insurance_burden_total' => $this->formatMoneyValue($monthlyWindowSourceRow?->sakura_insurance_burden_total ?? $sakuraInsuranceBurdenTotal),
            'hinata_insurance_burden_total' => $this->formatMoneyValue($monthlyWindowSourceRow?->hinata_insurance_burden_total ?? $hinataInsuranceBurdenTotal),
        ];

        return view('staff_portal.office.store_daily_report.daily_summary.index', $this->commonViewData($request, [
            'targetMonth' => sprintf('%04d-%02d', $targetYear, $targetMonthNo),
            'selectedStore' => $selectedStore,
            'selectedPrintType' => $selectedPrintType,
            'isDailySummaryMonthlyClosed' => $monthlyClosingRow !== null,
            'dailySummaryMonthlyClosingProcessedAt' => $this->formatDateValue($monthlyClosingRow?->processed_at, 'Y/m/d'),
            'storeOptions' => $storeOptions,
            'dailySummaryRows' => $dailySummaryRows,
            'monthlyWindowInput' => $monthlyWindowInput,
            'is_admin' => $is_admin,

        ]));
    }

    public function dailySummaryMonthlyPrint(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        [$targetYear, $targetMonthNo] = $this->parseTargetMonth($targetMonth);
        $selectedStore = trim((string) $request->query('日報集計店舗', ''));
        if ($selectedStore === '') {
            return redirect()->route('office.store_daily_report.daily_summary', [
                'target_month' => sprintf('%04d-%02d', $targetYear, $targetMonthNo),
            ])->with('errorMessage', '店舗を選択してから印刷してください。');
        }

        $printType = trim((string) $request->query('print_type', 'monthly_daily_report'));
        $targetMonthStart = sprintf('%04d-%02d-01', $targetYear, $targetMonthNo);
        $targetMonthEnd = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));

        $viewName = match ($printType) {
            'monthly_window_report' => 'monthly_window_print',
            'teacher_monthly_report' => 'teacher_monthly_print',
            'teacher_daily_report' => 'teacher_daily_print',
            default => 'monthly_print',
        };

        $viewData = match ($printType) {
            'monthly_window_report' => $this->buildMonthlyWindowPrintData($targetMonthStart, $targetMonthEnd, $selectedStore, [
                'sakura' => $this->moneyToDatabaseValue($request->query('sakura_receipt_burden_amount')),
                'hinata' => $this->moneyToDatabaseValue($request->query('hinata_receipt_burden_amount')),
            ]),
            'teacher_monthly_report' => $this->buildTeacherMonthlyPrintData($targetMonthStart, $targetMonthEnd, $selectedStore),
            'teacher_daily_report' => $this->buildTeacherDailyPrintData($targetMonthStart, $targetMonthEnd, $selectedStore),
            default => $this->buildMonthlyDailyPrintData($targetMonthStart, $targetMonthEnd, $selectedStore),
        };

        return view('staff_portal.office.store_daily_report.daily_summary.' . $viewName, $this->commonViewData($request, [
            'targetMonth' => sprintf('%04d-%02d', $targetYear, $targetMonthNo),
            'selectedStore' => $selectedStore,
            'printType' => $printType,
        ] + $viewData));
    }

    // 修正依頼
    public function requestHistory(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $selectedStatus = $request->has('status_filter')
            ? trim((string) $request->query('status_filter', ''))
            : trim((string) $request->query('状況', '依頼中'));
        $requestHistoryQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_依頼履歴')
            ->select([
                '状況',
                '対象日',
                '内容',
                '依頼日',
                '依頼者',
                '店舗',
                '事務所コメント',
                // '店舗非表示・事務所備忘用',
            ]);

        if ($selectedStatus !== '') {
            $requestHistoryQuery->where('状況', $selectedStatus);
        }

        $requestHistoryRows = $requestHistoryQuery
            ->orderByDesc('依頼日')
            ->get()
            ->map(fn($row): array => [
                '状況' => trim((string) ($row->{'状況'} ?? '')),
                '対象日' => $this->formatDateValue($row->{'対象日'} ?? null, 'Y/m/d'),
                'original_対象日' => $this->formatDateValue($row->{'対象日'} ?? null, 'Y-m-d'),
                '内容' => trim((string) ($row->{'内容'} ?? '')),
                '依頼日' => $this->formatDateValue($row->{'依頼日'} ?? null, 'Y/m/d H:i'),
                'original_依頼日' => $this->formatDateValue($row->{'依頼日'} ?? null, 'Y-m-d H:i:s'),
                '依頼者' => trim((string) ($row->{'依頼者'} ?? '')),
                '店舗' => trim((string) ($row->{'店舗'} ?? '')),
                '事務所コメント' => trim((string) ($row->{'事務所コメント'} ?? '')),
                // '店舗非表示・事務所備忘用' => trim((string) ($row->{'店舗非表示・事務所備忘用'} ?? '')),
            ])
            ->all();

        return view('staff_portal.office.store_daily_report.request_history.index', $this->commonViewData($request, [
            'selectedStatus' => $selectedStatus,
            'requestHistoryRows' => $requestHistoryRows,
        ]));
    }

    // 返戻ノート
    public function returnNote(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetYear = (int) $request->query('target_year', now()->format('Y'));
        if ($targetYear < 2000 || $targetYear > 2100) {
            $targetYear = (int) now()->format('Y');
        }
        $selectedStore = trim((string) $request->query('返戻店舗', ''));
        $paymentFilter = trim((string) $request->query('payment_filter', ''));
        $targetYearStart = sprintf('%04d-01-01', $targetYear);
        $targetYearEnd = sprintf('%04d-01-01', $targetYear + 1);
        $storeOptions = $this->storeDailyReportStoreOptions();

        $returnNoteQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_返戻')
            ->select([
                'henrei_no',
                '患者名',
                '振分',
                '本人区分',
                '返戻先保険者',
                '施術月',
                '提出月',
                '返戻理由',
                '金額',
                '通知書',
                '入金日',
                '振込先',
                '返戻店舗',
                '返戻備考',
                '事務所備考',
            ])
            ->where('振分', '返戻')
            ->where('施術月', '>=', $targetYearStart)
            ->where('施術月', '<', $targetYearEnd);

        if ($selectedStore !== '') {
            $returnNoteQuery->where('返戻店舗', $selectedStore);
        }

        if ($paymentFilter === 'unpaid') {
            $returnNoteQuery->whereNull('入金日');
        } elseif ($paymentFilter === 'paid') {
            $returnNoteQuery->whereNotNull('入金日');
        }

        $returnNoteRows = $returnNoteQuery
            ->orderByDesc('施術月')
            ->get()
            ->map(fn($row): array => [
                'henrei_no' => trim((string) ($row->henrei_no ?? '')),
                '患者名' => trim((string) ($row->{'患者名'} ?? '')),
                '振分' => trim((string) ($row->{'振分'} ?? '')),
                '本人区分' => trim((string) ($row->{'本人区分'} ?? '')),
                '返戻先保険者' => trim((string) ($row->{'返戻先保険者'} ?? '')),
                '施術月' => $this->formatDateValue($row->{'施術月'} ?? null, 'Y/m'),
                '提出月' => $this->formatDateValue($row->{'提出月'} ?? null, 'Y/m'),
                '返戻理由' => trim((string) ($row->{'返戻理由'} ?? '')),
                '金額' => $this->formatMoneyValue($row->{'金額'} ?? null),
                '通知書' => trim((string) ($row->{'通知書'} ?? '')),
                '入金日' => $this->formatDateValue($row->{'入金日'} ?? null, 'Y/m/d'),
                'original_入金日' => $this->formatDateValue($row->{'入金日'} ?? null, 'Y-m-d'),
                '振込先' => trim((string) ($row->{'振込先'} ?? '')),
                '返戻店舗' => trim((string) ($row->{'返戻店舗'} ?? '')),
                '返戻備考' => trim((string) ($row->{'返戻備考'} ?? '')),
                '事務所備考' => trim((string) ($row->{'事務所備考'} ?? '')),
            ])
            ->all();

        return view('staff_portal.office.store_daily_report.return_note.index', $this->commonViewData($request, [
            'targetYear' => (string) $targetYear,
            'selectedStore' => $selectedStore,
            'paymentFilter' => $paymentFilter,
            'storeOptions' => $storeOptions,
            'returnNoteRows' => $returnNoteRows,
        ]));
    }

    public function saveReturnNote(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $henrei_no = trim((string) $request->input('henrei_no', ''));
        $入金日 = $this->normalizeDateValue($request->input('入金日'));
        $事務所備考 = trim((string) $request->input('事務所備考', ''));

        $redirectQuery = [
            'target_year' => trim((string) $request->input('target_year', '')),
            '返戻店舗' => trim((string) $request->input('返戻店舗', '')),
            'payment_filter' => trim((string) $request->input('payment_filter', '')),
        ];

        if ($henrei_no === '') {
            return redirect()
                ->route('office.store_daily_report.return_note', $redirectQuery)
                ->with('errorMessage', '保存対象が確認できません。');
        }

        DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_返戻')
            ->where('henrei_no', $henrei_no)
            ->update([
                '入金日' => $入金日,
                '事務所備考' => $事務所備考 === '' ? null : $事務所備考,
            ]);

        return redirect()
            ->route('office.store_daily_report.return_note', $redirectQuery)
            ->with('statusMessage', '保存しました。');
    }

    // その他一覧
    public function otherList(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        [$targetYear, $targetMonthNo] = $this->parseTargetMonth($targetMonth);
        $selectedStore = trim((string) $request->query('レジ店舗', ''));
        $visibilityFilter = trim((string) $request->query('visibility_filter', ''));
        $targetMonthStart = sprintf('%04d-%02d-01', $targetYear, $targetMonthNo);
        $targetMonthEnd = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));
        $storeOptions = $this->storeDailyReportStoreOptions();

        $otherListQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->select([
                '項目',
                '品名',
                '金額',
                '日付',
                'レジＮｏ',
                'レジ店舗',
                '金額1',
                '非表示ch',
            ])
            ->where('日付', '>=', $targetMonthStart)
            ->where('日付', '<', $targetMonthEnd);

        if ($selectedStore !== '') {
            $otherListQuery->where('レジ店舗', $selectedStore);
        }

        if ($visibilityFilter === 'hidden') {
            $otherListQuery->where('非表示ch', 1);
        } elseif ($visibilityFilter === 'visible') {
            $otherListQuery->where(function ($query): void {
                $query->whereNull('非表示ch')
                    ->orWhere('非表示ch', 0);
            });
        }

        $otherListRows = $otherListQuery
            ->orderBy('項目')
            ->orderBy('日付')
            ->orderBy('レジＮｏ')
            ->get()
            ->map(fn($row): array => [
                '項目' => trim((string) ($row->{'項目'} ?? '')),
                '品名' => trim((string) ($row->{'品名'} ?? '')),
                '金額' => $this->formatMoneyValue($row->{'金額'} ?? null),
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                'レジＮｏ' => trim((string) ($row->{'レジＮｏ'} ?? '')),
                'レジ店舗' => trim((string) ($row->{'レジ店舗'} ?? '')),
                '金額1' => $this->formatMoneyValue($row->{'金額1'} ?? null),
                '非表示ch' => (bool) ($row->{'非表示ch'} ?? false) ? '非表示' : '',
            ])
            ->all();

        return view('staff_portal.office.store_daily_report.other_list.index', $this->commonViewData($request, [
            'targetMonth' => sprintf('%04d-%02d', $targetYear, $targetMonthNo),
            'selectedStore' => $selectedStore,
            'visibilityFilter' => $visibilityFilter,
            'storeOptions' => $storeOptions,
            'otherListRows' => $otherListRows,
        ]));
    }

    public function otherListPrint(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        [$targetYear, $targetMonthNo] = $this->parseTargetMonth($targetMonth);
        $selectedStore = trim((string) $request->query('レジ店舗', ''));
        $targetMonthStart = sprintf('%04d-%02d-01', $targetYear, $targetMonthNo);
        $targetMonthEnd = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));

        $printRowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->select([
                '項目',
                '品名',
                '金額',
                '日付',
                'レジＮｏ',
                'レジ店舗',
                '金額1',
                '非表示ch',
            ])
            ->where('日付', '>=', $targetMonthStart)
            ->where('日付', '<', $targetMonthEnd)
            ->where(function ($query): void {
                $query->whereNull('非表示ch')
                    ->orWhere('非表示ch', 0);
            });

        if ($selectedStore !== '') {
            $printRowsQuery->where('レジ店舗', $selectedStore);
        }

        $printRows = $printRowsQuery
            ->orderBy('項目')
            ->orderBy('日付')
            ->orderBy('レジＮｏ')
            ->get()
            ->map(fn($row): array => [
                '項目' => trim((string) ($row->{'項目'} ?? '')),
                '品名' => trim((string) ($row->{'品名'} ?? '')),
                '金額' => $this->formatMoneyValue($row->{'金額'} ?? null),
                'raw_金額' => (float) ($row->{'金額'} ?? 0),
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                'レジ店舗' => trim((string) ($row->{'レジ店舗'} ?? '')),
            ])
            ->all();

        return view('staff_portal.office.store_daily_report.other_list.print', $this->commonViewData($request, [
            'targetMonth' => sprintf('%04d-%02d', $targetYear, $targetMonthNo),
            'selectedStore' => $selectedStore,
            'printRows' => $printRows,
            'totalAmount' => collect($printRows)->sum('raw_金額'),
        ]));
    }

    // 項目別集計
    public function itemSummary(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        [$targetYear, $targetMonthNo] = $this->parseTargetMonth($targetMonth);
        $selectedStore = trim((string) $request->query('店舗', ''));
        $targetMonthStart = sprintf('%04d-%02d-01', $targetYear, $targetMonthNo);
        $targetMonthEnd = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));
        $storeOptions = $this->storeDailyReportStoreOptions();

        $itemSummaryQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->join('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                'patient.患者No',
                'patient.日別順',
                'patient.日付',
                'patient.患者名',
                'patient.保険証',
                'patient.回収日',
                'patient.店舗',
                'teacher.メニュー',
                'teacher.自費',
                'teacher.保険負担',
                'teacher.請求金額',
                'teacher.レセ差額',
                'teacher.先生別No',
                'teacher.項目',
            ])
            ->where('patient.日付', '>=', $targetMonthStart)
            ->where('patient.日付', '<', $targetMonthEnd)
            ->where(function ($query): void {
                $query->whereIn('teacher.メニュー', ['割引', '交通事故', '物品販売'])
                    ->orWhere('patient.保険証', '<>', 0)
                    ->orWhere('teacher.項目', '物品販売');
            });

        if ($selectedStore !== '') {
            $itemSummaryQuery->where('patient.店舗', $selectedStore);
        }

        $itemSummaryRows = $itemSummaryQuery
            ->orderBy('patient.日付')
            ->orderBy('patient.日別順')
            ->orderBy('patient.患者名')
            ->get()
            ->map(fn($row): array => [
                '患者No' => trim((string) ($row->{'患者No'} ?? '')),
                '日別順' => trim((string) ($row->{'日別順'} ?? '')),
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                '患者名' => trim((string) ($row->{'患者名'} ?? '')),
                '保険証' => trim((string) ($row->{'保険証'} ?? '')),
                '回収日' => $this->formatDateValue($row->{'回収日'} ?? null, 'Y/m/d'),
                '店舗' => trim((string) ($row->{'店舗'} ?? '')),
                'メニュー' => trim((string) ($row->{'メニュー'} ?? '')),
                '自費' => $this->formatMoneyValue($row->{'自費'} ?? null),
                '保険負担' => $this->formatMoneyValue($row->{'保険負担'} ?? null),
                '請求金額' => $this->formatMoneyValue($row->{'請求金額'} ?? null),
                'レセ差額' => $this->formatMoneyValue($row->{'レセ差額'} ?? null),
                '先生別No' => trim((string) ($row->{'先生別No'} ?? '')),
                '項目' => trim((string) ($row->{'項目'} ?? '')),
            ])
            ->all();

        return view('staff_portal.office.store_daily_report.item_summary.index', $this->commonViewData($request, [
            'targetMonth' => sprintf('%04d-%02d', $targetYear, $targetMonthNo),
            'selectedStore' => $selectedStore,
            'storeOptions' => $storeOptions,
            'itemSummaryRows' => $itemSummaryRows,
        ]));
    }

    public function saveRequestHistory(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $対象日 = trim((string) $request->input('original_対象日', ''));
        $内容 = trim((string) $request->input('original_内容', ''));
        $依頼日 = trim((string) $request->input('original_依頼日', ''));
        $依頼者 = trim((string) $request->input('original_依頼者', ''));
        $店舗 = trim((string) $request->input('original_店舗', ''));
        $状況 = trim((string) $request->input('状況', ''));
        $事務所コメント = trim((string) $request->input('事務所コメント', ''));

        if ($対象日 === '' || $内容 === '' || $依頼日 === '' || $依頼者 === '' || $店舗 === '') {
            return redirect()
                ->route('office.store_daily_report.request_history')
                ->with('errorMessage', '保存対象が確認できません。');
        }

        DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_依頼履歴')
            ->whereDate('対象日', $対象日)
            ->where('内容', $内容)
            ->where('依頼者', $依頼者)
            ->where('店舗', $店舗)
            ->whereBetween('依頼日', [$依頼日, date('Y-m-d H:i:s', strtotime($依頼日) + 59)])
            ->update([
                '状況' => $状況 === '' ? null : $状況,
                '事務所コメント' => $事務所コメント === '' ? null : $事務所コメント,
            ]);

        return redirect()
            ->route('office.store_daily_report.request_history', ['status_filter' => $状況 !== '' ? $状況 : '依頼中'])
            ->with('statusMessage', '保存しました。');
    }

    public function dailySummaryDetail(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->query('daily_summary_id', ''));
        if ($dailySummaryId === '') {
            return redirect()->route('office.store_daily_report.daily_summary');
        }
        $dailySummaryRow = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->select([
                '日報集計No',
                '日付',
                'レセコン',
                '先生別計',
                '差額',
                '交通事故',
                '備考',
                'レジ',
                '銀行預入金額',
                '来院人数',
                '予約人数',
                '院内人数',
                '確定',
                '確定日',
                '日報集計店舗',
                '誤差チェック',
            ])
            ->where('日報集計No', $dailySummaryId)
            ->first();

        if ($dailySummaryRow === null) {
            return redirect()->route('office.store_daily_report.daily_summary');
        }

        $targetDate = $this->formatDateValue($dailySummaryRow->{'日付'} ?? null, 'Y-m-d');
        $targetStore = trim((string) ($dailySummaryRow->{'日報集計店舗'} ?? ''));
        $targetMonth = $targetDate !== '' ? substr($targetDate, 0, 7) : now()->format('Y-m');
        $targetMonthEnd = Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->endOfMonth()->startOfDay();
        $monthlyClosingRow = $this->dailySummaryMonthlyClosingRow($targetMonthEnd);
        $isDailySummaryMonthlyClosed = $monthlyClosingRow !== null;
        $filterStaffId = trim((string) $request->query('filter_staff_id', ''));
        $filterModifiedOnly = $request->boolean('filter_modified_only');
        $filterPatientName = trim((string) $request->query('filter_patient_name', ''));
        $nextDailyOrder = ((int) DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報')
            ->whereDate('日付', $targetDate)
            ->where('店舗', $targetStore)
            ->max('日別順')) + 1;

        $normalRowsQuery = $this->dailySummaryDetailBaseQuery()
            ->select($this->dailySummaryDetailSelects(false))
            ->whereDate('patient.日付', $targetDate)
            ->where('patient.店舗', $targetStore)
            ->groupByRaw($this->dailySummaryDetailGroupByColumns());

        $insuranceCardRowsQuery = $this->dailySummaryDetailBaseQuery()
            ->select($this->dailySummaryDetailSelects(true))
            ->whereDate('patient.回収日', $targetDate)
            ->where('patient.店舗', $targetStore)
            ->where('patient.保険証', 2)
            ->groupByRaw($this->dailySummaryDetailGroupByColumns());

        $dailyDetailRows = $normalRowsQuery
            ->union($insuranceCardRowsQuery)
            ->orderBy('sort_time')
            ->orderBy('日別順')
            ->get()
            ->map(fn($row): array => [
                'No' => trim((string) ($row->{'No'} ?? '')),
                '日別順' => trim((string) ($row->{'日別順'} ?? '')),
                '新患' => trim((string) ($row->{'新患'} ?? '')),
                '患者No' => trim((string) ($row->{'患者No'} ?? '')),
                '時刻' => $this->formatDateValue($row->{'時刻'} ?? null, 'H:i'),
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                '保険証' => trim((string) ($row->{'保険証'} ?? '')),
                '担当者ID' => trim((string) ($row->{'担当者ID'} ?? '')),
                '先生別No' => trim((string) ($row->{'先生別No'} ?? '')),
                'staff_display_name' => trim((string) ($row->staff_display_name ?? '')),
                '項目' => trim((string) ($row->{'項目'} ?? '')),
                '患者名' => trim((string) ($row->{'患者名'} ?? '')),
                'チャージ' => trim((string) ($row->charge ?? '')),
                'カード手数料' => $this->formatMoneyValue($row->{'カード手数料'} ?? null),
                '計算外' => trim((string) ($row->{'計算外'} ?? '')),
                '先生別外' => trim((string) ($row->{'先生別外'} ?? '')),
                'ch' => trim((string) ($row->{'ch'} ?? '')),
                'メニュー集計' => trim((string) ($row->{'メニュー集計'} ?? '')),
                '回数表示' => trim((string) ($row->{'回数表示'} ?? '')),
                '割合' => trim((string) ($row->{'割合'} ?? '')),
                '自費' => $this->formatMoneyValue($row->{'自費'} ?? null),
                '保険負担' => $this->formatMoneyValue($row->{'保険負担'} ?? null),
                '請求金額' => $this->formatMoneyValue($row->{'請求金額'} ?? null),
                '自費計' => $this->formatMoneyValue($row->{'自費計'} ?? null),
                '保険負担計' => $this->formatMoneyValue($row->{'保険負担計'} ?? null),
                'レセ差額' => $this->formatMoneyValue($row->{'レセ差額'} ?? null),
                '請求金額計' => $this->formatMoneyValue($row->{'請求金額計'} ?? null),
                '回収日' => $this->formatDateValue($row->{'回収日'} ?? null, 'Y/m/d'),
                'レセ負担金' => $this->formatMoneyValue($row->{'レセ負担金'} ?? null),
                '負担金ch' => trim((string) ($row->{'負担金ch'} ?? '')),
                '保険請求' => $this->formatMoneyValue($row->{'保険請求'} ?? null),
                '差額' => $this->formatMoneyValue($row->{'差額'} ?? null),
                '日報備考' => trim((string) ($row->{'日報備考'} ?? '')),
                '備考' => trim((string) ($row->{'備考'} ?? '')),
                '人数' => $this->formatNumberValue($row->{'人数'} ?? null),
            ])
            ->all();

        $dailyDetailRowsCollection = collect($dailyDetailRows);
        $moneyTextToFloat = static fn(mixed $value): float => (float) str_replace(',', '', trim((string) ($value ?? '')));
        $dailySummaryReceiptBurdenAmount = $dailyDetailRowsCollection
            ->groupBy(fn(array $row): string => trim((string) ($row['患者No'] ?? '')))
            ->sum(function ($rows) use ($moneyTextToFloat): float {
                $row = $rows->first() ?? [];

                return $moneyTextToFloat($row['レセ負担金'] ?? null);
            });
        $staffSummaryRows = $dailyDetailRowsCollection
            ->groupBy(fn(array $row): string => trim((string) ($row['担当者ID'] ?? '')))
            ->map(function ($rows, string $担当者ID) use ($moneyTextToFloat): array {
                $firstRow = $rows->first() ?? [];
                $staffDisplayName = trim((string) ($firstRow['staff_display_name'] ?? ''));

                return [
                    '担当者ID' => $担当者ID,
                    '担当者名' => $staffDisplayName !== '' ? $staffDisplayName : $担当者ID,
                    '件数' => $this->formatNumberValue($rows->count()),
                    '自費計' => $this->formatMoneyValue($rows->sum(fn(array $row): float => $moneyTextToFloat($row['自費計'] ?? null))),
                    '保険負担計' => $this->formatMoneyValue($rows->sum(fn(array $row): float => $moneyTextToFloat($row['保険負担計'] ?? null))),
                    'レセ差額' => $this->formatMoneyValue($rows->sum(fn(array $row): float => $moneyTextToFloat($row['レセ差額'] ?? null))),
                    '請求金額計' => $this->formatMoneyValue($rows->sum(fn(array $row): float => $moneyTextToFloat($row['請求金額計'] ?? null))),
                ];
            })
            ->filter(fn(array $row): bool => trim((string) ($row['担当者名'] ?? '')) !== '')
            ->values()
            ->all();

        $filterStaffOptions = $dailyDetailRowsCollection
            ->mapWithKeys(function (array $row): array {
                $staffId = trim((string) ($row['担当者ID'] ?? ''));
                $staffName = trim((string) ($row['staff_display_name'] ?? ''));

                return $staffId === '' ? [] : [$staffId => ($staffName !== '' ? $staffName : $staffId)];
            })
            ->all();

        if ($filterStaffId !== '') {
            $dailyDetailRowsCollection = $dailyDetailRowsCollection
                ->filter(fn(array $row): bool => trim((string) ($row['担当者ID'] ?? '')) === $filterStaffId)
                ->values();
        }

        if ($filterModifiedOnly) {
            $dailyDetailRowsCollection = $dailyDetailRowsCollection
                ->filter(fn(array $row): bool => (int) ($row['ch'] ?? 0) === 1)
                ->values();
        }

        if ($filterPatientName !== '') {
            $dailyDetailRowsCollection = $dailyDetailRowsCollection
                ->filter(fn(array $row): bool => mb_strpos((string) ($row['患者名'] ?? ''), $filterPatientName) !== false)
                ->values();
        }

        $patientSummaryRows = $dailyDetailRowsCollection
            ->values()
            ->all();

        $patientDetailRowsByPatientNo = $dailyDetailRowsCollection
            ->groupBy(fn(array $row): string => (string) ($row['患者No'] ?? ''))
            ->map(fn($rows): array => $rows->values()->all())
            ->all();

        $menuOptions = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_施術メニュー')
            ->select('メニュー')
            ->whereNotNull('メニュー')
            ->where('メニュー', '<>', '')
            ->orderBy('メニュー')
            ->get()
            ->map(fn($row): string => trim((string) ($row->{'メニュー'} ?? '')))
            ->filter(fn(string $メニュー): bool => $メニュー !== '')
            ->unique()
            ->values()
            ->all();

        $dailySummary = [
            '日報集計No' => (string) ($dailySummaryRow->{'日報集計No'} ?? ''),
            '日付' => $this->formatDateWithJapaneseWeekday($dailySummaryRow->{'日付'} ?? null),
            '日報集計店舗' => trim((string) ($dailySummaryRow->{'日報集計店舗'} ?? '')),
            '来院人数' => $this->formatNumberValue($dailySummaryRow->{'来院人数'} ?? null),
            '予約人数' => $this->formatNumberValue($dailySummaryRow->{'予約人数'} ?? null),
            '院内人数' => $this->formatNumberValue($dailySummaryRow->{'院内人数'} ?? null),
            'レセコン' => $this->formatMoneyValue($dailySummaryRow->{'レセコン'} ?? null),
            '先生別計' => $this->formatMoneyValue($dailySummaryRow->{'先生別計'} ?? null),
            '差額' => $this->formatMoneyValue($dailySummaryRow->{'差額'} ?? null),
            '交通事故' => $this->formatMoneyValue($dailySummaryRow->{'交通事故'} ?? null),
            'レジ' => $this->formatMoneyValue($dailySummaryRow->{'レジ'} ?? null),
            '銀行預入金額' => $this->formatMoneyValue($dailySummaryRow->{'銀行預入金額'} ?? null),
            'カード' => $this->formatMoneyValue($dailySummaryRow->{'カード'} ?? null),
            'レセ負担金' => $this->formatMoneyValue($dailySummaryReceiptBurdenAmount),
            '誤差チェック' => $this->formatMoneyValue($dailySummaryRow->{'誤差チェック'} ?? null),
            '確定' => (bool) ($dailySummaryRow->{'確定'} ?? false) ? '済' : '',
            '確定日' => $this->formatDateValue($dailySummaryRow->{'確定日'} ?? null, 'Y/m/d H:i'),
            '備考' => trim((string) ($dailySummaryRow->{'備考'} ?? '')),
        ];

        $dailySummaryExpenseRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->select([
                'レジＮｏ',
                '項目',
                '品名',
                '金額',
                '金額1',
                '非表示ch',
            ])
            ->whereDate('日付', $targetDate)
            ->where('レジ店舗', $targetStore)
            ->orderByDesc('レジＮｏ')
            ->get()
            ->map(fn($row): array => [
                'レジＮｏ' => trim((string) ($row->{'レジＮｏ'} ?? '')),
                '項目' => trim((string) ($row->{'項目'} ?? '')),
                '品名' => trim((string) ($row->{'品名'} ?? '')),
                '金額' => $this->formatMoneyValue($row->{'金額'} ?? null),
                '金額1' => $this->formatMoneyValue($row->{'金額1'} ?? null),
                '非表示ch' => (bool) ($row->{'非表示ch'} ?? false),
            ])
            ->all();

        return view('staff_portal.office.store_daily_report.daily_summary.detail', $this->commonViewData($request, [
            'dailySummary' => $dailySummary,
            'targetDate' => $targetDate,
            'targetMonth' => $targetMonth,
            'isDailySummaryMonthlyClosed' => $isDailySummaryMonthlyClosed,
            'dailySummaryMonthlyClosingProcessedAt' => $this->formatDateValue($monthlyClosingRow?->processed_at, 'Y/m/d H:i'),
            'nextDailyOrder' => $nextDailyOrder,
            'patientSummaryRows' => $patientSummaryRows,
            'patientDetailRowsByPatientNo' => $patientDetailRowsByPatientNo,
            'filterStaffOptions' => $filterStaffOptions,
            'filterStaffId' => $filterStaffId,
            'filterModifiedOnly' => $filterModifiedOnly,
            'filterPatientName' => $filterPatientName,
            'menuOptions' => $menuOptions,
            'staffSummaryRows' => $staffSummaryRows,
            'dailySummaryExpenseRows' => $dailySummaryExpenseRows,
        ]));
    }

    public function dailySummaryPrint(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->query('daily_summary_id', ''));
        if ($dailySummaryId === '') {
            return redirect()->route('office.store_daily_report.daily_summary');
        }

        $dailySummaryRow = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->select([
                '日報集計No',
                '日付',
                'レセコン',
                '先生別計',
                '差額',
                '交通事故',
                '備考',
                'レジ',
                '銀行預入金額',
                '来院人数',
                '予約人数',
                '院内人数',
                '確定',
                '確定日',
                '日報集計店舗',
                '誤差チェック',
            ])
            ->where('日報集計No', $dailySummaryId)
            ->first();

        if ($dailySummaryRow === null) {
            return redirect()->route('office.store_daily_report.daily_summary');
        }

        $targetDate = $this->formatDateValue($dailySummaryRow->{'日付'} ?? null, 'Y-m-d');
        $targetStore = trim((string) ($dailySummaryRow->{'日報集計店舗'} ?? ''));
        $printMode = trim((string) $request->query('print_mode', 'default'));
        $isModifiedPrint = $printMode === 'modified';
        $moneyTextToFloat = static fn(mixed $value): float => (float) str_replace(',', '', trim((string) ($value ?? '')));

        $normalRowsQuery = $this->dailySummaryDetailBaseQuery()
            ->select($this->dailySummaryDetailSelects(false, $isModifiedPrint))
            ->whereDate('patient.日付', $targetDate)
            ->where('patient.店舗', $targetStore)
            ->groupByRaw($this->dailySummaryDetailGroupByColumns());

        // 保険証忘れ回収分は、施術日ではなくレジ金が動いた回収日基準で当日の日報に載せる。
        $insuranceCardRowsQuery = $this->dailySummaryDetailBaseQuery()
            ->select($this->dailySummaryDetailSelects(true, $isModifiedPrint))
            ->whereDate('patient.回収日', $targetDate)
            ->where('patient.店舗', $targetStore)
            ->where('patient.保険証', 2)
            ->groupByRaw($this->dailySummaryDetailGroupByColumns());

        $printRows = $normalRowsQuery
            ->union($insuranceCardRowsQuery)
            ->orderBy('時刻')
            ->orderBy('患者名')
            ->orderBy('日別順')
            ->get()
            ->map(fn($row): array => [
                '日別順' => trim((string) ($row->{'日別順'} ?? '')),
                '新患' => trim((string) ($row->{'新患'} ?? '')),
                '患者No' => trim((string) ($row->{'患者No'} ?? '')),
                '時刻' => $this->formatDateValue($row->{'時刻'} ?? null, 'H:i'),
                '患者名' => trim((string) ($row->{'患者名'} ?? '')),
                '割合' => trim((string) ($row->{'割合'} ?? '')),
                'ch' => trim((string) ($row->{'ch'} ?? '')),
                'メニュー集計' => trim((string) ($row->{'メニュー集計'} ?? '')),
                '回数表示' => trim((string) ($row->{'回数表示'} ?? '')),
                '備考' => trim((string) ($row->{'備考'} ?? '')),
                'レセ負担金' => $this->formatMoneyValue($row->{'レセ負担金'} ?? null),
                '負担金ch' => trim((string) ($row->{'負担金ch'} ?? '')),
                '自費計' => $this->formatMoneyValue($row->{'自費計'} ?? null),
                '保険負担計' => $this->formatMoneyValue($row->{'保険負担計'} ?? null),
                'レセ差額' => $this->formatMoneyValue($row->{'レセ差額'} ?? null),
                '請求金額計' => $this->formatMoneyValue($row->{'請求金額計'} ?? null),
                'カード計' => $this->formatMoneyValue($row->{'カード計'} ?? null),
                '担当者' => trim((string) ($row->staff_display_name ?? '')),
                '項目' => trim((string) ($row->{'項目'} ?? '')),
                '計算外' => trim((string) ($row->{'計算外'} ?? '')),
                '差額' => $this->formatMoneyValue($row->{'差額'} ?? null),
                'charge' => trim((string) ($row->{'charge'} ?? '')),
                '保険証' => trim((string) ($row->{'保険証'} ?? '')),
                '回収日' => $this->formatDateValue($row->{'回収日'} ?? null, 'Y/m/d'),
                '日報備考' => trim((string) ($row->{'日報備考'} ?? '')),
            ])
            ->all();

        if ($isModifiedPrint) {
            $printRows = collect($printRows)
                ->filter(function (array $row) use ($moneyTextToFloat): bool {
                    if (trim((string) ($row['割合'] ?? '')) === '交') {
                        return false;
                    }

                    $total = $moneyTextToFloat($row['自費計'] ?? null)
                        + $moneyTextToFloat($row['保険負担計'] ?? null)
                        + $moneyTextToFloat($row['請求金額計'] ?? null);

                    return $total !== 0.0;
                })
                ->values()
                ->all();
        }

        $usedCopaymentPatientNos = [];
        $printRows = collect($printRows)
            ->map(function (array $row) use (&$usedCopaymentPatientNos): array {
                $patientNo = trim((string) ($row['患者No'] ?? ''));
                if ($patientNo === '') {
                    return $row;
                }

                if (isset($usedCopaymentPatientNos[$patientNo])) {
                    $row['レセ負担金'] = '0';
                    return $row;
                }

                $usedCopaymentPatientNos[$patientNo] = true;
                return $row;
            })
            ->all();

        $cashRegisterRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->select([
                '項目',
                '品名',
                '日付',
                'レジ店舗',
                '非表示ch',
                'レジＮｏ',
                DB::raw('SUM(金額) as 金額の合計'),
            ])
            ->whereDate('日付', $targetDate)
            ->where('レジ店舗', $targetStore)
            ->groupBy('項目', '品名', '日付', 'レジ店舗', '非表示ch', 'レジＮｏ')
            ->havingRaw('SUM(金額) <> 0')
            ->where(function ($query): void {
                $query->where('非表示ch', 0)
                    ->orWhere('項目', 'チャージ金');
            })
            ->orderByDesc('レジＮｏ')
            ->get()
            ->map(fn($row): array => [
                '項目' => trim((string) ($row->{'項目'} ?? '')),
                '品名' => trim((string) ($row->{'品名'} ?? '')),
                '金額の合計' => $this->formatMoneyValue($row->{'金額の合計'} ?? null),
            ])
            ->all();

        $sumMoney = static fn(array $rows, string $key): float => collect($rows)->sum(
            fn(array $row): float => (float) str_replace(',', '', (string) ($row[$key] ?? ''))
        );
        $privateTreatmentRows = collect($printRows)
            ->filter(fn(array $row): bool => $moneyTextToFloat($row['自費計'] ?? null) !== 0.0 && (int) ($row['計算外'] ?? 0) === 0)
            ->groupBy(fn(array $row): string => implode('|', [
                trim((string) ($row['メニュー集計'] ?? '')),
                trim((string) ($row['保険証'] ?? '')),
                trim((string) ($row['計算外'] ?? '')),
                trim((string) ($row['項目'] ?? '')),
                trim((string) ($row['charge'] ?? '')),
            ]))
            ->map(function ($rows): array {
                $firstRow = $rows->first() ?? [];
                $自費の合計 = $rows->sum(fn(array $row): float => (float) str_replace(',', '', (string) ($row['自費計'] ?? '')));

                return [
                    'メニュー' => trim((string) ($firstRow['メニュー集計'] ?? '')),
                    'メニューのカウント' => $this->formatNumberValue($rows->count()),
                    '自費の合計' => $this->formatMoneyValue($自費の合計),
                    'charge' => trim((string) ($firstRow['charge'] ?? '')),
                    'raw_自費の合計' => $自費の合計,
                ];
            })
            ->sortByDesc(fn(array $row): int => (int) ($row['charge'] ?? 0))
            ->values()
            ->all();


        $cashRegisterDeductionAmount = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->whereDate('日付', $targetDate)
            ->where('レジ店舗', $targetStore)
            ->where('項目', '<>', 'チャージ金')
            ->where('項目', '<>', '物品販売')
            ->sum('金額');

        $dailySalesAmount = $sumMoney($printRows, '保険負担計')
            + $sumMoney($printRows, '自費計')
            - (float) $cashRegisterDeductionAmount;

        $dailySummaryPeopleRow = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->join('dbo.T_患者名日報', '日報集計No', '=', '日報集計No_t')
            ->join('dbo.T_先生別日報', 'T_患者名日報.患者No', '=', 'T_先生別日報.患者No_t')
            ->whereDate('T_日報集計.日付', $targetDate)
            ->where('店舗', $targetStore)
            ->select([
                '予約人数',
                DB::raw('COUNT(DISTINCT CASE WHEN (請求金額 + 保険負担) <> 0 THEN T_患者名日報.患者No END) AS 保険人数'),
                DB::raw("COUNT(DISTINCT CASE WHEN 割合 = N'自'" . ($isModifiedPrint ? " AND ch = 0" : "") . " THEN T_患者名日報.患者No END) AS 自費人数"),
                DB::raw('COUNT(DISTINCT CASE WHEN 保険証 <> 0 THEN T_患者名日報.患者No END) AS 保険証忘れ'),
                DB::raw('COUNT(DISTINCT CASE WHEN 先生別外 = 0 THEN T_患者名日報.患者No END) AS 来院人数'),
                DB::raw("COUNT(DISTINCT CASE WHEN 割合 = N'交'" . ($isModifiedPrint ? " AND ch = 0" : "") . " THEN T_患者名日報.患者No END) AS 交通事故"),
                DB::raw("COUNT(DISTINCT CASE WHEN 割合 = N'交'" . ($isModifiedPrint ? " AND ch = 0" : "") . " THEN T_患者名日報.患者No END) AS 自賠責"),
                DB::raw("COUNT(DISTINCT CASE WHEN 割合 <> N'交' AND メニュー LIKE N'%交通事故%' AND 請求金額 <> 0" . ($isModifiedPrint ? " AND ch = 0" : "") . " THEN T_患者名日報.患者No END) AS 健康保険"),
            ])
            ->groupBy('T_日報集計.日付', '店舗', '予約人数')
            ->first();

        $dailySummaryPeopleTotals = [
            '予約人数' => $this->formatNumberValue($dailySummaryPeopleRow->{'予約人数'} ?? null),
            '来院人数' => $this->formatNumberValue($dailySummaryPeopleRow->{'来院人数'} ?? null),
            '自費人数' => $this->formatNumberValue($dailySummaryPeopleRow->{'自費人数'} ?? null),
            '保険人数' => $this->formatNumberValue($dailySummaryPeopleRow->{'保険人数'} ?? null),
            '保険証忘れ' => $this->formatNumberValue($dailySummaryPeopleRow->{'保険証忘れ'} ?? null),
            '交通事故' => $this->formatNumberValue($dailySummaryPeopleRow->{'交通事故'} ?? null),
            '自賠責' => $this->formatNumberValue($dailySummaryPeopleRow->{'自賠責'} ?? null),
            '健康保険' => $this->formatNumberValue($dailySummaryPeopleRow->{'健康保険'} ?? null),
        ];

        $dailySummary = [
            '日報集計No' => (string) ($dailySummaryRow->{'日報集計No'} ?? ''),
            '日付' => $this->formatDateWithJapaneseWeekday($dailySummaryRow->{'日付'} ?? null),
            '日報集計店舗' => trim((string) ($dailySummaryRow->{'日報集計店舗'} ?? '')),
            '来院人数' => $this->formatNumberValue($dailySummaryRow->{'来院人数'} ?? null),
            '予約人数' => $this->formatNumberValue($dailySummaryRow->{'予約人数'} ?? null),
            '院内人数' => $this->formatNumberValue($dailySummaryRow->{'院内人数'} ?? null),
            'レセコン' => $this->formatMoneyValue($dailySummaryRow->{'レセコン'} ?? null),
            '先生別計' => $this->formatMoneyValue($dailySummaryRow->{'先生別計'} ?? null),
            '差額' => $this->formatMoneyValue($dailySummaryRow->{'差額'} ?? null),
            '交通事故' => $this->formatMoneyValue($dailySummaryRow->{'交通事故'} ?? null),
            'レジ' => $this->formatMoneyValue($dailySummaryRow->{'レジ'} ?? null),
            '銀行預入金額' => $this->formatMoneyValue($dailySummaryRow->{'銀行預入金額'} ?? null),
            '誤差チェック' => $this->formatMoneyValue($dailySummaryRow->{'誤差チェック'} ?? null),
            '確定' => (bool) ($dailySummaryRow->{'確定'} ?? false) ? '済' : '',
            '確定日' => $this->formatDateValue($dailySummaryRow->{'確定日'} ?? null, 'Y/m/d H:i'),
            '備考' => trim((string) ($dailySummaryRow->{'備考'} ?? '')),
        ];

        return view('staff_portal.office.store_daily_report.daily_summary.print', $this->commonViewData($request, [
            'dailySummary' => $dailySummary,
            'printMode' => $printMode,
            'dailySummaryPeopleTotals' => $dailySummaryPeopleTotals,
            'printRows' => $printRows,
            'cashRegisterRows' => $cashRegisterRows,
            'privateTreatmentRows' => $privateTreatmentRows,
            'privateTreatmentTotal' => $this->formatMoneyValue(collect($privateTreatmentRows)->sum('raw_自費の合計')),
            'dailySalesAmount' => $this->formatMoneyValue($dailySalesAmount),
            'printTotals' => [
                '件数' => count($printRows),
                '自費計' => $this->formatMoneyValue($sumMoney($printRows, '自費計')),
                '保険負担計' => $this->formatMoneyValue($sumMoney($printRows, '保険負担計')),
                'レセ差額' => $this->formatMoneyValue($sumMoney($printRows, 'レセ差額')),
                '請求金額計' => $this->formatMoneyValue($sumMoney($printRows, '請求金額計')),
                'カード計' => $this->formatMoneyValue($sumMoney($printRows, 'カード計')),
            ],
        ]));
    }

    public function saveDailySummaryHeader(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->input('daily_summary_id', ''));
        if ($dailySummaryId === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary')
                ->with('errorMessage', '保存対象が確認できません。');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $isConfirmed = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->where('日報集計No', $dailySummaryId)
            ->value('確定');

        if ((bool) $isConfirmed && !$this->isPaymentCheck($staffRow)) {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '確定済みの日報は保存できません。');
        }

        DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->where('日報集計No', $dailySummaryId)
            ->update([
                '来院人数' => $this->integerToDatabaseValue($request->input('来院人数')),
                '予約人数' => $this->integerToDatabaseValue($request->input('予約人数')),
                '院内人数' => $this->integerToDatabaseValue($request->input('院内人数')),
                'レセコン' => $this->moneyToDatabaseValue($request->input('レセコン')),
                '先生別計' => $this->moneyToDatabaseValue($request->input('先生別計')),
                '差額' => $this->moneyToDatabaseValue($request->input('差額')),
                '交通事故' => $this->moneyToDatabaseValue($request->input('交通事故')),
                'レジ' => $this->moneyToDatabaseValue($request->input('レジ')),
                '銀行預入金額' => $this->moneyToDatabaseValue($request->input('銀行預入金額')),
                'カード' => $this->moneyToDatabaseValue($request->input('カード')),
                '誤差チェック' => $this->moneyToDatabaseValue($request->input('誤差チェック')),
                '備考' => $this->blankToNull($request->input('備考')),
            ]);

        return redirect()
            ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
            ->with('statusMessage', '保存しました。');
    }

    public function saveDailySummaryExpense(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->input('daily_summary_id', ''));
        if ($dailySummaryId === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary')
                ->with('errorMessage', '保存対象が確認できません。');
        }

        $dailySummaryRow = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->select(['日付', '日報集計店舗', '確定'])
            ->where('日報集計No', $dailySummaryId)
            ->first();

        if ($dailySummaryRow === null) {
            return redirect()
                ->route('office.store_daily_report.daily_summary')
                ->with('errorMessage', '保存対象が確認できません。');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $isPaymentCheckUser = $this->isPaymentCheck($staffRow);
        if ((bool) ($dailySummaryRow->{'確定'} ?? false) && !$isPaymentCheckUser) {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '確定済みの日報は保存できません。');
        }

        $targetDate = $this->normalizeDateValue($dailySummaryRow->{'日付'} ?? null);
        $targetStore = trim((string) ($dailySummaryRow->{'日報集計店舗'} ?? ''));
        if ($targetDate === null || $targetStore === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '日付または店舗が確認できません。');
        }

        $targetMonthEnd = Carbon::createFromFormat('Y-m-d', $targetDate)->endOfMonth()->startOfDay();
        if ($isPaymentCheckUser && $this->dailySummaryMonthlyClosingRow($targetMonthEnd) !== null) {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '月次処理済みの経費は保存できません。');
        }

        $allowedItems = ['返金', '未収金', '物品購入費', '本日回収金', 'チャージ金'];
        $expenseRows = $request->input('expense_rows', []);
        if (!is_array($expenseRows)) {
            $expenseRows = [];
        }

        $savedCount = 0;
        foreach ($expenseRows as $expenseRow) {
            if (!is_array($expenseRow)) {
                continue;
            }

            $item = trim((string) ($expenseRow['項目'] ?? ''));
            $name = trim((string) ($expenseRow['品名'] ?? ''));
            $amount = trim((string) ($expenseRow['金額'] ?? ''));
            $adjustedAmount = trim((string) ($expenseRow['金額1'] ?? ''));
            $registerNo = trim((string) ($expenseRow['レジＮｏ'] ?? ''));

            if (!empty($expenseRow['_delete'])) {
                if ($registerNo !== '') {
                    DB::connection('sqlsrv_dailyreport')
                        ->table('dbo.T_レジ詳細')
                        ->where('レジＮｏ', $registerNo)
                        ->whereDate('日付', $targetDate)
                        ->where('レジ店舗', $targetStore)
                        ->delete();

                    $savedCount++;
                }

                continue;
            }

            if ($item === '' && $name === '' && $amount === '' && $adjustedAmount === '') {
                continue;
            }

            if (!in_array($item, $allowedItems, true)) {
                return redirect()
                    ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                    ->with('errorMessage', '項目を選択してください。');
            }

            $payload = [
                '項目' => $item,
                '品名' => $this->blankToNull($name),
                '金額' => $this->moneyToDatabaseValue($amount),
                '日付' => $targetDate,
                'レジ店舗' => $targetStore,
                '金額1' => $this->moneyToDatabaseValue($adjustedAmount),
                '非表示ch' => $this->checkboxToDatabaseValue($expenseRow['非表示ch'] ?? null),
            ];

            if ($registerNo !== '') {
                DB::connection('sqlsrv_dailyreport')
                    ->table('dbo.T_レジ詳細')
                    ->where('レジＮｏ', $registerNo)
                    ->whereDate('日付', $targetDate)
                    ->where('レジ店舗', $targetStore)
                    ->update($payload);
            } else {
                DB::connection('sqlsrv_dailyreport')
                    ->table('dbo.T_レジ詳細')
                    ->insert($payload);
            }

            $savedCount++;
        }

        if ($savedCount === 0) {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '保存する経費がありません。');
        }

        return redirect()
            ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
            ->with('statusMessage', '経費を保存しました。');
    }

    public function saveMonthlyWindowInput(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
            'sakura_receipt_burden_amount' => ['nullable', 'string'],
            'hinata_receipt_burden_amount' => ['nullable', 'string'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', $data['target_month'] . '-01')->startOfDay();
        if ($this->dailySummaryMonthlyClosingRow($targetMonthStart) !== null) {
            return redirect()->route('office.store_daily_report.daily_summary', [
                'target_month' => $data['target_month'],
            ])->with('errorMessage', '月次処理済のため保存できません。');
        }

        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();
        $insuranceBurdenTotals = $this->dailySummaryMonthlyWindowInsuranceTotals($targetMonthStart, $targetMonthNext);

        DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->updateOrInsert(
                [
                    'closing_month' => $targetMonthStart->format('Y-m-d'),
                    'authority' => self::DAILY_SUMMARY_MONTHLY_INPUT_AUTHORITY,
                ],
                [
                    'processed_at' => now(),
                    'sakura_receipt_burden_amount' => $this->moneyToDatabaseValue($data['sakura_receipt_burden_amount'] ?? null),
                    'hinata_receipt_burden_amount' => $this->moneyToDatabaseValue($data['hinata_receipt_burden_amount'] ?? null),
                    'sakura_insurance_burden_total' => $this->dailySummaryStoreTotalByName($insuranceBurdenTotals, 'さくら'),
                    'hinata_insurance_burden_total' => $this->dailySummaryStoreTotalByName($insuranceBurdenTotals, 'ひなた'),
                ]
            );

        return redirect()->route('office.store_daily_report.daily_summary', [
            'target_month' => $data['target_month'],
        ])->with('statusMessage', '月間日報入力を保存しました。');
    }
    // 日報の月次処理を登録する
    public function closeDailySummaryMonthly(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $data = $request->validate([
            'daily_summary_id' => ['required', 'string'],
            'target_month' => ['required', 'date_format:Y-m'],
            'sakura_receipt_burden_amount' => ['nullable', 'string'],
            'hinata_receipt_burden_amount' => ['nullable', 'string'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', $data['target_month'] . '-01')->startOfDay();
        $targetMonth = $targetMonthStart->copy()->endOfMonth()->startOfDay();
        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();

        $monthlyClosingRow = $this->dailySummaryMonthlyClosingRow($targetMonthStart);
        $createdJournalCount = 0;
        $monthlyInputRow = $this->dailySummaryMonthlyInputRow($targetMonthStart);
        $insuranceBurdenTotals = $this->dailySummaryMonthlyWindowInsuranceTotals($targetMonthStart, $targetMonthNext);
        $monthlyClosingPayload = [
            'sakura_receipt_burden_amount' => $this->moneyToDatabaseValue($data['sakura_receipt_burden_amount'] ?? null)
                ?? ($monthlyInputRow !== null ? (float) ($monthlyInputRow->sakura_receipt_burden_amount ?? 0) : null),
            'hinata_receipt_burden_amount' => $this->moneyToDatabaseValue($data['hinata_receipt_burden_amount'] ?? null)
                ?? ($monthlyInputRow !== null ? (float) ($monthlyInputRow->hinata_receipt_burden_amount ?? 0) : null),
            'sakura_insurance_burden_total' => $this->dailySummaryStoreTotalByName($insuranceBurdenTotals, 'さくら'),
            'hinata_insurance_burden_total' => $this->dailySummaryStoreTotalByName($insuranceBurdenTotals, 'ひなた'),
        ];

        DB::connection('sqlsrv')->transaction(function () use ($targetMonthStart, $targetMonthNext, $targetMonth, $monthlyClosingRow, $monthlyClosingPayload, &$createdJournalCount): void {
            foreach ($this->dailySummaryMonthlyJournalPayloads($targetMonthStart, $targetMonthNext, $targetMonth, $monthlyClosingPayload) as $payload) {
                $journalExists = DB::connection('sqlsrv')
                    ->table('dbo.mx_journal_entries')
                    ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
                    ->where('summary_text', $payload['summary_text'])
                    ->where('debit_account_title', $payload['debit_account_title'])
                    ->where('debit_item_name', $payload['debit_item_name'])
                    ->where('credit_account_title', $payload['credit_account_title'])
                    ->where('credit_item_name', $payload['credit_item_name'])
                    ->where('credit_department_name', $payload['credit_department_name'])
                    ->exists();

                if ($journalExists) {
                    DB::connection('sqlsrv')
                        ->table('dbo.mx_journal_entries')
                        ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
                        ->where('summary_text', $payload['summary_text'])
                        ->where('debit_account_title', $payload['debit_account_title'])
                        ->where('debit_item_name', $payload['debit_item_name'])
                        ->where('credit_account_title', $payload['credit_account_title'])
                        ->where('credit_item_name', $payload['credit_item_name'])
                        ->where('credit_department_name', $payload['credit_department_name'])
                        ->update($payload);

                    continue;
                }

                DB::connection('sqlsrv')
                    ->table('dbo.mx_journal_entries')
                    ->insert($payload);
                $createdJournalCount++;
            }

            if ($monthlyClosingRow === null) {
                DB::connection('sqlsrv')
                    ->table('dbo.mx_monthly_closings')
                    ->insert($monthlyClosingPayload + [
                        'closing_month' => $targetMonthStart->format('Y-m-d'),
                        'authority' => self::DAILY_SUMMARY_MONTHLY_CLOSING_AUTHORITY,
                        'processed_at' => now(),
                    ]);
            }
        });

        $message = $monthlyClosingRow === null ? '月次処理が完了しました。' : '月次処理は既に完了しています。';
        if ($createdJournalCount > 0) {
            $message .= ' 売上仕訳を' . $createdJournalCount . '件作成しました。';
        }

        return redirect()->route('office.store_daily_report.daily_summary', [
            'target_month' => $data['target_month'],
        ])->with('statusMessage', $message);
    }

    public function unlockDailySummaryMonthly(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isAdmin($staffRow)) {
            abort(403);
        }

        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', $data['target_month'] . '-01')->startOfDay();
        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();

        $deleted = 0;
        $deletedJournalCount = 0;
        DB::connection('sqlsrv')->transaction(function () use ($targetMonthStart, $targetMonthNext, &$deleted, &$deletedJournalCount): void {
            $deletedJournalCount = $this->deleteDailySummaryMonthlyJournalEntries($targetMonthStart);
            $deleted = DB::connection('sqlsrv')
                ->table('dbo.mx_monthly_closings')
                ->where('closing_month', '>=', $targetMonthStart->format('Y-m-d'))
                ->where('closing_month', '<', $targetMonthNext->format('Y-m-d'))
                ->where('authority', self::DAILY_SUMMARY_MONTHLY_CLOSING_AUTHORITY)
                ->delete();
        });

        $message = $deleted > 0 ? '店舗月次処理を解除しました。' : '解除対象の店舗月次処理がありませんでした。';
        if ($deletedJournalCount > 0) {
            $message .= ' 売上仕訳を' . $deletedJournalCount . '件削除しました。';
        }

        return redirect()->route('office.store_daily_report.daily_summary', [
            'target_month' => $data['target_month'],
        ])->with('statusMessage', $message);
    }

    public function addDailySummaryPatient(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->input('daily_summary_id', ''));
        $targetDate = $this->normalizeDateValue($request->input('日付'));
        $targetStore = trim((string) $request->input('店舗', ''));
        $dailyOrder = trim((string) $request->input('日別順', ''));
        $patientName = trim((string) $request->input('患者名', ''));

        if ($dailySummaryId === '' || $targetDate === null || $targetStore === '' || $dailyOrder === '' || $patientName === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '患者追加に必要な項目が不足しています。');
        }

        $patientNo = $dailySummaryId . '-' . str_pad((string) ((int) $dailyOrder), 3, '0', STR_PAD_LEFT);

        $exists = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報')
            ->whereDate('日付', $targetDate)
            ->where('店舗', $targetStore)
            ->where('日別順', $dailyOrder)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '同じ日付・店舗の日別順が既に存在します。');
        }

        DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報')
            ->insert([
                '日別順' => $dailyOrder,
                '日付' => $targetDate,
                '時刻' => $this->normalizeDateTimeValue($targetDate, $request->input('時刻')),
                '患者No' => $patientNo,
                '患者名' => $patientName,
                '新患' => $this->blankToNull($request->input('新患')),
                '割合' => $this->blankToNull($request->input('割合')),
                '保険証' => $this->blankToNull($request->input('保険証')),
                '店舗' => $targetStore,
                '日報集計No_t' => $dailySummaryId,
            ]);

        return redirect()
            ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
            ->with('statusMessage', '患者を追加しました。');
    }

    public function bulkCheckDailySummaryDetail(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->input('daily_summary_id', ''));
        if ($dailySummaryId === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary')
                ->with('errorMessage', '日報が確認できません。');
        }

        $dailySummaryRow = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->select(['日付', '日報集計店舗', '確定'])
            ->where('日報集計No', $dailySummaryId)
            ->first();

        if ($dailySummaryRow === null) {
            return redirect()
                ->route('office.store_daily_report.daily_summary')
                ->with('errorMessage', '日報が確認できません。');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if ((bool) ($dailySummaryRow->{'確定'} ?? false) && !$this->isPaymentCheck($staffRow)) {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '確定済みの日報は更新できません。');
        }

        $targetDate = $this->formatDateValue($dailySummaryRow->{'日付'} ?? null, 'Y-m-d');
        $targetStore = trim((string) ($dailySummaryRow->{'日報集計店舗'} ?? ''));

        if ($targetDate === '' || $targetStore === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '日付または店舗が確認できません。');
        }

        DB::connection('sqlsrv_dailyreport')->update(
            'UPDATE dbo.T_先生別日報
                SET ch = 1
             FROM dbo.T_先生別日報
             INNER JOIN dbo.T_患者名日報
                ON dbo.T_先生別日報.患者No_t = dbo.T_患者名日報.患者No
             WHERE CONVERT(date, dbo.T_患者名日報.日付) = ?
                AND dbo.T_患者名日報.店舗 = ?
                AND dbo.T_先生別日報.メニュー IS NOT NULL
                AND dbo.T_先生別日報.担当者ID <> ?',
            [$targetDate, $targetStore, '055']
        );

        return redirect()
            ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
            ->with('statusMessage', '一括chを更新しました。');
    }

    public function saveDailySummaryDetail(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $dailySummaryId = trim((string) $request->input('daily_summary_id', ''));
        $patientDailyReportNo = trim((string) $request->input('No', ''));
        $patientNo = trim((string) $request->input('患者No', ''));
        $action = trim((string) $request->input('action', 'save'));

        if ($dailySummaryId === '' || $patientDailyReportNo === '' || $patientNo === '') {
            return redirect()
                ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                ->with('errorMessage', '保存対象が確認できません。');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);

        if ($action === 'save' && !$this->isPaymentCheck($staffRow)) {
            $isConfirmed = DB::connection('sqlsrv_dailyreport')
                ->table('dbo.T_日報集計')
                ->where('日報集計No', $dailySummaryId)
                ->value('確定');

            if ((bool) $isConfirmed) {
                return redirect()
                    ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
                    ->with('errorMessage', '確定済みの日報は保存できません。');
            }
        }

        DB::connection('sqlsrv_dailyreport')->transaction(function () use ($request, $patientDailyReportNo, $patientNo, $action): void {
            $detailRows = $request->input('detail_rows', []);

            if ($action === 'delete_patient') {
                collect($detailRows)
                    ->pluck('先生別No')
                    ->map(fn($value): string => trim((string) $value))
                    ->filter()
                    ->each(function (string $先生別No): void {
                        DB::connection('sqlsrv_dailyreport')
                            ->table('dbo.T_先生別日報')
                            ->where('先生別No', $先生別No)
                            ->delete();
                    });

                DB::connection('sqlsrv_dailyreport')
                    ->table('dbo.T_患者名日報')
                    ->where('No', $patientDailyReportNo)
                    ->delete();

                return;
            }

            DB::connection('sqlsrv_dailyreport')
                ->table('dbo.T_患者名日報')
                ->where('No', $patientDailyReportNo)
                ->update([
                    '新患' => $this->blankToNull($request->input('新患')),
                    '時刻' => $this->normalizeDateTimeValue($request->input('日付'), $request->input('時刻')),
                    '患者名' => $this->blankToNull($request->input('患者名')),
                    '割合' => $this->blankToNull($request->input('割合')),
                    '保険証' => $this->blankToNull($request->input('保険証')),
                    '回収日' => $this->normalizeDateValue($request->input('回収日')),
                    'レセ負担金' => $this->moneyToDatabaseValue($request->input('レセ負担金')),
                    '負担金ch' => $this->checkboxToDatabaseValue($request->input('負担金ch')),
                    '保険請求' => $this->moneyToDatabaseValue($request->input('保険請求')),
                    '差額' => $this->moneyToDatabaseValue($request->input('差額')),
                    '日報備考' => $this->blankToNull($request->input('日報備考')),
                ]);

            foreach ($detailRows as $detailRow) {
                if (!is_array($detailRow)) {
                    continue;
                }

                $先生別No = trim((string) ($detailRow['先生別No'] ?? ''));
                if (!empty($detailRow['_delete'])) {
                    if ($先生別No !== '') {
                        DB::connection('sqlsrv_dailyreport')
                            ->table('dbo.T_先生別日報')
                            ->where('先生別No', $先生別No)
                            ->delete();
                    }

                    continue;
                }

                $teacherData = [
                    'メニュー' => $this->blankToNull($detailRow['メニュー'] ?? null),
                    '回数' => $this->blankToNull($detailRow['回数'] ?? null),
                    '備考' => $this->blankToNull($detailRow['備考'] ?? null),
                    'charge' => $this->checkboxToDatabaseValue($detailRow['charge'] ?? null),
                    '自費' => $this->moneyToDatabaseValue($detailRow['自費'] ?? null),
                    '保険負担' => $this->moneyToDatabaseValue($detailRow['保険負担'] ?? null),
                    'レセ差額' => $this->moneyToDatabaseValue($detailRow['レセ差額'] ?? null),
                    '請求金額' => $this->moneyToDatabaseValue($detailRow['請求金額'] ?? null),
                    'カード手数料' => $this->moneyToDatabaseValue($detailRow['カード手数料'] ?? null),
                    '担当者ID' => $this->blankToNull($detailRow['担当者ID'] ?? null),
                    '項目' => $this->blankToNull($detailRow['項目'] ?? null),
                    '計算外' => $this->checkboxToDatabaseValue($detailRow['計算外'] ?? null),
                    '先生別外' => $this->checkboxToDatabaseValue($detailRow['先生別外'] ?? null),
                    'ch' => $this->checkboxToDatabaseValue($detailRow['ch'] ?? null),
                ];

                $hasInput = collect($teacherData)
                    ->reject(fn($value, string $key): bool => in_array($key, ['charge', '計算外', '先生別外', 'ch'], true) && (int) $value === 0)
                    ->filter(fn($value): bool => $value !== null && $value !== '')
                    ->isNotEmpty();

                if ($先生別No === '' && !$hasInput) {
                    continue;
                }

                if ($先生別No !== '') {
                    DB::connection('sqlsrv_dailyreport')
                        ->table('dbo.T_先生別日報')
                        ->where('先生別No', $先生別No)
                        ->update($teacherData);

                    continue;
                }

                DB::connection('sqlsrv_dailyreport')
                    ->table('dbo.T_先生別日報')
                    ->insert($teacherData + ['患者No_t' => $patientNo]);
            }
        });

        return redirect()
            ->route('office.store_daily_report.daily_summary.detail', ['daily_summary_id' => $dailySummaryId])
            ->with('statusMessage', $action === 'delete_patient' ? '削除しました。' : '保存しました。');
    }

    private function dailySummaryDetailBaseQuery()
    {
        return DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->leftJoin('TCPGSYSTEM_DEV.dbo.mx_staffs as staff', 'teacher.担当者ID', '=', 'staff.staff_id');
    }

    private function dailySummaryDetailSelects(bool $isInsuranceCardCollection, bool $isModifiedPrint = false): array
    {
        $menuSql = $isInsuranceCardCollection
            ? "CASE WHEN patient.保険証 = 2 THEN FORMAT(patient.日付, 'M/d') + N' 保険証忘れ回収' ELSE COALESCE(teacher.メニュー, N'') + COALESCE(CAST(teacher.回数 AS NVARCHAR(50)), N'') END as メニュー集計"
            : "CASE WHEN patient.保険証 = 1 OR patient.保険証 = 2 THEN N'保険証忘れ' ELSE teacher.メニュー END as メニュー集計";

        $countOrderSql = $isInsuranceCardCollection ? "N'999' as 日別順" : 'patient.日別順 as 日別順';
        $dateSql = $isInsuranceCardCollection ? 'patient.回収日 as 日付' : 'patient.日付 as 日付';
        $collectionDateSql = $isInsuranceCardCollection ? 'patient.日付 as 回収日' : 'patient.回収日 as 回収日';
        $copaymentSql = $isInsuranceCardCollection
            ? 'CASE WHEN teacher.計算外 <> 0 OR patient.保険証 = 2 THEN teacher.保険負担 ELSE 0 END as 保険負担計'
            : 'CASE WHEN teacher.計算外 = 0 AND patient.保険証 = 0 THEN teacher.保険負担 ELSE 0 END as 保険負担計';
        $claimSql = $isInsuranceCardCollection
            ? 'CASE WHEN teacher.計算外 = 0 AND patient.保険証 = 2 THEN teacher.請求金額 ELSE 0 END as 請求金額計'
            : 'CASE WHEN teacher.計算外 = 0 AND patient.保険証 = 0 THEN teacher.請求金額 ELSE 0 END as 請求金額計';
        $privateFeeSql = $isInsuranceCardCollection
            ? 'CASE WHEN teacher.計算外 = 0 AND patient.保険証 = 2' . ($isModifiedPrint ? ' AND teacher.ch = 0' : '') . ' THEN teacher.自費 ELSE 0 END as 自費計'
            : 'CASE WHEN teacher.計算外 = 0 AND patient.保険証 = 0' . ($isModifiedPrint ? ' AND teacher.ch = 0' : '') . ' THEN teacher.自費 ELSE 0 END as 自費計';

        return [
            DB::raw('patient.No as No'),
            DB::raw($countOrderSql),
            'patient.新患',
            DB::raw($dateSql),
            DB::raw($collectionDateSql),
            'patient.保険証',
            'patient.患者No',
            'patient.割合',
            'patient.時刻',
            DB::raw('CONVERT(time, patient.時刻) as sort_time'),
            'patient.患者名',
            'teacher.担当者ID',
            'teacher.先生別No',
            'teacher.項目',
            'teacher.回数',
            'teacher.備考',
            'teacher.メニュー',
            'teacher.レセ差額',
            'teacher.カード手数料',
            'teacher.ch',
            'teacher.charge',
            'teacher.自費',
            DB::raw("LTRIM(RTRIM(COALESCE(staff.display_name_ja, staff.staff_name, teacher.担当者ID, ''))) as staff_display_name"),
            DB::raw($menuSql),
            DB::raw('CASE WHEN patient.保険証 = 1 OR patient.保険証 = 2 THEN NULL ELSE teacher.回数 END as 回数表示'),
            DB::raw('CASE WHEN teacher.カード手数料 <> 0 THEN teacher.自費 ELSE 0 END as カード計'),
            DB::raw($privateFeeSql),
            DB::raw($copaymentSql),
            DB::raw($claimSql),
            'teacher.計算外',
            'teacher.保険負担',
            'teacher.請求金額',
            'patient.レセ負担金',
            'patient.負担金ch',
            'patient.保険請求',
            'patient.差額',
            'patient.日報備考',
            'teacher.先生別外',
            'patient.店舗',
            DB::raw('COUNT(DISTINCT patient.患者No) as 人数'),
        ];
    }

    private function dailySummaryDetailGroupByColumns(): string
    {
        return implode(', ', [
            'patient.No',
            'patient.日別順',
            'patient.新患',
            'patient.日付',
            'patient.回収日',
            'patient.保険証',
            'patient.患者No',
            'patient.割合',
            'patient.時刻',
            'patient.患者名',
            'teacher.担当者ID',
            'teacher.先生別No',
            'teacher.項目',
            'teacher.回数',
            'teacher.備考',
            'teacher.メニュー',
            'teacher.レセ差額',
            'teacher.カード手数料',
            'teacher.ch',
            'teacher.charge',
            'staff.display_name_ja',
            'staff.staff_name',
            'teacher.計算外',
            'teacher.保険負担',
            'teacher.請求金額',
            'patient.レセ負担金',
            'patient.負担金ch',
            'patient.保険請求',
            'patient.差額',
            'patient.日報備考',
            'teacher.先生別外',
            'patient.店舗',
            'teacher.自費',
        ]);
    }

    private function buildMonthlyWindowPrintData(string $targetMonthStart, string $targetMonthEnd, string $selectedStore, array $receiptBurdenInputs = []): array
    {
        $normalRowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                DB::raw('CONVERT(date, patient.日付) as 日付'),
                'patient.店舗',
                'patient.患者No',
                'patient.レセ負担金',
                'patient.差額',
                'patient.保険証',
                'patient.負担金ch',
                DB::raw("MAX(CASE WHEN patient.新患 = N'新患' THEN 1 ELSE 0 END) as 新患"),
                DB::raw("MAX(CASE WHEN patient.新患 = N'新患扱い' THEN 1 ELSE 0 END) as 新患扱い"),
                DB::raw('SUM(CASE WHEN patient.保険証 = 0 AND COALESCE(teacher.ch, 0) = 0 THEN COALESCE(teacher.自費, 0) ELSE 0 END) as 自費計'),
                DB::raw('MAX(CASE WHEN patient.保険証 = 0 AND COALESCE(patient.負担金ch, 0) = 0 THEN COALESCE(patient.レセ負担金, 0) ELSE 0 END) as 保険負担計'),
                DB::raw('0 as レセ差額計'),
                DB::raw('MAX(CASE WHEN patient.保険証 = 0 THEN COALESCE(patient.レセ負担金, 0) ELSE 0 END) as レセ負担金計'),
                DB::raw('SUM(CASE WHEN patient.保険証 = 2 THEN COALESCE(teacher.レセ差額, 0) ELSE 0 END) as レセ差額減算'),
                DB::raw('1 as 人数'),
            ])
            ->where('patient.日付', '>=', $targetMonthStart)
            ->where('patient.日付', '<', $targetMonthEnd)
            ->groupBy('patient.日付', 'patient.店舗', 'patient.患者No', 'patient.レセ負担金', 'patient.差額', 'patient.保険証', 'patient.負担金ch');

        $collectionRowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                DB::raw('CONVERT(date, patient.回収日) as 日付'),
                'patient.店舗',
                'patient.患者No',
                'patient.レセ負担金',
                'patient.差額',
                'patient.保険証',
                'patient.負担金ch',
                DB::raw("MAX(CASE WHEN patient.新患 = N'新患' THEN 1 ELSE 0 END) as 新患"),
                DB::raw("MAX(CASE WHEN patient.新患 = N'新患扱い' THEN 1 ELSE 0 END) as 新患扱い"),
                DB::raw('SUM(CASE WHEN patient.保険証 <> 0 AND COALESCE(teacher.ch, 0) = 0 THEN COALESCE(teacher.自費, 0) ELSE 0 END) as 自費計'),
                DB::raw('MAX(CASE WHEN patient.保険証 = 2 AND COALESCE(patient.負担金ch, 0) = 0 THEN COALESCE(patient.レセ負担金, 0) ELSE 0 END) as 保険負担計'),
                DB::raw('0 as レセ差額計'),
                DB::raw('MAX(CASE WHEN patient.保険証 = 2 THEN COALESCE(patient.レセ負担金, 0) ELSE 0 END) as レセ負担金計'),
                DB::raw('0 as レセ差額減算'),
                DB::raw('1 as 人数'),
            ])
            ->where('patient.回収日', '>=', $targetMonthStart)
            ->where('patient.回収日', '<', $targetMonthEnd)
            ->where('patient.保険証', 2)
            ->groupBy('patient.回収日', 'patient.店舗', 'patient.患者No', 'patient.レセ負担金', 'patient.差額', 'patient.保険証', 'patient.負担金ch');

        if ($selectedStore !== '') {
            $normalRowsQuery->where('patient.店舗', $selectedStore);
            $collectionRowsQuery->where('patient.店舗', $selectedStore);
        }

        $expenseRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->select([
                DB::raw('CONVERT(date, 日付) as 日付'),
                'レジ店舗',
                DB::raw('SUM(COALESCE(金額, 0)) as その他'),
            ])
            ->where('日付', '>=', $targetMonthStart)
            ->where('日付', '<', $targetMonthEnd)
            ->where('項目', '<>', 'チャージ金')
            ->where('項目', '<>', '物品販売')
            ->where(function ($query): void {
                $query->whereNull('非表示ch')
                    ->orWhere('非表示ch', 0);
            });

        if ($selectedStore !== '') {
            $expenseRows->where('レジ店舗', $selectedStore);
        }

        $expenseByDateStore = $expenseRows
            ->groupBy('日付', 'レジ店舗')
            ->get()
            ->mapWithKeys(fn($row): array => [
                $this->formatDateValue($row->{'日付'} ?? null, 'Y-m-d') . '|' . trim((string) ($row->{'レジ店舗'} ?? '')) => (float) ($row->{'その他'} ?? 0),
            ])
            ->all();

        $dailyDiffRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計')
            ->select([
                DB::raw('CONVERT(date, 日付) as 日付'),
                '日報集計店舗',
                DB::raw('SUM(COALESCE(差額, 0)) as 差額'),
            ])
            ->where('日付', '>=', $targetMonthStart)
            ->where('日付', '<', $targetMonthEnd);

        if ($selectedStore !== '') {
            $dailyDiffRows->where('日報集計店舗', $selectedStore);
        }

        $dailyDiffByDateStore = $dailyDiffRows
            ->groupBy('日付', '日報集計店舗')
            ->get()
            ->mapWithKeys(fn($row): array => [
                $this->formatDateValue($row->{'日付'} ?? null, 'Y-m-d') . '|' . trim((string) ($row->{'日報集計店舗'} ?? '')) => (float) ($row->{'差額'} ?? 0),
            ])
            ->all();

        $patientRows = $normalRowsQuery
            ->get()
            ->merge($collectionRowsQuery->get());

        $groupedRows = $patientRows
            ->groupBy(fn($row): string => $this->formatDateValue($row->{'日付'} ?? null, 'Y-m-d') . '|' . trim((string) ($row->{'店舗'} ?? '')));

        foreach ($expenseByDateStore as $key => $amount) {
            if (!$groupedRows->has($key)) {
                $groupedRows->put($key, collect());
            }
        }

        $rows = $groupedRows
            ->map(function ($rows, string $key) use ($expenseByDateStore, $dailyDiffByDateStore): array {
                [$date, $storeName] = explode('|', $key, 2);
                $selfPay = (float) $rows->sum(fn($row): float => (float) ($row->{'自費計'} ?? 0));
                $insurance = (float) $rows->sum(fn($row): float => (float) ($row->{'保険負担計'} ?? 0));
                $expense = (float) ($expenseByDateStore[$key] ?? 0);
                $receiptBurden = (float) $rows->sum(fn($row): float => (float) ($row->{'レセ負担金計'} ?? 0));
                $difference = (float) ($dailyDiffByDateStore[$key] ?? 0);

                return [
                    'date' => $date,
                    'day' => $this->formatDateValue($date, 'j'),
                    'store_name' => $storeName,
                    'new_patient_count' => (int) $rows->sum(fn($row): int => (int) ($row->{'新患'} ?? 0)),
                    'new_like_count' => (int) $rows->sum(fn($row): int => (int) ($row->{'新患扱い'} ?? 0)),
                    'self_pay_amount' => $selfPay,
                    'insurance_amount' => $insurance,
                    'expense_amount' => $expense,
                    'total_amount' => $selfPay + $insurance - $expense,
                    'people_count' => (int) $rows->sum(fn($row): int => (int) ($row->{'人数'} ?? 0)),
                    'receipt_burden_amount' => $receiptBurden,
                    'difference_amount' => $difference,
                ];
            })
            ->sortBy([['store_name', 'asc'], ['date', 'asc']])
            ->values()
            ->all();

        $year = (int) substr($targetMonthStart, 0, 4);
        $month = (int) substr($targetMonthStart, 5, 2);

        $monthlyClosingRow = DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->where('closing_month', '>=', $targetMonthStart)
            ->where('closing_month', '<', $targetMonthEnd)
            ->where('authority', self::DAILY_SUMMARY_MONTHLY_CLOSING_AUTHORITY)
            ->orderByDesc('processed_at')
            ->first();

        $summaryReceiptBurdenAmount = collect($rows)->sum('receipt_burden_amount');
        $storedSakuraReceiptBurdenAmount = $monthlyClosingRow !== null
            ? (float) ($monthlyClosingRow->sakura_receipt_burden_amount ?? 0)
            : $receiptBurdenInputs['sakura'] ?? null;
        $storedHinataReceiptBurdenAmount = $monthlyClosingRow !== null
            ? (float) ($monthlyClosingRow->hinata_receipt_burden_amount ?? 0)
            : $receiptBurdenInputs['hinata'] ?? null;

        if (str_contains($selectedStore, 'さくら') && $storedSakuraReceiptBurdenAmount !== null) {
            $summaryReceiptBurdenAmount = $storedSakuraReceiptBurdenAmount;
        } elseif (str_contains($selectedStore, 'ひなた') && $storedHinataReceiptBurdenAmount !== null) {
            $summaryReceiptBurdenAmount = $storedHinataReceiptBurdenAmount;
        } elseif ($storedSakuraReceiptBurdenAmount !== null || $storedHinataReceiptBurdenAmount !== null) {
            $summaryReceiptBurdenAmount = (float) ($storedSakuraReceiptBurdenAmount ?? 0)
                + (float) ($storedHinataReceiptBurdenAmount ?? 0);
        }

        return [
            'windowRows' => $rows,
            'windowTotals' => [
                'days' => collect($rows)->pluck('date')->unique()->count(),
                'new_patient_count' => collect($rows)->sum('new_patient_count'),
                'new_like_count' => collect($rows)->sum('new_like_count'),
                'self_pay_amount' => collect($rows)->sum('self_pay_amount'),
                'insurance_amount' => collect($rows)->sum('insurance_amount'),
                'expense_amount' => collect($rows)->sum('expense_amount'),
                'total_amount' => collect($rows)->sum('total_amount'),
                'people_count' => collect($rows)->sum('people_count'),
                'receipt_burden_amount' => collect($rows)->sum('receipt_burden_amount'),
                'summary_receipt_burden_amount' => $summaryReceiptBurdenAmount,
                'difference_amount' => collect($rows)->sum('difference_amount'),
            ],
            'targetMonthWareki' => '令和' . ($year - 2018) . '年' . $month . '月分',
        ];
    }
    private function buildMonthlyDailyPrintData(string $targetMonthStart, string $targetMonthEnd, string $selectedStore): array
    {
        $receiptBurdenSubQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報')
            ->select([
                '日付',
                '店舗',
                DB::raw('SUM(COALESCE(レセ負担金, 0)) as レセ負担金'),
            ])
            ->where('日付', '>=', $targetMonthStart)
            ->where('日付', '<', $targetMonthEnd)
            ->groupBy('日付', '店舗');

        $rowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_日報集計 as daily')
            ->leftJoinSub($receiptBurdenSubQuery, 'patient_receipt_burden', function ($join): void {
                $join->on('daily.日付', '=', 'patient_receipt_burden.日付')
                    ->on('daily.日報集計店舗', '=', 'patient_receipt_burden.店舗');
            })
            ->select([
                'daily.日付',
                'daily.レセコン',
                'daily.先生別計',
                'daily.差額',
                'daily.交通事故',
                'daily.備考',
                'daily.レジ',
                'daily.銀行預入金額',
                'daily.来院人数',
                'daily.予約人数',
                'daily.院内人数',
                'daily.日報集計店舗',
                DB::raw('COALESCE(patient_receipt_burden.レセ負担金, 0) as レセ負担金'),
            ])
            ->where('daily.日付', '>=', $targetMonthStart)
            ->where('daily.日付', '<', $targetMonthEnd)
            ->orderBy('daily.日付');

        if ($selectedStore !== '') {
            $rowsQuery->where('daily.日報集計店舗', $selectedStore);
        }

        $rows = $rowsQuery
            ->get()
            ->map(fn($row): array => [
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                '来院人数' => $this->formatNumberValue($row->{'来院人数'} ?? null),
                '予約人数' => $this->formatNumberValue($row->{'予約人数'} ?? null),
                '院内人数' => $this->formatNumberValue($row->{'院内人数'} ?? null),
                'レセコン' => $this->formatMoneyValue($row->{'レセコン'} ?? null),
                '先生別計' => $this->formatMoneyValue($row->{'先生別計'} ?? null),
                '差額' => $this->formatMoneyValue($row->{'差額'} ?? null),
                'レセ負担金' => $this->formatMoneyValue($row->{'レセ負担金'} ?? null),
                '交通事故' => $this->formatMoneyValue($row->{'交通事故'} ?? null),
                'レジ' => $this->formatMoneyValue($row->{'レジ'} ?? null),
                'raw_来院人数' => (float) ($row->{'来院人数'} ?? 0),
                'raw_予約人数' => (float) ($row->{'予約人数'} ?? 0),
                'raw_院内人数' => (float) ($row->{'院内人数'} ?? 0),
                'raw_レセコン' => (float) ($row->{'レセコン'} ?? 0),
                'raw_先生別計' => (float) ($row->{'先生別計'} ?? 0),
                'raw_差額' => (float) ($row->{'差額'} ?? 0),
                'raw_レセ負担金' => (float) ($row->{'レセ負担金'} ?? 0),
                'raw_交通事故' => (float) ($row->{'交通事故'} ?? 0),
                'raw_レジ' => (float) ($row->{'レジ'} ?? 0),
            ])
            ->all();

        return [
            'printRows' => $rows,
            'printTotals' => [
                '来院人数' => $this->formatNumberValue(collect($rows)->sum('raw_来院人数')),
                '予約人数' => $this->formatNumberValue(collect($rows)->sum('raw_予約人数')),
                '院内人数' => $this->formatNumberValue(collect($rows)->sum('raw_院内人数')),
                'レセコン' => $this->formatMoneyValue(collect($rows)->sum('raw_レセコン')),
                '先生別計' => $this->formatMoneyValue(collect($rows)->sum('raw_先生別計')),
                '差額' => $this->formatMoneyValue(collect($rows)->sum('raw_差額')),
                'レセ負担金' => $this->formatMoneyValue(collect($rows)->sum('raw_レセ負担金')),
                '交通事故' => $this->formatMoneyValue(collect($rows)->sum('raw_交通事故')),
                'レジ' => $this->formatMoneyValue(collect($rows)->sum('raw_レジ')),
            ],
        ];
    }

    private function buildTeacherMonthlyPrintData(string $targetMonthStart, string $targetMonthEnd, string $selectedStore): array
    {
        $rowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->leftJoin('TCPGSYSTEM_DEV.dbo.mx_staffs as staff', 'teacher.担当者ID', '=', 'staff.staff_id')
            ->select([
                'patient.日付',
                'teacher.担当者ID',
                DB::raw("LTRIM(RTRIM(COALESCE(staff.display_name_ja, staff.staff_name, teacher.担当者ID, ''))) as 担当者名"),
                DB::raw('COUNT(DISTINCT patient.患者No) as 人数'),
                DB::raw("SUM(CASE WHEN patient.割合 = N'自' THEN 1 ELSE 0 END) as 自費人数"),
                DB::raw('SUM(COALESCE(teacher.自費, 0)) as 自費'),
                DB::raw("COUNT(DISTINCT CASE WHEN (COALESCE(teacher.保険負担, 0) + COALESCE(teacher.請求金額, 0)) <> 0 THEN patient.患者No END) as 保険人数"),
                DB::raw('SUM(COALESCE(teacher.保険負担, 0)) as 保険負担'),
                DB::raw('SUM(COALESCE(teacher.請求金額, 0)) as 請求金額'),
                DB::raw('SUM(COALESCE(teacher.レセ差額, 0)) as レセ差額'),
                DB::raw('SUM(COALESCE(teacher.カード手数料, 0)) as カード手数料'),
            ])
            ->where('patient.日付', '>=', $targetMonthStart)
            ->where('patient.日付', '<', $targetMonthEnd)
            ->whereNotNull('teacher.担当者ID')
            ->where('teacher.担当者ID', '<>', '')
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->groupBy('patient.日付', 'teacher.担当者ID', 'staff.display_name_ja', 'staff.staff_name')
            ->orderBy('teacher.担当者ID')
            ->orderBy('patient.日付');

        if ($selectedStore !== '') {
            $rowsQuery->where('patient.店舗', $selectedStore);
        }

        $rows = $rowsQuery
            ->get()
            ->map(fn($row): array => [
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                '担当者ID' => trim((string) ($row->{'担当者ID'} ?? '')),
                '担当者名' => trim((string) ($row->{'担当者名'} ?? '')),
                '人数' => $this->formatNumberValue($row->{'人数'} ?? null),
                '自費人数' => $this->formatNumberValue($row->{'自費人数'} ?? null),
                '自費' => $this->formatMoneyValue($row->{'自費'} ?? null),
                '保険人数' => $this->formatNumberValue($row->{'保険人数'} ?? null),
                '保険負担' => $this->formatMoneyValue($row->{'保険負担'} ?? null),
                '請求金額' => $this->formatMoneyValue($row->{'請求金額'} ?? null),
                'レセ差額' => $this->formatMoneyValue($row->{'レセ差額'} ?? null),
                'カード手数料' => $this->formatMoneyValue($row->{'カード手数料'} ?? null),
                'raw_人数' => (float) ($row->{'人数'} ?? 0),
                'raw_自費人数' => (float) ($row->{'自費人数'} ?? 0),
                'raw_自費' => (float) ($row->{'自費'} ?? 0),
                'raw_保険人数' => (float) ($row->{'保険人数'} ?? 0),
                'raw_保険負担' => (float) ($row->{'保険負担'} ?? 0),
                'raw_請求金額' => (float) ($row->{'請求金額'} ?? 0),
                'raw_レセ差額' => (float) ($row->{'レセ差額'} ?? 0),
                'raw_カード手数料' => (float) ($row->{'カード手数料'} ?? 0),
            ])
            ->all();

        return [
            'printRows' => $rows,
            'teacherGroups' => collect($rows)
                ->groupBy(fn(array $row): string => trim((string) ($row['担当者ID'] ?? '')))
                ->map(fn($groupRows): array => [
                    '担当者ID' => trim((string) ($groupRows->first()['担当者ID'] ?? '')),
                    '担当者名' => trim((string) ($groupRows->first()['担当者名'] ?? '')),
                    'rows' => $groupRows->values()->all(),
                    'totals' => [
                        '日数計' => $this->formatNumberValue($groupRows->count()),
                        '人数' => $this->formatNumberValue($groupRows->sum('raw_人数')),
                        '自費人数' => $this->formatNumberValue($groupRows->sum('raw_自費人数')),
                        '自費' => $this->formatMoneyValue($groupRows->sum('raw_自費')),
                        '保険人数' => $this->formatNumberValue($groupRows->sum('raw_保険人数')),
                        '保険負担' => $this->formatMoneyValue($groupRows->sum('raw_保険負担')),
                        '請求金額' => $this->formatMoneyValue($groupRows->sum('raw_請求金額')),
                        '保険合計' => $this->formatMoneyValue($groupRows->sum('raw_請求金額') + $groupRows->sum('raw_保険負担')),
                        '合計' => $this->formatMoneyValue($groupRows->sum('raw_自費') + $groupRows->sum('raw_請求金額') + $groupRows->sum('raw_保険負担')),
                    ],
                ])
                ->values()
                ->all(),
            'printTotals' => [
                '日数計' => $this->formatNumberValue(collect($rows)->count()),
                '人数' => $this->formatNumberValue(collect($rows)->sum('raw_人数')),
                '自費人数' => $this->formatNumberValue(collect($rows)->sum('raw_自費人数')),
                '自費' => $this->formatMoneyValue(collect($rows)->sum('raw_自費')),
                '保険人数' => $this->formatNumberValue(collect($rows)->sum('raw_保険人数')),
                '保険負担' => $this->formatMoneyValue(collect($rows)->sum('raw_保険負担')),
                '請求金額' => $this->formatMoneyValue(collect($rows)->sum('raw_請求金額')),
                '保険合計' => $this->formatMoneyValue(collect($rows)->sum('raw_請求金額') + collect($rows)->sum('raw_保険負担')),
                '合計' => $this->formatMoneyValue(collect($rows)->sum('raw_自費') + collect($rows)->sum('raw_請求金額') + collect($rows)->sum('raw_保険負担')),
                'レセ差額' => $this->formatMoneyValue(collect($rows)->sum('raw_レセ差額')),
                'カード手数料' => $this->formatMoneyValue(collect($rows)->sum('raw_カード手数料')),
            ],
        ];
    }

    private function buildTeacherDailyPrintData(string $targetMonthStart, string $targetMonthEnd, string $selectedStore): array
    {
        $rowsQuery = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->leftJoin('TCPGSYSTEM_DEV.dbo.mx_staffs as staff', 'teacher.担当者ID', '=', 'staff.staff_id')
            ->select([
                'patient.日付',
                'patient.日別順',
                'patient.時刻',
                'patient.患者名',
                'patient.新患',
                'teacher.担当者ID',
                DB::raw("LTRIM(RTRIM(COALESCE(staff.display_name_ja, staff.staff_name, teacher.担当者ID, ''))) as 担当者名"),
                'teacher.自費',
                'teacher.保険負担',
                'teacher.請求金額',
            ])
            ->where('patient.日付', '>=', $targetMonthStart)
            ->where('patient.日付', '<', $targetMonthEnd)
            ->whereNotNull('teacher.担当者ID')
            ->where('teacher.担当者ID', '<>', '')
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->orderBy('patient.日付')
            ->orderBy('teacher.担当者ID')
            ->orderBy('patient.日別順')
            ->orderBy('patient.時刻');

        if ($selectedStore !== '') {
            $rowsQuery->where('patient.店舗', $selectedStore);
        }

        $rows = $rowsQuery
            ->get()
            ->map(fn($row): array => [
                '日付' => $this->formatDateValue($row->{'日付'} ?? null, 'Y/m/d'),
                '日別順' => trim((string) ($row->{'日別順'} ?? '')),
                '時刻' => $this->formatDateValue($row->{'時刻'} ?? null, 'H:i'),
                '患者名' => trim((string) ($row->{'患者名'} ?? '')),
                '新患' => trim((string) ($row->{'新患'} ?? '')),
                '担当者ID' => trim((string) ($row->{'担当者ID'} ?? '')),
                '担当者名' => trim((string) ($row->{'担当者名'} ?? '')),
                '自費' => $this->formatMoneyValue($row->{'自費'} ?? null),
                '保険負担' => $this->formatMoneyValue($row->{'保険負担'} ?? null),
                '請求金額' => $this->formatMoneyValue($row->{'請求金額'} ?? null),
                'raw_自費' => (float) ($row->{'自費'} ?? 0),
                'raw_保険負担' => (float) ($row->{'保険負担'} ?? 0),
                'raw_請求金額' => (float) ($row->{'請求金額'} ?? 0),
            ])
            ->all();

        $teacherDailyGroups = collect($rows)
            ->groupBy(fn(array $row): string => trim((string) ($row['日付'] ?? '')) . '|' . trim((string) ($row['担当者ID'] ?? '')))
            ->map(fn($groupRows): array => [
                '日付' => trim((string) ($groupRows->first()['日付'] ?? '')),
                '担当者ID' => trim((string) ($groupRows->first()['担当者ID'] ?? '')),
                '担当者名' => trim((string) ($groupRows->first()['担当者名'] ?? '')),
                'rows' => $groupRows->values()->all(),
                'totals' => [
                    '自費' => $this->formatMoneyValue($groupRows->sum('raw_自費')),
                    '保険負担' => $this->formatMoneyValue($groupRows->sum('raw_保険負担')),
                    '請求金額' => $this->formatMoneyValue($groupRows->sum('raw_請求金額')),
                ],
            ])
            ->values()
            ->all();

        return [
            'printRows' => $rows,
            'teacherDailyGroups' => $teacherDailyGroups,
            'printTotals' => [
                '自費' => $this->formatMoneyValue(collect($rows)->sum('raw_自費')),
                '保険負担' => $this->formatMoneyValue(collect($rows)->sum('raw_保険負担')),
                '請求金額' => $this->formatMoneyValue(collect($rows)->sum('raw_請求金額')),
            ],
        ];
    }

    private function parseTargetMonth(string $targetMonth): array
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $targetMonth, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [(int) now()->format('Y'), (int) now()->format('m')];
    }

    private function dailySummaryMonthlyClosingRow(Carbon $targetMonth): ?object
    {
        $targetMonthStart = $targetMonth->copy()->startOfMonth()->startOfDay();
        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();

        return DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->where('closing_month', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('closing_month', '<', $targetMonthNext->format('Y-m-d'))
            ->where('authority', self::DAILY_SUMMARY_MONTHLY_CLOSING_AUTHORITY)
            ->orderByDesc('processed_at')
            ->first();
    }
    private function dailySummaryMonthlyInputRow(Carbon $targetMonth): ?object
    {
        $targetMonthStart = $targetMonth->copy()->startOfMonth()->startOfDay();
        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();

        return DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->where('closing_month', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('closing_month', '<', $targetMonthNext->format('Y-m-d'))
            ->where('authority', self::DAILY_SUMMARY_MONTHLY_INPUT_AUTHORITY)
            ->orderByDesc('processed_at')
            ->first();
    }

    private function deleteDailySummaryMonthlyJournalEntries(Carbon $targetMonthStart): int
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
            ->where('summary_text', $targetMonthStart->format('n') . '月売上')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('debit_account_title', '現金')
                        ->where('credit_account_title', '窓口収入')
                        ->where('credit_item_name', '保険窓口負担');
                })->orWhere(function ($query) use ($targetMonthStart): void {
                    $query->where('debit_account_title', '現金')
                        ->where('credit_account_title', '自費収入')
                        ->where('credit_item_name', '自費')
                        ->where('journal_breakdown', 'not like', $targetMonthStart->format('Ym') . 'レセ%');
                })->orWhere(function ($query): void {
                    $query->where('debit_account_title', '消耗品費')
                        ->where('debit_item_name', '店舗経費')
                        ->where('credit_account_title', '現金')
                        ->where('credit_item_name', '店舗経費');
                });
            })
            ->delete();
    }

    private function dailySummaryMonthlyJournalPayloads(Carbon $targetMonthStart, Carbon $targetMonthNext, Carbon $targetMonth, array $monthlyClosingPayload = []): array
    {
        $definitions = [
            '窓口' => [
                'totals' => $this->dailySummaryMonthlyConfirmedWindowTotals($targetMonthStart, $targetMonthNext, $monthlyClosingPayload),
                'debit_account_title' => '現金',
                'debit_item_name' => '保険窓口負担',
                'debit_tax_category' => '対象外',
                'credit_account_title' => '窓口収入',
                'credit_item_name' => '保険窓口負担',
                'credit_tax_category' => '非課売上',
            ],
            '経費' => [
                'totals' => $this->dailySummaryMonthlyExpenseTotals($targetMonthStart, $targetMonthNext),
                'debit_account_title' => '消耗品費',
                'debit_item_name' => '店舗経費',
                'debit_tax_category' => '対象外',
                'credit_account_title' => '現金',
                'credit_item_name' => '店舗経費',
                'credit_tax_category' => '対象外',
            ],
            '自費' => [
                'totals' => $this->dailySummaryMonthlySelfPayTotals($targetMonthStart, $targetMonthNext),
                'debit_account_title' => '現金',
                'debit_item_name' => '自費',
                'debit_tax_category' => '対象外',
                'credit_account_title' => '自費収入',
                'credit_item_name' => '自費',
                'credit_tax_category' => '課税売上10%',
            ],
        ];

        $payloads = [];

        foreach ($definitions as $journalLabel => $definition) {
            foreach ($definition['totals'] as $storeName => $amount) {
                if ((float) $amount === 0.0) {
                    continue;
                }

                $departmentRow = $this->dailySummaryJournalDepartmentRow($storeName);
                if ($departmentRow === null) {
                    continue;
                }

                $departmentName = trim((string) (($departmentRow->store_short_name ?? '') !== ''
                    ? $departmentRow->store_short_name
                    : ($departmentRow->store_category ?? '')));
                if ($departmentName === '') {
                    continue;
                }

                $payloads[] = [
                    'journal_breakdown' => $targetMonthStart->format('Ym') . $journalLabel . $departmentName,
                    'summary_text' => $targetMonthStart->format('n') . '月売上',
                    'occurred_at' => $targetMonth->format('Y-m-d'),
                    'month_date' => $targetMonthStart->format('Y-m-d'),
                    'debit_account_title' => $definition['debit_account_title'],
                    'debit_item_name' => $definition['debit_item_name'],
                    'debit_tax_category' => $definition['debit_tax_category'],
                    'debit_department_name' => $departmentName,
                    'debit_amount' => $amount,
                    'credit_account_title' => $definition['credit_account_title'],
                    'credit_amount' => $amount,
                    'credit_item_name' => $definition['credit_item_name'],
                    'credit_tax_category' => $definition['credit_tax_category'],
                    'credit_department_name' => $departmentName,
                    'company_name_short' => trim((string) $this->dailySummaryJournalCompanyName($departmentName)),
                ];
            }
        }

        return $payloads;
    }

    private function dailySummaryMonthlyConfirmedWindowTotals(Carbon $targetMonthStart, Carbon $targetMonthNext, array $monthlyClosingPayload): array
    {
        $totals = $this->dailySummaryMonthlyWindowInsuranceTotals($targetMonthStart, $targetMonthNext);

        foreach ($totals as $storeName => $amount) {
            if (str_contains((string) $storeName, 'さくら') && array_key_exists('sakura_insurance_burden_total', $monthlyClosingPayload)) {
                $totals[$storeName] = (float) ($monthlyClosingPayload['sakura_insurance_burden_total'] ?? 0);
            }

            if (str_contains((string) $storeName, 'ひなた') && array_key_exists('hinata_insurance_burden_total', $monthlyClosingPayload)) {
                $totals[$storeName] = (float) ($monthlyClosingPayload['hinata_insurance_burden_total'] ?? 0);
            }
        }

        return $totals;
    }
    private function dailySummaryMonthlyWindowInsuranceTotals(Carbon $targetMonthStart, Carbon $targetMonthNext): array
    {
        $normalRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->select([
                'patient.店舗',
                DB::raw('SUM(CASE WHEN patient.保険証 = 0 AND COALESCE(patient.負担金ch, 0) = 0 THEN COALESCE(patient.レセ負担金, 0) ELSE 0 END) as amount'),
            ])
            ->where('patient.日付', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('patient.日付', '<', $targetMonthNext->format('Y-m-d'))
            ->groupBy('patient.店舗')
            ->get();

        $collectionRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->select([
                'patient.店舗',
                DB::raw('SUM(CASE WHEN patient.保険証 = 2 AND COALESCE(patient.負担金ch, 0) = 0 THEN COALESCE(patient.レセ負担金, 0) ELSE 0 END) as amount'),
            ])
            ->where('patient.回収日', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('patient.回収日', '<', $targetMonthNext->format('Y-m-d'))
            ->where('patient.保険証', 2)
            ->groupBy('patient.店舗')
            ->get();

        $totals = [];
        foreach ($normalRows->merge($collectionRows) as $row) {
            $storeName = trim((string) ($row->{'店舗'} ?? ''));
            if ($storeName === '') {
                continue;
            }

            $totals[$storeName] = (float) ($totals[$storeName] ?? 0) + (float) ($row->amount ?? 0);
        }

        return $totals;
    }
    private function dailySummaryMonthlyJournalTotals(Carbon $targetMonthStart, Carbon $targetMonthNext): array
    {
        $normalRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                'patient.店舗',
                DB::raw('SUM(CASE WHEN teacher.計算外 = 0 AND patient.保険証 = 0 THEN COALESCE(patient.レセ負担金, 0) + COALESCE(patient.差額, 0) ELSE 0 END) as amount'),
            ])
            ->where('patient.日付', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('patient.日付', '<', $targetMonthNext->format('Y-m-d'))
            ->groupBy('patient.店舗')
            ->get();

        $collectionRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                'patient.店舗',
                DB::raw('SUM(CASE WHEN teacher.計算外 <> 0 OR patient.保険証 = 2 THEN COALESCE(patient.レセ負担金, 0) + COALESCE(patient.差額, 0) ELSE 0 END) as amount'),
            ])
            ->where('patient.回収日', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('patient.回収日', '<', $targetMonthNext->format('Y-m-d'))
            ->where('patient.保険証', 2)
            ->groupBy('patient.店舗')
            ->get();

        $totals = [];

        foreach ($normalRows as $row) {
            $storeName = trim((string) ($row->{'店舗'} ?? ''));
            if ($storeName === '') {
                continue;
            }

            $totals[$storeName] = (float) ($totals[$storeName] ?? 0) + (float) ($row->amount ?? 0);
        }

        foreach ($collectionRows as $row) {
            $storeName = trim((string) ($row->{'店舗'} ?? ''));
            if ($storeName === '') {
                continue;
            }

            $totals[$storeName] = (float) ($totals[$storeName] ?? 0) + (float) ($row->amount ?? 0);
        }

        return $totals;
    }

    private function dailySummaryMonthlySelfPayTotals(Carbon $targetMonthStart, Carbon $targetMonthNext): array
    {
        $normalRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                'patient.店舗',
                DB::raw('SUM(CASE WHEN patient.保険証 = 0 AND COALESCE(teacher.ch, 0) = 0 THEN COALESCE(teacher.自費, 0) ELSE 0 END) as amount'),
            ])
            ->where('patient.日付', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('patient.日付', '<', $targetMonthNext->format('Y-m-d'))
            ->groupBy('patient.店舗')
            ->get();

        $collectionRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->leftJoin('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->select([
                'patient.店舗',
                DB::raw('SUM(CASE WHEN patient.保険証 <> 0 AND COALESCE(teacher.ch, 0) = 0 THEN COALESCE(teacher.自費, 0) ELSE 0 END) as amount'),
            ])
            ->where('patient.回収日', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('patient.回収日', '<', $targetMonthNext->format('Y-m-d'))
            ->where('patient.保険証', 2)
            ->groupBy('patient.店舗')
            ->get();

        $totals = [];

        foreach ($normalRows as $row) {
            $storeName = trim((string) ($row->{'店舗'} ?? ''));
            if ($storeName === '') {
                continue;
            }

            $totals[$storeName] = (float) ($totals[$storeName] ?? 0) + (float) ($row->amount ?? 0);
        }

        foreach ($collectionRows as $row) {
            $storeName = trim((string) ($row->{'店舗'} ?? ''));
            if ($storeName === '') {
                continue;
            }

            $totals[$storeName] = (float) ($totals[$storeName] ?? 0) + (float) ($row->amount ?? 0);
        }

        return $totals;
    }

    private function dailySummaryMonthlyExpenseTotals(Carbon $targetMonthStart, Carbon $targetMonthNext): array
    {
        $rows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_レジ詳細')
            ->select([
                'レジ店舗',
                DB::raw('SUM(COALESCE(金額, 0)) as amount'),
            ])
            ->where('日付', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('日付', '<', $targetMonthNext->format('Y-m-d'))
            ->where('項目', '<>', 'チャージ金')
            ->where('項目', '<>', '物品販売')
            ->where(function ($query): void {
                $query->whereNull('非表示ch')
                    ->orWhere('非表示ch', 0);
            })
            ->groupBy('レジ店舗')
            ->get();

        $totals = [];

        foreach ($rows as $row) {
            $storeName = trim((string) ($row->{'レジ店舗'} ?? ''));
            if ($storeName === '') {
                continue;
            }

            $totals[$storeName] = (float) ($row->amount ?? 0);
        }

        return $totals;
    }
    private function dailySummaryStoreTotalByName(array $totals, string $storeNamePart): float
    {
        foreach ($totals as $storeName => $amount) {
            if (str_contains((string) $storeName, $storeNamePart)) {
                return (float) $amount;
            }
        }

        return 0.0;
    }
    private function dailySummaryJournalDepartmentRow(string $storeName): ?object
    {
        $storeRow = DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->where('store_name', $storeName)
            ->where('business_type', '（店舗）')
            ->where(function ($query): void {
                $query->where('is_closed', false)
                    ->orWhere('is_closed', 0)
                    ->orWhereNull('is_closed');
            })
            ->select('store_code')
            ->orderBy('store_code')
            ->first();

        if ($storeRow !== null) {
            $departmentRow = DB::connection('sqlsrv')
                ->table('dbo.mx_departments')
                ->where('official_store_no', trim((string) ($storeRow->store_code ?? '')))
                ->where(function ($query): void {
                    $query->where('has_receipt', true)
                        ->orWhere('has_receipt', 1);
                })
                ->select(['store_short_name', 'store_category'])
                ->orderBy('department_no')
                ->first();

            if ($departmentRow !== null) {
                return $departmentRow;
            }
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->where(function ($query) use ($storeName): void {
                $query->where('store_short_name', $storeName)
                    ->orWhere('store_category', $storeName);
            })
            ->select(['store_short_name', 'store_category'])
            ->first();
    }
    private function dailySummaryJournalCompanyName(string $departmentName): string
    {
        return trim((string) (DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where(function ($query) use ($departmentName): void {
                $query->where('debit_department_name', $departmentName)
                    ->orWhere('credit_department_name', $departmentName);
            })
            ->whereNotNull('company_name_short')
            ->where('company_name_short', '<>', '')
            ->orderByDesc('journal_entry_id')
            ->value('company_name_short') ?? ''));
    }

    private function storeDailyReportStoreOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->select('store_name')
            ->where('business_type', '（店舗）')
            ->whereNotNull('store_name')
            ->where('store_name', '<>', '')
            ->orderBy('store_code')
            ->get()
            ->map(fn($row): string => trim((string) ($row->store_name ?? '')))
            ->filter(fn(string $storeName): bool => $storeName !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function formatNumberValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value);
    }

    private function formatDateWithJapaneseWeekday(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return '';
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return date('Y/m/d', $timestamp) . '(' . $weekdays[(int) date('w', $timestamp)] . ')';
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function moneyToDatabaseValue(mixed $value): ?float
    {
        $value = str_replace(',', '', trim((string) ($value ?? '')));

        return $value === '' ? null : (float) $value;
    }

    private function integerToDatabaseValue(mixed $value): ?int
    {
        $value = str_replace(',', '', trim((string) ($value ?? '')));

        return $value === '' ? null : (int) $value;
    }

    private function checkboxToDatabaseValue(mixed $value): int
    {
        return !empty($value) ? 1 : 0;
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime(str_replace('/', '-', $value));

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    private function normalizeDateTimeValue(mixed $dateValue, mixed $timeValue): ?string
    {
        $date = $this->normalizeDateValue($dateValue);
        $time = trim((string) ($timeValue ?? ''));
        if ($date === null || $time === '') {
            return null;
        }

        $timestamp = strtotime($date . ' ' . $time);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
