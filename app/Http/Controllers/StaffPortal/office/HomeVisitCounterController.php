<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\SelectOptionService;

class HomeVisitCounterController extends Controller
{
    use HandlesStaffPortalContext;

    private const RECEIPT_MONTHLY_CLOSING_AUTHORITY = '入金確認';

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $targetMonth = $this->targetMonth($request);
        $selectedStore = trim((string) $request->query('store_name', ''));
        $selectedPatientName = trim((string) $request->query('patient_name', ''));
        $showUnpaidOnly = trim((string) $request->query('unpaid_only', '')) === '1';
        $is_admin = (int) (($this->staffPortalStaffRow($staffId) ?? [])['is_admin'] ?? 0) === 1;

        $targetMonthDate = $this->targetMonthStartDate($targetMonth);
        $targetMonthStart = $targetMonthDate->format('Y-m-d');
        $targetMonthNext = $targetMonthDate->copy()->addMonthNoOverflow()->format('Y-m-d');
        $targetMonthEnd = $targetMonthDate->copy()->endOfMonth()->startOfDay();
        $monthlyClosingRow = $this->receiptMonthlyClosingRow($targetMonthEnd);
        $isReceiptMonthlyClosed = $monthlyClosingRow !== null;

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_insurers as insurer', 'detail.insurer_number', '=', 'insurer.insurer_number')
            ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
            // ->leftJoin('dbo.mx_departments as department', 'detail.department_no', '=', 'department.department_no')
            ->leftJoin('dbo.mx_staffs as staff', 'detail.staff_name', '=', 'staff.staff_id')
            ->select([
                'detail.insurance_claim_detail_id',
                'detail.treatment_month',
                'detail.insurer_number',
                'insurer.insurer_name',
                'detail.deposit_name',
                'detail.subject_name',
                'detail.payment_date_text',
                'detail.payment_amount',
                'detail.adjustment_amount',
                'detail.cash_collected_amount',
                'detail.staff_name',
                // 'detail.department_no',
                'detail.store_name',
                'department.store_category',
                'detail.remarks',
                DB::raw("LTRIM(RTRIM(COALESCE(staff.display_name_ja, staff.staff_name, detail.staff_name, ''))) as staff_display_name"),
            ])
            ->where('detail.insurer_number', '99999999');

        if ($showUnpaidOnly) {
            $rowsQuery->whereYear('detail.treatment_month', '>=', 2022);
            $rowsQuery
                ->where('detail.deposit_name', 'ｺｼﾞﾝﾌﾘｺﾐ')
                ->whereRaw('(COALESCE(detail.adjustment_amount, 0) + COALESCE(detail.cash_collected_amount, 0) - COALESCE(detail.payment_amount, 0)) <> 0');
        } elseif ($selectedPatientName === '') {
            $rowsQuery
                ->where('detail.treatment_month', '>=', $targetMonthStart)
                ->where('detail.treatment_month', '<', $targetMonthNext);
        }

        if ($selectedStore !== '') {
            $rowsQuery->where('department.store_category', $selectedStore);
        }

        if ($selectedPatientName !== '') {
            $rowsQuery->where('detail.subject_name', 'like', '%' . $selectedPatientName . '%');
        }

        $homeVisitRows = $rowsQuery
            ->orderByDesc('detail.treatment_month')
            ->orderByDesc('detail.insurance_claim_detail_id')
            ->get()
            ->map(fn($row): array => [
                'insurance_claim_detail_id' => (int) ($row->insurance_claim_detail_id ?? 0),
                'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m/d'),
                'treatment_month_raw' => $this->formatDateValue($row->treatment_month, 'Y/m/d'),
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                'deposit_name_raw' => trim((string) ($row->deposit_name ?? '')),
                'patient_name' => trim((string) ($row->subject_name ?? '')),
                'patient_name_raw' => trim((string) ($row->subject_name ?? '')),
                'payment_date_text' => trim((string) ($row->payment_date_text ?? '')),
                'payment_amount' => $this->formatMoneyValue($row->payment_amount),
                'remaining_amount' => (function () use ($row) {
                    $remainingAmount = ((int) ($row->adjustment_amount ?? 0))
                        + ((int) ($row->cash_collected_amount ?? 0))
                        - ((int) ($row->payment_amount ?? 0));

                    return $remainingAmount === 0 ? '0' : $this->formatMoneyValue($remainingAmount);
                })(),
                'cash_collected_amount' => $this->formatMoneyValue($row->cash_collected_amount),
                'cash_collected_amount_value' => (float) ($row->cash_collected_amount ?? 0),
                'cash_collected_amount_raw' => $row->cash_collected_amount === null ? '' : rtrim(rtrim((string) $row->cash_collected_amount, '0'), '.'),
                'staff_name' => trim((string) ($row->staff_display_name ?? '')),
                'staff_id_raw' => trim((string) ($row->staff_name ?? '')),
                'store_name' => trim((string) ($row->store_category ?? '')),
                'store_name_raw' => trim((string) ($row->store_category ?? '')),
                'remarks' => trim((string) ($row->remarks ?? '')),
                'remarks_raw' => trim((string) ($row->remarks ?? '')),
            ])
            ->all();

        $cashCollectedTotal = array_sum(array_map(
            static fn(array $row): float => (float) ($row['cash_collected_amount_value'] ?? 0),
            $homeVisitRows
        ));

        $storeOptions = $this->receiptStoreOptions();
        if ($selectedStore !== '' && !in_array($selectedStore, $storeOptions, true)) {
            array_unshift($storeOptions, $selectedStore);
        }

        $patientNameOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->select(['subject_name'])
            ->where('insurer_number', '99999999')
            ->whereNotNull('subject_name')
            ->orderBy('subject_name')
            ->get()
            ->map(fn($row): string => trim((string) ($row->subject_name ?? '')))
            ->filter(fn(string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();

        // $targetMonthStart = Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth()->format('Y-m-d');
        $targetMonthStart = $this->targetMonthStartDate($targetMonth)->format('Y-m-d');


        $staffNameOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id'])
            ->selectRaw("LTRIM(RTRIM(COALESCE(display_name_ja, staff_name, ''))) as staff_label")
            ->where(function ($query): void {
                $query->where('oushin_staff', true)
                    ->orWhere('oushin_staff', 1);
            })
            ->where(function ($query) use ($targetMonthStart): void {
                $query->whereNull('tai_date')
                    ->orWhereDate('tai_date', '>=', $targetMonthStart);
            })
            ->orderByRaw("LTRIM(RTRIM(COALESCE(display_name_ja, staff_name, '')))")
            ->get()
            ->map(fn($row): array => [
                'value' => trim((string) ($row->staff_id ?? '')),
                'label' => trim((string) ($row->staff_label ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['value'] !== '' && $row['label'] !== '')
            ->unique()
            ->values()
            ->all();

        $depositNameOptions = [
            'ｵｳｼﾝﾏﾄﾞｸﾞﾁ',
            'ｼﾞﾋ',
            'ｼﾞﾋｺｳｻﾞ',
            'ﾁｮｳｾｲ',
            'ｺｼﾞﾝﾌﾘｺﾐ',
        ];

        $insurerName99999999 = trim((string) (DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->where('insurer_number', '99999999')
            ->value('insurer_name') ?? ''));

        return view('staff_portal.office.receipt.home_visit_counter.index', $this->commonViewData($request, [
            'targetMonth' => $targetMonth,
            'selectedStore' => $selectedStore,
            'selectedPatientName' => $selectedPatientName,
            'showUnpaidOnly' => $showUnpaidOnly,
            'storeOptions' => $storeOptions,
            'depositNameOptions' => $depositNameOptions,
            'patientNameOptions' => $patientNameOptions,
            'staffNameOptions' => $staffNameOptions,
            'insurerName99999999' => $insurerName99999999,
            'homeVisitRows' => $homeVisitRows,
            'cashCollectedTotal' => $cashCollectedTotal,
            'is_admin' => $is_admin,
            'isReceiptMonthlyClosed' => $isReceiptMonthlyClosed,
            'receiptMonthlyClosingProcessedAt' => $this->formatDateValue($monthlyClosingRow?->processed_at, 'Y/m/d'),
        ]));
    }

    public function save(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $is_admin = (int) (($this->staffPortalStaffRow($staffId) ?? [])['is_admin'] ?? 0) === 1;

        $data = $request->validate([
            'insurance_claim_detail_id' => ['nullable', 'integer'],
            'target_month' => ['required', 'date_format:Y-m'],
            'row_treatment_month' => ['nullable', 'date'],
            'store_name' => ['nullable', 'string', 'max:20'],
            'deposit_name' => ['nullable', 'string', 'max:50'],
            // mx_insurance_claim_details.subject_name/payment_date_textの実カラム長に合わせる
            // （50/255まで許可していたせいで、長い文字列を保存するとバリデーションを通過した後に
            // 生のSQLエラー（文字列切り捨て）で落ちていた）。
            'patient_name' => ['nullable', 'string', 'max:20'],
            'payment_date_text' => ['nullable', 'string', 'max:20'],
            'payment_amount' => ['nullable', 'string'],
            'cash_collected_amount' => ['nullable', 'string'],
            'staff_name' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'filter_store_name' => ['nullable', 'string', 'max:20'],
            'filter_patient_name' => ['nullable', 'string', 'max:50'],
            'filter_unpaid_only' => ['nullable', 'string'],
        ]);

        $detailId = (int) ($data['insurance_claim_detail_id'] ?? 0);
        $submittedTreatmentMonth = trim((string) ($data['row_treatment_month'] ?? ''));

        if ($detailId > 0 && $submittedTreatmentMonth !== '') {
            $targetMonth = Carbon::parse(str_replace('/', '-', $submittedTreatmentMonth))->startOfDay();
        } elseif ($detailId > 0) {
            $existingTreatmentMonth = DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->where('insurance_claim_detail_id', $detailId)
                ->value('treatment_month');
            $targetMonth = $existingTreatmentMonth !== null
                ? Carbon::parse((string) $existingTreatmentMonth)->startOfDay()
                : Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->endOfMonth()->startOfDay();
        } else {
            $targetMonth = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')
                ->endOfMonth()
                ->startOfDay();
        }

        $submittedStoreName = trim((string) ($data['store_name'] ?? ''));
        $resolvedStoreShortName = '';

        if ($submittedStoreName !== '') {
            $departmentRow = DB::connection('sqlsrv')
                ->table('dbo.mx_departments')
                ->where('store_category', $submittedStoreName)
                ->select(['store_short_name'])
                ->first();

            $resolvedStoreShortName = trim((string) ($departmentRow?->store_short_name ?? ''));
        }

        $payload = [
            'insurer_number' => '99999999',
            'treatment_month' => $targetMonth,
            'store_name' => $resolvedStoreShortName,
            'deposit_name' => trim((string) ($data['deposit_name'] ?? '')),
            'subject_name' => trim((string) ($data['patient_name'] ?? '')),
            'cash_collected_amount' => $this->parseMoneyInput($data['cash_collected_amount'] ?? null),
            'staff_name' => trim((string) ($data['staff_name'] ?? '')),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
        ];

        if ($is_admin) {
            $payload['payment_date_text'] = trim((string) ($data['payment_date_text'] ?? ''));
            $payload['payment_amount'] = $this->parseMoneyInput($data['payment_amount'] ?? null);
        }

        if ($detailId > 0) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->where('insurance_claim_detail_id', $detailId)
                ->update($payload);
        } else {
            DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->insert($payload);
        }

        return redirect()->route('office.receipt.home_visit_counter', array_filter([
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['filter_store_name'] ?? '')),
            'patient_name' => trim((string) ($data['filter_patient_name'] ?? '')),
            'unpaid_only' => trim((string) ($data['filter_unpaid_only'] ?? '')) === '1' ? '1' : null,
        ], fn($value): bool => $value !== null && $value !== ''))->with('successMessage', '往診窓口を保存しました');
    }

    public function delete(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $data = $request->validate([
            'insurance_claim_detail_id' => ['required', 'integer'],
            'target_month' => ['required', 'date_format:Y-m'],
            'store_name' => ['nullable', 'string', 'max:20'],
            'filter_store_name' => ['nullable', 'string', 'max:20'],
            'filter_patient_name' => ['nullable', 'string', 'max:50'],
            'filter_unpaid_only' => ['nullable', 'string'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->where('insurance_claim_detail_id', (int) $data['insurance_claim_detail_id'])
            ->delete();

        return redirect()->route('office.receipt.home_visit_counter', array_filter([
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['filter_store_name'] ?? '')),
            'patient_name' => trim((string) ($data['filter_patient_name'] ?? '')),
            'unpaid_only' => trim((string) ($data['filter_unpaid_only'] ?? '')) === '1' ? '1' : null,
        ], fn($value): bool => $value !== null && $value !== ''))->with('successMessage', '往診窓口を削除しました');
    }

    private function parseMoneyInput(mixed $value): ?float
    {
        $normalized = str_replace(',', '', trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        return (float) $normalized;
    }

    private function receiptMonthlyClosingRow(Carbon $targetMonth): ?object
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->whereDate('closing_month', $targetMonth->format('Y-m-d'))
            ->where('authority', self::RECEIPT_MONTHLY_CLOSING_AUTHORITY)
            ->first();
    }

    private function isReceiptMonthlyClosed(Carbon $targetMonth): bool
    {
        return $this->receiptMonthlyClosingRow($targetMonth) !== null;
    }
}
