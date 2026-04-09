<?php

namespace App\Services\Admin\V2\Attendance;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceV2DailyTableItemBuilder
{
    /**
     * @param list<string> $timeCardKeys
     * @return array{
     *   dailyRows:list<array<string,string>>,
     *   dailySummary:array<string,mixed>|null,
     *   attendanceCategories:list<string>,
     *   storeOptions:list<array{value:string,label:string}>,
     *   isEditable:bool
     * }
     */
    public function build(array $timeCardKeys, int $year, int $month, bool $isEditable = true): array
    {
        $dailyRows = $this->rows($timeCardKeys, $year, $month);

        return [
            'dailyRows' => $dailyRows,
            'dailySummary' => $dailyRows === [] ? null : $this->summary($dailyRows),
            'attendanceCategories' => $this->attendanceCategories(),
            'storeOptions' => $this->storeOptions(),
            'isEditable' => $isEditable,
        ];
    }

    /**
     * @return list<string>
     */
    public function attendanceCategories(): array
    {
        return ["\u{632F}\u{51FA}", "\u{4EE3}\u{51FA}", "\u{632F}\u{4F11}", "\u{4EE3}\u{4F11}", "\u{6B20}\u{52E4}", "\u{6709}\u{4F11}", "\u{6709}\u{534A}", "\u{4F11}\u{51FA}", "\u{6CD5}\u{51FA}", "\u{51FA}\u{5F35}", "\u{9045}\u{65E9}"];
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function storeOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->select(['store_code', 'store_name', 'store_short_name'])
            ->orderBy('store_code')
            ->get()
            ->map(static function ($row): array {
                $value = trim((string) ($row->store_short_name ?? ''));
                if ($value === '') {
                    $value = trim((string) ($row->store_name ?? ''));
                }

                return [
                    'value' => $value,
                    'label' => $value,
                ];
            })
            ->filter(static fn (array $row): bool => $row['value'] !== '')
            ->unique('value')
            ->values()
            ->all();
    }

    /**
     * @param list<string> $staffKeys
     * @return list<array<string,string>>
     */
    public function rows(array $staffKeys, int $year, int $month): array
    {
        $staffKeys = array_values(array_unique(array_filter(array_map(
            static fn ($staffKey): string => trim((string) $staffKey),
            $staffKeys
        ), static fn (string $staffKey): bool => $staffKey !== '')));

        if ($staffKeys === [] || $year < 2000 || $month < 1 || $month > 12) {
            return [];
        }

        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('first day of next month');
        $lastDay = $start->modify('last day of this month');

        $storeRows = DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->select(['store_code', 'store_name', 'store_short_name'])
            ->get();

        $storeShortNameByCode = [];
        $storeShortNameByName = [];
        foreach ($storeRows as $storeRow) {
            $storeCode = trim((string) ($storeRow->store_code ?? ''));
            $storeName = trim((string) ($storeRow->store_name ?? ''));
            $storeShortName = trim((string) ($storeRow->store_short_name ?? ''));
            $displayName = $storeShortName !== '' ? $storeShortName : $storeName;

            if ($displayName === '') {
                continue;
            }

            if ($storeCode !== '' && !isset($storeShortNameByCode[$storeCode])) {
                $storeShortNameByCode[$storeCode] = $displayName;
            }

            if ($storeName !== '' && !isset($storeShortNameByName[$storeName])) {
                $storeShortNameByName[$storeName] = $displayName;
            }
        }

        $timeCardMap = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->where(function ($query) use ($staffKeys) {
                foreach ($staffKeys as $index => $staffKey) {
                    if ($index === 0) {
                        $query->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffKey]);
                        continue;
                    }

                    $query->orWhereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffKey]);
                }
            })
            ->where('work_date', '>=', $start->format('Y-m-d 00:00:00'))
            ->where('work_date', '<', $end->format('Y-m-d 00:00:00'))
            ->orderBy('work_date')
            ->get()
            ->mapWithKeys(function ($row) {
                $key = date('Y-m-d', strtotime((string) $row->work_date));
                return [$key => $row];
            })
            ->all();

        $rows = [];
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $card = $timeCardMap[$key] ?? null;
            $shiftScheduled = $this->calculateScheduled(
                $card->shift_start ?? null,
                $card->shift_leave ?? null,
                $card->shift_break_out ?? null,
                $card->shift_end ?? null,
            );
            $changeScheduled = $this->calculateScheduled(
                $card->change_start ?? null,
                $card->change_leave ?? null,
                $card->change_break_out ?? null,
                $card->change_end ?? null,
            );

            $rows[] = [
                'time_card_key' => trim((string) ($card->staff_name ?? ($staffKeys[0] ?? ''))),
                'work_date' => $key,
                'date_label' => $date->format('n/j') . '(' . $this->jpWeekday($date) . ')',
                'has_change_record' => $this->hasAnyTimeValue([
                    $card->change_start ?? null,
                    $card->change_leave ?? null,
                    $card->change_break_out ?? null,
                    $card->change_end ?? null,
                ]) ? '1' : '0',
                'holiday_category' => trim((string) ($card->holiday_category ?? '')),
                'attendance_category' => trim((string) ($card->work_type ?? '')),
                'category_time' => $this->formatNumber($card->work_type_time ?? null),
                'paid_leave_used' => $this->formatPaidLeave($card->paid_leave_used ?? null),
                'work_store' => $this->resolveStoreName(
                    $card->work_store ?? null,
                    $storeShortNameByCode,
                    $storeShortNameByName,
                ),
                'shift_start' => $this->formatTime($card->shift_start ?? null),
                'shift_leave' => $this->formatTime($card->shift_leave ?? null),
                'shift_break_out' => $this->formatTime($card->shift_break_out ?? null),
                'shift_end' => $this->formatTime($card->shift_end ?? null),
                'shift_scheduled' => $shiftScheduled,
                'actual_start' => $this->formatTime($card->actual_start ?? null),
                'actual_leave' => $this->formatTime($card->actual_leave ?? null),
                'actual_break_out' => $this->formatTime($card->actual_break_out ?? null),
                'actual_end' => $this->formatTime($card->actual_end ?? null),
                'actual_scheduled' => $this->calculateScheduled(
                    $card->actual_start ?? null,
                    $card->actual_leave ?? null,
                    $card->actual_break_out ?? null,
                    $card->actual_end ?? null,
                ),
                'change_start' => $this->formatTime($card->change_start ?? null),
                'change_leave' => $this->formatTime($card->change_leave ?? null),
                'change_break_out' => $this->formatTime($card->change_break_out ?? null),
                'change_end' => $this->formatTime($card->change_end ?? null),
                'change_scheduled' => $this->hasAnyTimeValue([
                    $card->change_start ?? null,
                    $card->change_leave ?? null,
                    $card->change_break_out ?? null,
                    $card->change_end ?? null,
                ]) ? $changeScheduled : $shiftScheduled,
                'overtime' => $this->formatNumber($card->overtime ?? null),
                'night_overtime' => $this->formatNumber($card->night_overtime ?? null),
                'timecard_note' => trim((string) ($card->timecard_note ?? '')),
                'return_note' => trim((string) ($card->return_note ?? '')),
                'has_staff_approval' => $this->hasApprovalValue($card->staff_request ?? null) ? '1' : '0',
                'has_manager_approval' => $this->hasApprovalValue($card->manager_approval ?? null) ? '1' : '0',
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string,string>> $rows
     * @return array<string,mixed>
     */
    public function summary(array $rows): array
    {
        $summary = [
            'work_days' => 0,
            'absence_days' => 0,
            'holiday_work_days' => 0,
            'late_early_days' => 0,
            'paid_leave_days' => 0.0,
            'shift_scheduled_total' => 0.0,
            'actual_scheduled_total' => 0.0,
            'change_scheduled_total' => 0.0,
            'overtime_total' => 0.0,
            'night_overtime_total' => 0.0,
            'category_totals' => [],
        ];

        foreach ($rows as $row) {
            $shiftScheduled = $this->toFloat($row['shift_scheduled'] ?? '');
            $actualScheduled = $this->toFloat($row['actual_scheduled'] ?? '');
            $changeScheduled = $this->toFloat($row['change_scheduled'] ?? '');
            $overtime = $this->toFloat($row['overtime'] ?? '');
            $nightOvertime = $this->toFloat($row['night_overtime'] ?? '');
            $paidLeave = $this->toFloat($row['paid_leave_used'] ?? '');
            $categoryTime = $this->toFloat($row['category_time'] ?? '');
            $attendanceCategory = trim((string) ($row['attendance_category'] ?? ''));

            if (($row['has_change_record'] ?? '0') === '1' || $shiftScheduled > 0) {
                $summary['work_days']++;
            }
            if ($attendanceCategory === "\u{6B20}\u{52E4}") {
                $summary['absence_days']++;
            }
            if ($attendanceCategory === "\u{4F11}\u{51FA}") {
                $summary['holiday_work_days']++;
            }
            if (in_array($attendanceCategory, ["\u{9045}\u{523B}", "\u{65E9}\u{9000}", "\u{9045}\u{65E9}"], true)) {
                $summary['late_early_days']++;
            }

            $summary['paid_leave_days'] += $paidLeave;
            $summary['shift_scheduled_total'] += $shiftScheduled;
            $summary['actual_scheduled_total'] += $actualScheduled;
            $summary['change_scheduled_total'] += $changeScheduled;
            $summary['overtime_total'] += $overtime;
            $summary['night_overtime_total'] += $nightOvertime;

            if ($attendanceCategory !== '' && $categoryTime > 0) {
                $summary['category_totals'][$attendanceCategory] = ($summary['category_totals'][$attendanceCategory] ?? 0.0) + $categoryTime;
            }
        }

        ksort($summary['category_totals']);

        return $summary;
    }

    private function formatTime(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (preg_match('/^\d+(\.\d+)?$/', $text) === 1) {
            return $this->formatNumber($value);
        }

        $normalized = str_ireplace(['AM', 'PM'], [' AM ', ' PM '], $text);
        $ts = strtotime($normalized);
        if ($ts === false) {
            return $text;
        }

        return date('H:i', $ts);
    }

    private function calculateScheduled(
        mixed $startValue,
        mixed $leaveValue,
        mixed $breakOutValue,
        mixed $endValue,
    ): string {
        $startMinutes = $this->parseTimeMinutes($startValue);
        $endMinutes = $this->parseTimeMinutes($endValue);
        if ($startMinutes === null || $endMinutes === null) {
            return '';
        }

        $leaveMinutes = $this->parseTimeMinutes($leaveValue);
        $breakOutMinutes = $this->parseTimeMinutes($breakOutValue);

        if ($leaveMinutes !== null && $breakOutMinutes !== null) {
            $totalMinutes = $this->minutesBetween($startMinutes, $leaveMinutes)
                + $this->minutesBetween($breakOutMinutes, $endMinutes);

            return $this->formatNumber($totalMinutes / 60);
        }

        return $this->formatNumber($this->minutesBetween($startMinutes, $endMinutes) / 60);
    }

    private function parseTimeMinutes(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $text) === 1) {
            $numeric = (float) $text;
            if ($numeric <= 24) {
                return (int) round($numeric * 60);
            }

            $digitsOnly = preg_replace('/\D+/', '', $text);
            if ($digitsOnly !== null && preg_match('/^\d{3,4}$/', $digitsOnly) === 1) {
                $hours = (int) substr($digitsOnly, 0, -2);
                $minutes = (int) substr($digitsOnly, -2);
                if ($hours < 24 && $minutes < 60) {
                    return ($hours * 60) + $minutes;
                }
            }

            return null;
        }

        $normalized = str_ireplace(['AM', 'PM'], [' AM ', ' PM '], $text);
        $ts = strtotime('2000-01-01 ' . $normalized);
        if ($ts === false) {
            $ts = strtotime($normalized);
        }
        if ($ts === false) {
            return null;
        }

        return ((int) date('G', $ts) * 60) + (int) date('i', $ts);
    }

    private function minutesBetween(int $startMinutes, int $endMinutes): int
    {
        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return max(0, $endMinutes - $startMinutes);
    }

    private function jpWeekday(DateTimeImmutable $date): string
    {
        return ["\u{65E5}", "\u{6708}", "\u{706B}", "\u{6C34}", "\u{6728}", "\u{91D1}", "\u{571F}"][(int) $date->format('w')];
    }

    private function hasAnyTimeValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->parseTimeMinutes($value) !== null) {
                return true;
            }
        }

        return false;
    }

    private function resolveStoreName(mixed $value, array $storeShortNameByCode, array $storeShortNameByName): string
    {
        $storeValue = trim((string) $value);
        if ($storeValue === '') {
            return '';
        }

        return $storeShortNameByCode[$storeValue]
            ?? $storeShortNameByName[$storeValue]
            ?? $storeValue;
    }

    private function hasApprovalValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    private function formatPaidLeave(mixed $value): string
    {
        $formatted = $this->formatNumber($value);

        return $formatted === '0' ? '' : $formatted;
    }

    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $num = (float) $value;
        if (abs($num - round($num)) < 0.00001) {
            return (string) (int) round($num);
        }

        return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    }

    private function toFloat(mixed $value): float
    {
        $text = trim((string) $value);

        return $text === '' || !is_numeric($text) ? 0.0 : (float) $text;
    }
}
