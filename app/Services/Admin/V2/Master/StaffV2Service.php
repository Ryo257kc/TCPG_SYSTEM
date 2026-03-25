<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffV2Service
{
    /** @var array<string, string>|null */
    private ?array $submissionMayorMap = null;

    /** @var array<string, bool> */
    private array $columnExistsCache = [];

    public function list(string $keyword, string $employmentFilter = 'active'): array
    {
        $query = DB::connection('sqlsrv')->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.section', '=', 'st.store_code')
            ->select([
                DB::raw('ms.staff_id as staff_id'),
                'ms.staff_name',
                DB::raw('ms.staff_name_furi as staff_name_kana'),
                DB::raw('ms.section as store_code'),
                DB::raw('st.store_name as store_name'),
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

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('ms.staff_code', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_id', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_name', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.section', 'like', '%' . $keyword . '%');
            });
        }

        return $query->get()->map(fn ($r) => [
            'staff_id' => (string) ($r->staff_id ?? ''),
            'staff_name' => (string) ($r->staff_name ?? ''),
            'staff_name_kana' => (string) ($r->staff_name_kana ?? ''),
            'store_code' => (string) ($r->store_code ?? ''),
            'store_name' => (string) ($r->store_name ?? ''),
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

    /** @return list<array{store_code:string,store_name:string}> */
    public function storeOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
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

            if (in_array($column, ['syaho', 'koyou'], true)) {
                $payload[$column] = (($values[$column] ?? null) === '1') ? 1 : 0;
                continue;
            }

            $payload[$column] = $this->nullable($values[$column] ?? null);
        }

        if ($payload === []) {
            return;
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->update($payload);
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

    public function fieldLabels(): array
    {
        return [
            'submission' => '住民税提出先',
            'is_accounting_user' => '経理',
            'is_payment_check_user' => '入金確認',
            'is_visit_management_user' => '往診管理',
            'is_view_only_user' => '閲覧権限',
            'staff_id' => 'スタッフID',
            'staff_code' => 'スタッフコード',
            'staff_name' => '氏名',
            'staff_name_furi' => '氏名カナ',
            'display_name' => '表示名',
            'display_name_ja' => '表示名',
            'section' => '店舗コード',
            '_store_name' => '店舗名',
            '_company_name' => '会社名',
            'employment' => '就業状況',
            'employment_status' => '就業状況',
            'staff_division' => '雇用区分',
            'tai_date' => '退社日',
            'joining_date' => '入社日',
            'nyu_date' => '入社日',
            'is_store_management_user' => '店舗管理',
            'is_daily_report_user' => '店舗施術者',
            'oushin_staff' => '往診スタッフ',
            'front_staff' => '店舗システム',
            'mail' => 'メール',
            'tel' => '電話番号',
            'home_tel' => '電話番号',
            'mobile' => '携帯番号',
            'mobile_tel' => '携帯番号',
            'birthday' => '生年月日',
            '_age' => '年齢',
            'sex' => '性別',
            'my_number' => 'マイナンバー',
            'head_house' => '世帯主',
            'relationship' => '続柄',
            'spouse' => '配偶者',
            'post_num' => '郵便番号',
            'address_furi' => '住所カナ',
            'address' => '住所',
            'address1' => '住所1',
            'address2' => '住所2',
            'address_kana' => '住所カナ',
            'prefecture' => '都道府県',
            'city' => '市区町村',
            'building' => '建物名',
            'bank_name_1' => '銀行名1',
            'bank_branch_1' => '支店名1',
            'account_type' => '口座種別1',
            'account_num' => '口座番号1',
            'bank_name_2' => '銀行名2',
            'bank_branch_2' => '支店名2',
            'account_type2' => '口座種別2',
            'account_num2' => '口座番号2',
            'transfer_purpose' => '振込先',
            'tax_amount' => '税額',
            'syaho_num' => '健康保険番号',
            'syaho_seiri_num' => '健保被保険者番号',
            'kiso_nenkin_num' => '基礎年金番号',
            'koyou_num' => '雇用保険番号',
            'syaho' => '社保加入',
            'koyou' => '雇保加入',
            'syaho_date' => '社保加入日',
            'koyou_date' => '雇保加入日',
            'yukyu_month' => '有休加算月',
            'yukyu' => '有休あり',
            'trial' => '試用期間',
            'has_fixed_term' => '期間の定めあり',
            'fixed_term_detail' => '期間の定め',
            'work_schedule_1' => '勤務時間1',
            'work_schedule_2' => '勤務時間2',
            'car_km' => '車通勤片道',
            'traffic_day' => '日額交通費',
            'traffic_day_tuika' => 'ひなた交通費日額',
            'working_time' => '所定労働時間（日）',
            'weekly_working_time' => '所定労働時間（週）',
            'year_working_time' => '所定労働時間（月）',
            'business_content' => '業務内容',
            'percentage_1' => '業務委託割合1',
            'percentage_2' => '業務委託割合2',
            'password' => 'パスワード',
            'memo' => 'メモ',
            'decision_date' => '決定日',
            'raise_year' => '改定年',
            'registration_date' => '登録日',
            'change_history' => '変更履歴',
            'target_month' => '対象月',
            'remaining_day' => '残日数',
            'date_use' => '使用日',
            'days_used' => '使用日数',
        ];
    }

    /** @return list<string> */
    private function editableColumns(): array
    {
        return [
            'staff_name_furi',
            'staff_name',
            'display_name_ja',
            'section',
            'staff_division',
            'employment',
            'nyu_date',
            'tai_date',
            'my_number',
            'post_num',
            'address_furi',
            'address',
            'address1',
            'address2',
            'building',
            'home_tel',
            'mobile_tel',
            'syaho_num',
            'koyou_num',
            'syaho_seiri_num',
            'kiso_nenkin_num',
            'syaho',
            'koyou',
            'syaho_date',
            'koyou_date',
            'memo',
        ];
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
