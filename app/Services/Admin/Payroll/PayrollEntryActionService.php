<?php

namespace App\Services\Admin\Payroll;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollEntryActionService
{
    public function syncAttendance(Request $request, AttendanceAggregateService $attendance): array
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['required', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $staffId = trim((string) $validated['staff_id']);

        $entry = $this->findPayrollEntryByMonthStaff($year, $month, $staffId);
        if ($entry === null) {
            return ['error' => 'Payroll row not found.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');
        if ((int) ($entry->{$lockCol} ?? 0) === 1) {
            return ['error' => 'Locked payroll cannot be synced.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $payrollDate = sprintf('%04d-%02d-01', $year, $month);
        $preview = $attendance->buildForPayrollMonth($staffId, $payrollDate);
        if ($preview === []) {
            return ['error' => 'Attendance source data not found.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $payloadCol = $this->payrollColumn('raw_payload', null);
        if ($payloadCol === null) {
            return ['error' => 'raw_payload column not found.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $payload = $this->decodePayload($entry->{$payloadCol} ?? null);
        foreach ($preview as $key => $value) {
            $payload[$key] = $value;
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encoded)) {
            return ['error' => 'Payload encode failed.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $update = [$payloadCol => $encoded];
        if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'updated_at')) {
            $update['updated_at'] = now('Asia/Tokyo');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($entryIdCol, (int) ($entry->{$entryIdCol} ?? 0))
            ->update($update);

        return ['status' => 'Attendance synced. Updated fields: ' . count($preview), 'query' => $this->baseQuery($validated, $staffId)];
    }

    public function syncAttendanceBulk(Request $request, AttendanceAggregateService $attendance): array
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];

        $bonusCol = $this->payrollColumn('is_bonus', 'bonus');
        $staffCol = $this->payrollColumn('staff_code', 'kyuyo_staff_id');
        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');
        $payloadCol = $this->payrollColumn('raw_payload', null);
        if ($payloadCol === null) {
            return ['error' => 'raw_payload column not found.', 'query' => $this->baseQuery($validated, trim((string) ($validated['staff_id'] ?? '')))];
        }

        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month]);

        $staffId = trim((string) ($validated['staff_id'] ?? ''));
        if ($staffId !== '') {
            $query->whereRaw('LTRIM(RTRIM(' . $staffCol . ')) = ?', [$staffId]);
        }

        $entries = $query->orderBy($entryIdCol, 'asc')->get([$entryIdCol, $staffCol, $lockCol, $payloadCol]);
        if ($entries->isEmpty()) {
            return ['status' => 'No payroll rows to sync.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $updatedRows = 0;
        $updatedFields = 0;
        $skippedLocked = 0;
        $payrollDate = sprintf('%04d-%02d-01', $year, $month);

        foreach ($entries as $entry) {
            if ((int) ($entry->{$lockCol} ?? 0) === 1) {
                $skippedLocked++;
                continue;
            }

            $sid = trim((string) ($entry->{$staffCol} ?? ''));
            if ($sid === '') {
                continue;
            }

            $preview = $attendance->buildForPayrollMonth($sid, $payrollDate);
            if ($preview === []) {
                continue;
            }

            $payload = $this->decodePayload($entry->{$payloadCol} ?? null);
            foreach ($preview as $k => $v) {
                $payload[$k] = $v;
                $updatedFields++;
            }

            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if (!is_string($encoded)) {
                continue;
            }

            $update = [$payloadCol => $encoded];
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'updated_at')) {
                $update['updated_at'] = now('Asia/Tokyo');
            }

            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->where($entryIdCol, (int) ($entry->{$entryIdCol} ?? 0))
                ->update($update);

            $updatedRows++;
        }

        return [
            'status' => 'Bulk sync completed. Rows: ' . $updatedRows . ' / Fields: ' . $updatedFields . ($skippedLocked > 0 ? ' / Skipped locked: ' . $skippedLocked : ''),
            'query' => $this->baseQuery($validated, $staffId),
        ];
    }

    public function save(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_staff_id' => ['nullable', 'string', 'max:50'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'row' => ['nullable', 'string', 'max:100'],
            'fields' => ['nullable', 'array'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $staffId = trim((string) ($validated['target_staff_id'] ?? ''));
        if ($staffId === '') {
            $staffId = trim((string) ($validated['staff_id'] ?? ''));
        }
        if ($staffId === '') {
            return ['error' => 'Target staff required.', 'query' => $this->baseQuery($validated, '')];
        }

        $entry = $this->findPayrollEntryByMonthStaff($year, $month, $staffId);
        if ($entry === null) {
            return ['error' => 'Payroll row not found.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');
        if ((int) ($entry->{$lockCol} ?? 0) === 1) {
            return ['error' => 'Locked payroll cannot be edited.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $payloadCol = $this->payrollColumn('raw_payload', null);
        if ($payloadCol === null) {
            return ['error' => 'raw_payload column not found.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $payload = $this->decodePayload($entry->{$payloadCol} ?? null);
        if (((int) ($payload['attendance_checked'] ?? 0)) !== 1) {
            return ['error' => 'Attendance is not checked.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $fields = is_array($validated['fields'] ?? null) ? $validated['fields'] : [];
        if ($fields === []) {
            return ['status' => 'No changed fields.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        foreach ($fields as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if (is_string($v) && trim($v) === '' && array_key_exists($k, $payload)) {
                continue;
            }
            $payload[$k] = $this->normalizeNumericInput($v);
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encoded)) {
            return ['error' => 'Payload encode failed.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $update = [$payloadCol => $encoded];
        if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'updated_at')) {
            $update['updated_at'] = now('Asia/Tokyo');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($entryIdCol, (int) ($entry->{$entryIdCol} ?? 0))
            ->update($update);

        return ['status' => 'Saved.', 'query' => $this->baseQuery($validated, $staffId)];
    }

    public function toggleLock(Request $request, bool $lock): array
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'target_staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'row' => ['nullable', 'string', 'max:100'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $staffId = trim((string) ($validated['target_staff_id'] ?? ''));
        if ($staffId === '') {
            $staffId = trim((string) ($validated['staff_id'] ?? ''));
        }
        if ($staffId === '') {
            return ['error' => 'Target staff required.', 'query' => $this->baseQuery($validated, '')];
        }

        $entry = $this->findPayrollEntryByMonthStaff($year, $month, $staffId);
        if ($entry === null) {
            return ['error' => 'Payroll row not found.', 'query' => $this->baseQuery($validated, $staffId)];
        }

        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');

        $update = [$lockCol => $lock ? 1 : 0];
        if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'updated_at')) {
            $update['updated_at'] = now('Asia/Tokyo');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($entryIdCol, (int) ($entry->{$entryIdCol} ?? 0))
            ->update($update);

        return ['status' => $lock ? 'Locked.' : 'Unlocked.', 'query' => $this->baseQuery($validated, $staffId)];
    }

    private function findPayrollEntryByMonthStaff(int $year, int $month, string $staffId): ?object
    {
        $bonusCol = $this->payrollColumn('is_bonus', 'bonus');
        $staffCol = $this->payrollColumn('staff_code', 'kyuyo_staff_id');
        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM(' . $staffCol . ')) = ?', [$staffId])
            ->orderBy($entryIdCol, 'desc')
            ->first();
    }

    private function baseQuery(array $validated, string $staffId): array
    {
        return array_filter([
            'month' => (string) ($validated['month'] ?? ''),
            'company_id' => trim((string) ($validated['company_id'] ?? '')),
            'staff_id' => trim((string) ($staffId !== '' ? $staffId : ($validated['staff_id'] ?? ''))),
            'row' => (string) ($validated['row'] ?? ''),
        ], static fn ($v) => $v !== '');
    }

    private function decodePayload(mixed $rawPayload): array
    {
        if (!is_string($rawPayload) || trim($rawPayload) === '') {
            return [];
        }

        $decoded = json_decode($rawPayload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeNumericInput(mixed $value): mixed
    {
        if ($value === null) {
            return 0;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0;
        }

        $normalized = str_replace([',', ' ', '　'], '', $text);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return $text;
        }

        return str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
    }

    private function payrollColumn(string $preferred, ?string $fallback): string
    {
        if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', $preferred)) {
            return $preferred;
        }
        if ($fallback !== null && Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', $fallback)) {
            return $fallback;
        }

        return $preferred;
    }
}
