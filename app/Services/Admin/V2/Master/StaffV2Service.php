<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StaffV2Service
{
    /** @var array<string, string>|null */
    private ?array $submissionMayorMap = null;

    /** @var array<string, bool> */
    private array $columnExistsCache = [];

    /** @var list<array{value:string,key:string,label:string,mayor:string,company_id:string,company_name:string}>|null */
    private ?array $residentSubmissionOptions = null;

    /** @var array<string, bool> */
    private array $tableColumnExistsCache = [];

    public function list(string $keyword, string $employmentFilter = 'active', string $companyFilter = ''): array
    {
        $companyFilter = trim($companyFilter);
        $query = DB::connection('sqlsrv')->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.section', '=', 'st.store_code')
            ->leftJoin('dbo.mx_companies as co', 'st.company_id', '=', 'co.company_id')
            ->select([
                DB::raw('ms.staff_id as staff_id'),
                'ms.staff_name',
                DB::raw('ms.staff_name_furi as staff_name_kana'),
                DB::raw('ms.section as store_code'),
                DB::raw('st.store_name as store_name'),
                DB::raw('st.company_id as company_id'),
                DB::raw('co.company_name as company_name'),
                DB::raw('ms.employment as employment_status'),
                DB::raw('ms.is_store_management_user as is_store_manager'),
                'ms.is_daily_report_user',
                DB::raw('ms.tai_date as retire_date'),
            ])
            ->orderBy('ms.staff_id');

        if ($employmentFilter === 'active') {
            $query->where(function ($q): void {
                $q->whereNull('ms.tai_date')
                    ->orWhere(DB::raw("LTRIM(RTRIM(CAST(ms.tai_date AS nvarchar(50))))"), '=', '')
                    ->orWhere(function ($dateQ): void {
                        $dateQ->whereNotNull('ms.tai_date')
                            ->whereDate('ms.tai_date', '>=', now()->toDateString());
                    });
            });
        } elseif ($employmentFilter === 'retired') {
            $query->whereNotNull('ms.tai_date')
                ->where(DB::raw("LTRIM(RTRIM(CAST(ms.tai_date AS nvarchar(50))))"), '<>', '')
                ->whereDate('ms.tai_date', '<', now()->toDateString());
        }

        if ($companyFilter !== '') {
            $query->whereRaw('LTRIM(RTRIM(CAST(st.company_id AS nvarchar(50)))) = ?', [$companyFilter]);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('ms.staff_id', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_name', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_name_furi', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.section', 'like', '%' . $keyword . '%')
                    ->orWhere('st.store_name', 'like', '%' . $keyword . '%')
                    ->orWhere('co.company_name', 'like', '%' . $keyword . '%');
            });
        }

        return $query->get()->map(fn ($r) => [
            'staff_id' => (string) ($r->staff_id ?? ''),
            'staff_name' => (string) ($r->staff_name ?? ''),
            'staff_name_kana' => (string) ($r->staff_name_kana ?? ''),
            'store_code' => (string) ($r->store_code ?? ''),
            'store_name' => (string) ($r->store_name ?? ''),
            'company_id' => (string) ($r->company_id ?? ''),
            'company_name' => (string) ($r->company_name ?? ''),
            'employment_status' => (string) ($r->employment_status ?? ''),
            'is_store_manager' => (int) ($r->is_store_manager ?? 0),
            'is_daily_report_user' => (int) ($r->is_daily_report_user ?? 0),
            'retire_date' => (string) ($r->retire_date ?? ''),
        ])->all();
    }

    public function detail(?string $staffId): ?array
    {
        $staffId = trim((string) $staffId);
        if ($staffId === '') {
            return null;
        }

        $columns = Schema::connection('sqlsrv')->getColumnListing('mx_staffs');

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.section', '=', 'st.store_code')
            ->leftJoin('dbo.mx_companies as co', 'st.company_id', '=', 'co.company_id')
            ->where('ms.staff_id', $staffId)
            ->select([
                'ms.*',
                DB::raw('st.store_name as _store_name'),
                DB::raw('co.company_name as _company_name'),
            ])
            ->first();

        if (!$row) {
            return null;
        }

        $detail = [];
        foreach ($columns as $column) {
            $rawValue = $row->{$column} ?? null;
            $detail[$column] = $this->normalizeValue($rawValue);
            $detail['_raw_' . $column] = $this->rawValue($rawValue);
        }
        $detail['submission'] = $this->resolveSubmissionLabel($detail['submission'] ?? '');
        $detail['employment_status'] = $this->normalizeValue($row->employment ?? null);
        $detail['_store_name'] = $this->normalizeValue($row->_store_name ?? null);
        $detail['_company_name'] = $this->normalizeValue($row->_company_name ?? null);
        $detail['_age'] = $this->calculateAgeLabel($row->birthday ?? null);

        return $detail;
    }

    public function blankDetail(): array
    {
        $columns = Schema::connection('sqlsrv')->getColumnListing('mx_staffs');

        $detail = [];
        foreach ($columns as $column) {
            $detail[$column] = '';
            $detail['_raw_' . $column] = '';
        }
        $detail['employment_status'] = '';
        $detail['_store_name'] = '';
        $detail['_company_name'] = '';
        $detail['_age'] = '';

        return $detail;
    }

    /** @return list<array{store_code:string,store_name:string}> */
    public function storeOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->where(function ($query) {
                $query->where('is_closed', false)
                    ->orWhere('is_closed', 0)
                    ->orWhereNull('is_closed');
            })
            ->select(['store_code', 'store_name'])
            ->orderBy('store_code')
            ->get()
            ->map(static fn ($row): array => [
                'store_code' => trim((string) ($row->store_code ?? '')),
                'store_name' => trim((string) ($row->store_name ?? '')),
            ])
            ->filter(static fn (array $row): bool => $row['store_code'] !== '')
            ->values()
            ->all();
    }

    /** @return list<array{value:string,key:string,label:string,mayor:string,company_id:string,company_name:string}> */
    public function residentSubmissionOptions(): array
    {
        if ($this->residentSubmissionOptions !== null) {
            return $this->residentSubmissionOptions;
        }

        $companies = DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->select(['company_id', 'company_name'])
            ->get()
            ->mapWithKeys(static fn ($row): array => [
                trim((string) ($row->company_id ?? '')) => trim((string) ($row->company_name ?? '')),
            ]);

        $this->residentSubmissionOptions = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_mayor')
            ->select(['mayor_no', 'mayor', 'office_no'])
            ->orderBy('mayor')
            ->orderBy('office_no')
            ->get()
            ->map(function ($row) use ($companies): array {
                $value = trim((string) ($row->mayor_no ?? ''));
                $companyId = trim((string) ($row->office_no ?? ''));
                $mayor = trim((string) ($row->mayor ?? ''));
                $companyName = trim((string) ($companies[$companyId] ?? ''));
                $companyLabel = $companyName !== '' ? $companyName : $companyId;

                return [
                    'value' => $value,
                    'key' => $this->normalizeMayorKey($value),
                    'label' => $companyLabel !== '' ? $mayor . ' / ' . $companyLabel : $mayor,
                    'mayor' => $mayor,
                    'company_id' => $companyId,
                    'company_name' => $companyName,
                ];
            })
            ->filter(static fn (array $row): bool => $row['value'] !== '' && $row['mayor'] !== '')
            ->values()
            ->all();

        return $this->residentSubmissionOptions;
    }

    /** @return list<string> */
    public function employmentOptions(): array
    {
        return ['在職', '退職', '転籍', '育児休業中', '産後休業中', '介護休業中', '傷病休業中', '採用予定'];
    }

    /** @return list<string> */
    public function staffDivisionOptions(): array
    {
        return ['役員', '兼務役員', '正社員', 'パート', 'アルバイト', '業務委託'];
    }

    /** @return list<string> */
    public function weekOptions(): array
    {
        return ['月', '火', '水', '木', '金', '土', '日'];
    }

    /** @return list<string> */
    public function fuyoRelationshipOptions(): array
    {
        return ['夫', '妻', '父', '母', '子', '次男', '三男', '四男', '五男', '長女', '次女', '三女', '四女', '五女'];
    }

    /** @return list<string> */
    public function fuyoSexOptions(): array
    {
        return ['男', '女'];
    }

    /** @return list<string> */
    public function kyojyuOptions(): array
    {
        return ['同居', '別居'];
    }

    /** @return list<string> */
    public function failureJudgmentOptions(): array
    {
        return ['1級', '2級', '3級', '4級', '5級', '6級', '7級', 'A1', 'A2', 'B1', 'B2'];
    }

    public function update(array $values): void
    {
        $staffId = trim((string) ($values['staff_id'] ?? ''));
        if ($staffId === '') {
            return;
        }

        $payload = [];
        foreach ($this->editableColumns() as $column) {
            if (!$this->hasColumn($column)) {
                continue;
            }

            if ($column === 'submission' && !array_key_exists('submission', $values)) {
                continue;
            }
            if (in_array($column, $this->staffInsuranceColumns(), true) && !array_key_exists($column, $values)) {
                continue;
            }

            if (in_array($column, $this->booleanColumns(), true)) {
                $payload[$column] = (($values[$column] ?? null) === '1') ? 1 : 0;
                continue;
            }

            $value = $this->nullable($values[$column] ?? null);
            if ($value !== null && in_array($column, $this->numericLikeColumns(), true)) {
                $value = str_replace(',', '', $value);
            }
            $payload[$column] = $value;
        }

        if ($payload === []) {
            return;
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->update($payload);
    }

    public function create(array $values): void
    {
        $staffId = trim((string) ($values['staff_id'] ?? ''));
        if ($staffId === '') {
            return;
        }

        $exists = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['staff_id' => 'このスタッフIDは既に登録されています。']);
        }

        $payload = [];
        if ($this->hasColumn('staff_id')) {
            $payload['staff_id'] = $staffId;
        }

        foreach ($this->editableColumns() as $column) {
            if (!$this->hasColumn($column)) {
                continue;
            }

            if ($column === 'submission' && !array_key_exists('submission', $values)) {
                continue;
            }
            if (in_array($column, $this->staffInsuranceColumns(), true) && !array_key_exists($column, $values)) {
                continue;
            }

            if (in_array($column, $this->booleanColumns(), true)) {
                $payload[$column] = (($values[$column] ?? null) === '1') ? 1 : 0;
                continue;
            }

            $value = $this->nullable($values[$column] ?? null);
            if ($value !== null && in_array($column, $this->numericLikeColumns(), true)) {
                $value = str_replace(',', '', $value);
            }
            $payload[$column] = $value;
        }

        DB::connection('sqlsrv')->table('dbo.mx_staffs')->insert($payload);
    }

    public function updateSubmission(array $values): void
    {
        $this->updateStaffColumns($values, ['submission', 'addressee_no']);
    }

    public function updateInsurance(array $values): void
    {
        $this->updateStaffColumns($values, $this->staffInsuranceColumns());
    }

    private function updateStaffColumns(array $values, array $columns): void
    {
        $staffId = trim((string) ($values['staff_id'] ?? ''));
        if ($staffId === '') {
            return;
        }

        $payload = [];
        foreach ($columns as $column) {
            if (!$this->hasColumn($column)) {
                continue;
            }

            if (in_array($column, $this->booleanColumns(), true)) {
                $payload[$column] = (($values[$column] ?? null) === '1') ? 1 : 0;
                continue;
            }

            $payload[$column] = $this->nullable($values[$column] ?? null);
        }

        if ($payload !== []) {
            DB::connection('sqlsrv')->table('dbo.mx_staffs')->where('staff_id', $staffId)->update($payload);
        }
    }

    public function relatedRows(string $staffId, string $table, array $orderColumns = []): array
    {
        if (!Schema::connection('sqlsrv')->hasTable($table)) {
            return ['columns' => [], 'rows' => []];
        }

        $columns = Schema::connection('sqlsrv')->getColumnListing($table);
        if (!in_array('staff_id', $columns, true)) {
            return ['columns' => $columns, 'rows' => []];
        }

        $query = DB::connection('sqlsrv')->table('dbo.' . $table)->where('staff_id', $staffId);
        foreach ($orderColumns as $column) {
            if (in_array($column, $columns, true)) {
                $query->orderByDesc($column);
            }
        }

        $rows = $query->get()->map(function ($row) use ($columns) {
            $mapped = [];
            foreach ($columns as $column) {
                $mapped[$column] = $this->normalizeValue($row->{$column} ?? null);
            }
            return $mapped;
        })->all();

        return ['columns' => $columns, 'rows' => $rows];
    }

    public function basicShiftRows(string $staffId): array
    {
        return $this->tableRows('mx_kihon_shifts', $staffId, ['shift_no'], 'sqlsrv', 'staff_name');
    }

    public function kihonRows(string $staffId): array
    {
        return $this->tableRows('mx_kihon', $staffId, ['decision_date', 'kihon_no'], 'sqlsrv_payroll');
    }

    public function shahoRows(string $staffId): array
    {
        return $this->tableRows('mx_staff_shou', $staffId, ['raise_year', 'staff_shou_id'], 'sqlsrv_payroll');
    }

    public function residentRows(string $staffId): array
    {
        return $this->tableRows('mx_resident', $staffId, ['target_month', 'resident_no'], 'sqlsrv_payroll');
    }

    public function fuyoRows(string $staffId): array
    {
        return $this->tableRows('mx_fuyo', $staffId, ['registration_date', 'fuyo_no'], 'sqlsrv_payroll');
    }

    public function createKihon(array $values): void
    {
        $this->insertRow('mx_kihon', $values, $this->kihonColumns(), 'sqlsrv_payroll');
    }

    public function updateKihon(array $values): void
    {
        $this->updateRow('mx_kihon', 'kihon_no', $values['kihon_no'] ?? '', $values, $this->kihonColumns(), 'sqlsrv_payroll');
    }

    public function deleteKihon(array $values): void
    {
        $this->deleteRow('mx_kihon', 'kihon_no', $values['kihon_no'] ?? '', 'sqlsrv_payroll');
    }

    public function createShaho(array $values): void
    {
        $this->insertRow('mx_staff_shou', $values, $this->shahoColumns(), 'sqlsrv_payroll');
    }

    public function updateShaho(array $values): void
    {
        $this->updateRow('mx_staff_shou', 'staff_shou_id', $values['staff_shou_id'] ?? '', $values, $this->shahoColumns(), 'sqlsrv_payroll');
    }

    public function deleteShaho(array $values): void
    {
        $this->deleteRow('mx_staff_shou', 'staff_shou_id', $values['staff_shou_id'] ?? '', 'sqlsrv_payroll');
    }

    public function createResident(array $values): void
    {
        $this->insertRow('mx_resident', $this->residentValues($values), $this->residentColumns(), 'sqlsrv_payroll');
    }

    public function updateResident(array $values): void
    {
        $this->updateRow('mx_resident', 'resident_no', $values['resident_no'] ?? '', $this->residentValues($values), $this->residentColumns(), 'sqlsrv_payroll');
    }

    public function deleteResident(array $values): void
    {
        $this->deleteRow('mx_resident', 'resident_no', $values['resident_no'] ?? '', 'sqlsrv_payroll');
    }

    public function createFuyo(array $values): void
    {
        $this->insertRow('mx_fuyo', $values, $this->fuyoColumns(), 'sqlsrv_payroll');
    }

    public function updateFuyo(array $values): void
    {
        $this->updateRow('mx_fuyo', 'fuyo_no', $values['fuyo_no'] ?? '', $values, $this->fuyoColumns(), 'sqlsrv_payroll');
    }

    public function deleteFuyo(array $values): void
    {
        $this->deleteRow('mx_fuyo', 'fuyo_no', $values['fuyo_no'] ?? '', 'sqlsrv_payroll');
    }

    public function createBasicShift(array $values): void
    {
        $this->insertRow('mx_kihon_shifts', $this->basicShiftValues($values), $this->basicShiftColumns(), 'sqlsrv');
    }

    public function updateBasicShift(array $values, bool $clear): void
    {
        $payload = $clear ? array_fill_keys(['shift_in', 'shift_exit', 'shift_entry', 'shift_out', 'section'], null) : $this->basicShiftValues($values);
        $this->updateRow('mx_kihon_shifts', 'shift_no', $values['shift_no'] ?? '', $payload, array_keys($payload), 'sqlsrv');
    }

    private function tableRows(string $table, string $staffId, array $orderColumns, string $connection, string $staffColumn = 'staff_id'): array
    {
        if (!Schema::connection($connection)->hasTable($table)) {
            return [];
        }

        $columns = Schema::connection($connection)->getColumnListing($table);
        if (!in_array($staffColumn, $columns, true)) {
            return [];
        }

        $query = DB::connection($connection)->table('dbo.' . $table)->where($staffColumn, $staffId);
        foreach ($orderColumns as $column) {
            if (in_array($column, $columns, true)) {
                $query->orderByDesc($column);
            }
        }

        return $query->get()->map(function ($row) use ($columns): array {
            $mapped = [];
            foreach ($columns as $column) {
                $rawValue = $row->{$column} ?? null;
                $mapped[$column] = $this->normalizeValue($rawValue);
                $mapped['_raw_' . $column] = $this->rawValue($rawValue);
            }
            return $mapped;
        })->all();
    }

    private function insertRow(string $table, array $values, array $columns, string $connection): void
    {
        $payload = $this->payloadFor($table, $values, $columns, $connection, true);
        if ($payload !== []) {
            DB::connection($connection)->table('dbo.' . $table)->insert($payload);
        }
    }

    private function updateRow(string $table, string $idColumn, mixed $idValue, array $values, array $columns, string $connection): void
    {
        $idValue = trim((string) $idValue);
        if ($idValue === '') {
            return;
        }

        $payload = $this->payloadFor($table, $values, $columns, $connection, false);
        if ($payload !== []) {
            DB::connection($connection)->table('dbo.' . $table)->where($idColumn, $idValue)->update($payload);
        }
    }

    private function deleteRow(string $table, string $idColumn, mixed $idValue, string $connection): void
    {
        $idValue = trim((string) $idValue);
        if ($idValue !== '') {
            DB::connection($connection)->table('dbo.' . $table)->where($idColumn, $idValue)->delete();
        }
    }

    private function payloadFor(string $table, array $values, array $columns, string $connection, bool $includeStaffId): array
    {
        $payload = [];
        foreach ($columns as $column) {
            if (!$this->tableHasColumn($connection, $table, $column)) {
                continue;
            }
            if ($column === 'staff_id' && !$includeStaffId) {
                continue;
            }

            if (in_array($column, ['deduction_target', 'widow'], true)) {
                $payload[$column] = (($values[$column] ?? null) === '1') ? 1 : 0;
                continue;
            }

            $value = $this->nullable($values[$column] ?? null);
            if ($value !== null && in_array($column, $this->numericLikeColumns(), true)) {
                $value = str_replace(',', '', $value);
            }
            $payload[$column] = $value;
        }

        return $payload;
    }

    private function residentValues(array $values): array
    {
        $values['target_month'] = $this->normalizeMonthStart($values['target_month'] ?? null);
        return $values;
    }

    private function basicShiftValues(array $values): array
    {
        return [
            'staff_name' => $values['staff_id'] ?? '',
            'week' => $values['week'] ?? '',
            'shift_in' => $values['shift_start'] ?? null,
            'shift_exit' => $values['shift_exit'] ?? null,
            'shift_entry' => $values['shift_in_out'] ?? null,
            'shift_out' => $values['shift_end'] ?? null,
            'section' => $values['shop_code'] ?? null,
        ];
    }

    /** @return list<string> */
    private function editableColumns(): array
    {
        return [
            'staff_name_furi',
            'staff_name',
            'display_name_ja',
            'birthday',
            'sex',
            'section',
            'staff_division',
            'employment',
            'nyu_date',
            'tai_date',
            'my_number',
            'post_num',
            'address_furi',
            'address',
            'home_tel',
            'mobile_tel',
            'head_house',
            'relationship',
            'spouse',
            'syaho_num',
            'koyou_num',
            'syaho_seiri_num',
            'kiso_nenkin_num',
            'syaho',
            'koyou',
            'syaho_date',
            'koyou_date',
            'tax_amount',
            'submission',
            'business_content',
            'has_fixed_term',
            'fixed_term_detail',
            'work_schedule_1',
            'work_schedule_2',
            'trial',
            'yukyu',
            'yukyu_month',
            'working_time',
            'weekly_working_time',
            'year_working_time',
            'car_km',
            'traffic_day',
            'traffic_day_tuika',
            'percentage_1',
            'percentage_2',
            'transfer_purpose',
            'bank_name_1',
            'bank_branch_1',
            'account_type',
            'account_num',
            'bank_name_2',
            'bank_branch_2',
            'account_type2',
            'account_num2',
            'change_history',
            'is_admin',
            'oushin_staff',
            'front_staff',
            'is_accounting_user',
            'is_payment_check_user',
            'is_visit_management_user',
            'is_view_only_user',
            'is_store_management_user',
            'is_daily_report_user',
            'memo',
        ];
    }

    /** @return list<string> */
    private function staffInsuranceColumns(): array
    {
        return ['syaho_num', 'koyou_num', 'syaho_seiri_num', 'kiso_nenkin_num', 'syaho', 'koyou', 'syaho_date', 'koyou_date'];
    }

    /** @return list<string> */
    private function booleanColumns(): array
    {
        return ['syaho', 'koyou', 'spouse', 'trial', 'yukyu', 'has_fixed_term', 'is_admin', 'oushin_staff', 'front_staff', 'is_accounting_user', 'is_payment_check_user', 'is_visit_management_user', 'is_view_only_user', 'is_store_management_user', 'is_daily_report_user'];
    }

    /** @return list<string> */
    private function numericLikeColumns(): array
    {
        return ['monthly_salary', 'hourly_pay', 'hourly_salary', 'executive_remu', 'position_allow', 'duties_allow', 'qualification_allow', 'claim_allow', 'traffic_pay', 'adjustment_add', 'rent_subsidies', 'rent_pay', 'adjustment_pay', 'fixed_overtime', 'kenpo_monthly_amo', 'kounen_monthly_amo', 'resident_tax1', 'resident_tax2', 'resident_tax3', 'resident_tax4', 'resident_tax5', 'resident_tax6', 'resident_tax7', 'resident_tax8', 'resident_tax9', 'resident_tax10', 'resident_tax11', 'resident_tax12', 'tax_amount'];
    }

    /** @return list<string> */
    private function kihonColumns(): array
    {
        return ['staff_id', 'decision_date', 'monthly_salary', 'hourly_pay', 'hourly_salary', 'executive_remu', 'position_allow', 'duties_allow', 'qualification_allow', 'claim_allow', 'traffic_pay', 'adjustment_add', 'rent_subsidies', 'rent_pay', 'adjustment_pay', 'fixed_overtime'];
    }

    /** @return list<string> */
    private function shahoColumns(): array
    {
        return ['staff_id', 'raise_year', 'kenpo_monthly_amo', 'kounen_monthly_amo', 'kenpo_toukyu', 'kounen_toukyu'];
    }

    /** @return list<string> */
    private function residentColumns(): array
    {
        return ['staff_id', 'target_month', 'resident_tax1', 'resident_tax2', 'resident_tax3', 'resident_tax4', 'resident_tax5', 'resident_tax6', 'resident_tax7', 'resident_tax8', 'resident_tax9', 'resident_tax10', 'resident_tax11', 'resident_tax12', 'memo'];
    }

    /** @return list<string> */
    private function fuyoColumns(): array
    {
        return ['staff_id', 'deduction_target', 'fuyo_name_furi', 'fuyo_name', 'fuyo_relationship', 'kyojyu', 'fuyo_address', 'failure_notebook', 'failure_judgment', 'fuyo_birthday', 'fuyo_sex', 'fuyo_my_number', 'fuyo_shunyu', 'registration_date', 'widow'];
    }

    /** @return list<string> */
    private function basicShiftColumns(): array
    {
        return ['staff_name', 'week', 'shift_in', 'shift_exit', 'shift_entry', 'shift_out', 'section'];
    }

    private function tableHasColumn(string $connection, string $table, string $column): bool
    {
        $key = $connection . '.' . $table . '.' . $column;
        if (!array_key_exists($key, $this->tableColumnExistsCache)) {
            $this->tableColumnExistsCache[$key] = Schema::connection($connection)->hasColumn($table, $column);
        }

        return $this->tableColumnExistsCache[$key];
    }

    private function normalizeMonthStart(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return $value . '-01';
        }

        return $value;
    }

    private function hasColumn(string $column): bool
    {
        if (!array_key_exists($column, $this->columnExistsCache)) {
            $this->columnExistsCache[$column] = Schema::connection('sqlsrv')->hasColumn('mx_staffs', $column);
        }

        return $this->columnExistsCache[$column];
    }

    private function nullable(mixed $value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $this->formatJapaneseDate($value);
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2}(?:\.\d+)?)?$/', $normalized) === 1) {
            try {
                return $this->formatJapaneseDate(new \DateTimeImmutable(substr($normalized, 0, 10)));
            } catch (\Throwable) {
                return substr($normalized, 0, 10);
            }
        }

        return $normalized;
    }

    private function rawValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $normalized) === 1) {
            return substr($normalized, 0, 10);
        }

        return $normalized;
    }

    private function resolveSubmissionLabel(string $value): string
    {
        $normalized = $this->normalizeMayorKey($value);
        if ($normalized === '') {
            return $value;
        }

        $map = $this->submissionMayorMap();
        return $map[$normalized] ?? $value;
    }

    /** @return array<string, string> */
    private function submissionMayorMap(): array
    {
        if ($this->submissionMayorMap !== null) {
            return $this->submissionMayorMap;
        }

        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_mayor')
            ->select(['mayor_no', 'mayor'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $this->normalizeMayorKey((string) ($row->mayor_no ?? ''));
            $label = trim((string) ($row->mayor ?? ''));
            if ($key === '' || $label === '' || isset($map[$key])) {
                continue;
            }

            $map[$key] = $label;
        }

        $this->submissionMayorMap = $map;

        return $this->submissionMayorMap;
    }

    private function normalizeMayorKey(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (is_numeric($trimmed)) {
            return (string) ((int) $trimmed);
        }

        return $trimmed;
    }

    private function formatJapaneseDate(\DateTimeInterface $date): string
    {
        $ymd = sprintf(
            '%d/%d/%d',
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j')
        );

        $reiwaStart = new \DateTimeImmutable('2019-05-01');
        $heiseiStart = new \DateTimeImmutable('1989-01-08');
        $showaStart = new \DateTimeImmutable('1926-12-25');

        $current = new \DateTimeImmutable($date->format('Y-m-d'));

        if ($current >= $reiwaStart) {
            $year = (int) $current->format('Y') - 2018;
            return "（R{$year}）{$ymd}";
        }

        if ($current >= $heiseiStart) {
            $year = (int) $current->format('Y') - 1988;
            return "（H{$year}）{$ymd}";
        }

        if ($current >= $showaStart) {
            $year = (int) $current->format('Y') - 1925;
            return "（S{$year}）{$ymd}";
        }

        return $ymd;
    }

    private function calculateAgeLabel(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        try {
            $birthday = $value instanceof \DateTimeInterface
                ? new \DateTimeImmutable($value->format('Y-m-d'))
                : new \DateTimeImmutable(substr(trim((string) $value), 0, 10));
        } catch (\Throwable) {
            return '';
        }

        $today = new \DateTimeImmutable('today');
        return (string) $birthday->diff($today)->y . '歳';
    }
}
