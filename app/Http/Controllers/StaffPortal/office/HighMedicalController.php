<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HighMedicalController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        $selectedStore = $this->selectedStore($request);
        $selectedStatus = trim((string) $request->query('payment_status', 'unpaid'));
        $selectedSubjectName = trim((string) $request->query('subject_name', ''));
        $selectedBankDepositDate = trim((string) $request->query('bank_deposit_date', ''));
        if (!in_array($selectedStatus, ['all', 'unpaid', 'paid'], true)) {
            $selectedStatus = 'unpaid';
        }

        // DBクエリ
        $rowsQuery = $this->receiptDetailBaseQuery()
            ->select([
                'detail.insurance_claim_detail_id',
                'detail.treatment_month',
                'detail.insurer_number',
                'detail.subject_name',
                'detail.remarks',
                // 'detail.department_no',
                'department.store_category',
                'insurer.insurance_category',
                'insurer.insurance_type',
                'insurer.insurer_name',
                'detail.claim_amount',
                'detail.is_high_cost_medical',
                'detail.bank_deposit_date',
                'detail.bank_deposit_amount',
                'detail.is_invoiced',
                'department.bank_account_selection',
                'company.company_name',
            ])
            ->where(function ($query): void {
                $query->where('detail.is_high_cost_medical', true)
                    ->orWhere('detail.is_high_cost_medical', 1);
            })
            ->where('detail.insurer_number', '<>', '99999999');

        if ($selectedStore !== '') {
            $rowsQuery->where('department.store_category', $selectedStore);
        }

        if ($selectedSubjectName !== '') {
            $rowsQuery->where('detail.subject_name', $selectedSubjectName);
        }

        if ($selectedBankDepositDate !== '') {
            $rowsQuery->whereDate('detail.bank_deposit_date', $selectedBankDepositDate);
        }

        if ($selectedStatus === 'unpaid') {
            $rowsQuery->whereRaw('COALESCE(detail.bank_deposit_amount, 0) = 0');
        } elseif ($selectedStatus === 'paid') {
            $rowsQuery->whereRaw('COALESCE(detail.bank_deposit_amount, 0) <> 0');
        }

        // 患者選択
        $patientOptionsQuery = $this->highMedicalBaseQuery()
            ->select(['detail.subject_name'])
            ->whereNotNull('detail.subject_name');

        if ($selectedStore !== '') {
            $patientOptionsQuery->where('department.store_category', $selectedStore);
        }

        $patientOptions = $patientOptionsQuery
            ->orderBy('detail.subject_name')
            ->get()
            ->map(fn($row): string => trim((string) ($row->subject_name ?? '')))
            ->filter(fn(string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();

        // 入金日選択
        $bankDepositDateOptionsQuery = $this->highMedicalBaseQuery()
            ->select(['detail.bank_deposit_date'])
            ->whereRaw('COALESCE(detail.bank_deposit_amount, 0) <> 0')
            ->whereNotNull('detail.bank_deposit_date')
            ->where('detail.bank_deposit_date', '<>', '');

        if ($selectedStore !== '') {
            $bankDepositDateOptionsQuery->where('department.store_category', $selectedStore);
        }

        if ($selectedSubjectName !== '') {
            $bankDepositDateOptionsQuery->where('detail.subject_name', $selectedSubjectName);
        }

        $bankDepositDateOptions = $bankDepositDateOptionsQuery
            ->orderByDesc('detail.bank_deposit_date')
            ->get()
            ->map(
                fn($row): string => !empty($row->bank_deposit_date)
                    ? date('Y/m/d', strtotime($row->bank_deposit_date))
                    : ''
            )
            ->filter(fn(string $date): bool => $date !== '')
            ->unique()
            ->values()
            ->all();

        // 一覧表示
        $receiptRows = $rowsQuery
            ->orderByDesc('detail.treatment_month')
            ->orderBy('detail.insurer_number')
            ->orderBy('detail.insurance_claim_detail_id')
            ->get()
            ->map(fn($row): array => [
                'insurance_claim_detail_id' => (int) ($row->insurance_claim_detail_id ?? 0),
                'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m/d'),
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'store_name' => trim((string) (($row->store_category ?? '') !== '' ? $row->store_category : ($row->store_name ?? ''))),
                'subject_name' => trim((string) ($row->subject_name ?? '')),
                'remarks' => trim((string) ($row->remarks ?? '')),
                'insurance_category' => trim((string) ($row->insurance_category ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'insurance_type' => trim((string) ($row->insurance_type ?? '')),
                'high_cost_medical' => (bool) ($row->is_high_cost_medical ?? false) ? '1' : '',
                'claim_amount' => $this->formatMoneyValue($row->claim_amount),
                'bank_deposit_date' => $row->bank_deposit_date ? date('Y/m/d', strtotime($row->bank_deposit_date)) : '',
                'bank_deposit_amount' => $this->formatMoneyValue($row->bank_deposit_amount),
                'bank_account_selection' => trim((string) ($row->bank_account_selection ?? '')),
                'is_invoiced' => (bool) ($row->is_invoiced ?? false) ? '1' : '',
                'company_name' => trim((string) ($row->company_name ?? '')),
            ])
            ->all();

        return view('staff_portal.office.receipt.high_medical.index',  $this->commonViewData($request, [
            'selectedStore' => $selectedStore,
            'selectedStatus' => $selectedStatus,
            'selectedSubjectName' => $selectedSubjectName,
            'selectedBankDepositDate' => $selectedBankDepositDate,
            'storeOptions' => $this->receiptStoreOptions(),
            'patientOptions' => $patientOptions,
            'bankDepositDateOptions' => $bankDepositDateOptions,
            'receiptRows' => $receiptRows,
        ]));
    }

    // 保存
    public function save(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $data = $request->validate([
            'insurance_claim_detail_id' => ['required', 'integer'],
            'bank_deposit_amount' => ['nullable', 'string'],
            'is_invoiced' => ['nullable', 'boolean'],
            'subject_name' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['nullable', 'string', 'max:20'],
            'filter_subject_name' => ['nullable', 'string', 'max:255'],
            'filter_bank_deposit_date' => ['nullable', 'string', 'max:255'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->where('insurance_claim_detail_id', (int) $data['insurance_claim_detail_id'])
            ->update([
                'bank_deposit_amount' => $this->parseMoneyInput($data['bank_deposit_amount'] ?? null),
                'is_invoiced' => $request->boolean('is_invoiced'),
                'subject_name' => trim((string) ($data['subject_name'] ?? '')),
                'remarks' => trim((string) ($data['remarks'] ?? '')),
            ]);

        return redirect()->route('office.receipt.high_medical', [
            'store_name' => trim((string) ($data['store_name'] ?? '')),
            'payment_status' => trim((string) ($data['payment_status'] ?? 'unpaid')),
            'subject_name' => trim((string) ($data['filter_subject_name'] ?? '')),
            'bank_deposit_date' => trim((string) ($data['filter_bank_deposit_date'] ?? '')),
        ]);
    }

    // 高額療養費共通条件
    private function highMedicalBaseQuery()
    {
        return $this->receiptDetailBaseQuery()
            ->where(function ($query): void {
                $query->where('detail.is_high_cost_medical', true)
                    ->orWhere('detail.is_high_cost_medical', 1);
            })
            ->where('detail.insurer_number', '<>', '99999999');
    }

    private function parseMoneyInput(mixed $value): ?float
    {
        $normalized = str_replace(',', '', trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        return (float) $normalized;
    }
}
