<?php

namespace App\Services\StaffPortal\Attendance;

use App\Services\Admin\V2\Attendance\AttendanceV2MonthlySummaryService;
use App\Support\AttendanceTime;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceV2DailyTableItemBuilder
{
    public function __construct(
        private readonly AttendanceV2MonthlySummaryService $monthlySummaryService,
    ) {}

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
            'dailySummary' => $dailyRows === [] ? null : $this->monthlySummaryService->summaryForTimeCardKeys($timeCardKeys, $year, $month),
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
            ->where(function ($query) {
                $query->where('is_closed', false)
                    ->orWhere('is_closed', 0)
                    ->orWhereNull('is_closed');
            })
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
            ->filter(static fn(array $row): bool => $row['value'] !== '')
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
            static fn($staffKey): string => trim((string) $staffKey),
            $staffKeys
        ), static fn(string $staffKey): bool => $staffKey !== '')));

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

            $hasChangeRecord = $this->hasAnyTimeValue([
                $card->change_start ?? null,
                $card->change_leave ?? null,
                $card->change_break_out ?? null,
                $card->change_end ?? null,
            ]);

            $rawChangeScheduled = trim((string) ($card->change_scheduled ?? ''));

            $displayChangeScheduled = $rawChangeScheduled !== ''
                ? $this->formatNumber($rawChangeScheduled)
                : ($hasChangeRecord ? $changeScheduled : $shiftScheduled);

            $rows[] = [
                'time_no' => (string) ($card->time_no ?? ''),
                'time_card_key' => trim((string) ($card->staff_name ?? ($staffKeys[0] ?? ''))),
                'work_date' => $key,
                'date_label' => $date->format('n/j') . '(' . $this->jpWeekday($date) . ')',
                'has_change_record' => $hasChangeRecord ? '1' : '0',
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
                'shift_scheduled_hours' => $shiftScheduled,
                'actual_start' => $this->formatTime($card->actual_start ?? null),
                'actual_leave' => $this->formatTime($card->actual_leave ?? null),
                'actual_break_out' => $this->formatTime($card->actual_break_out ?? null),
                'actual_end' => $this->formatTime($card->actual_end ?? null),
                'actual_scheduled_hours' => $this->calculateScheduled(
                    $card->actual_start ?? null,
                    $card->actual_leave ?? null,
                    $card->actual_break_out ?? null,
                    $card->actual_end ?? null,
                ),
                'change_start' => $this->formatTime($card->change_start ?? null),
                'change_leave' => $this->formatTime($card->change_leave ?? null),
                'change_break_out' => $this->formatTime($card->change_break_out ?? null),
                'change_end' => $this->formatTime($card->change_end ?? null),
                'change_scheduled' => $displayChangeScheduled,
                'overtime' => $this->formatNumber($card->overtime ?? null),
                'night_overtime' => $this->formatNumber($card->night_overtime ?? null),
                'timecard_note' => trim((string) ($card->timecard_note ?? '')),
                'return_note' => trim((string) ($card->return_note ?? '')),
                'is_returned' => ((int) ($card->is_returned ?? 0)) === 1,
                'staff_request' => trim((string) ($card->staff_request ?? '')),
                'has_staff_approval' => $this->hasApprovalValue($card->staff_request ?? null) ? '1' : '0',
                'has_manager_approval' => $this->hasApprovalValue($card->manager_approval ?? null) ? '1' : '0',
            ];
        }

        return $rows;
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
        if (AttendanceTime::isZeroPlaceholder($text)) {
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
        $hours = AttendanceTime::scheduledHours($startValue, $leaveValue, $breakOutValue, $endValue);

        return $hours > 0 ? AttendanceTime::formatNumber($hours) : '';
    }

    private function jpWeekday(DateTimeImmutable $date): string
    {
        return ["\u{65E5}", "\u{6708}", "\u{706B}", "\u{6C34}", "\u{6728}", "\u{91D1}", "\u{571F}"][(int) $date->format('w')];
    }

    private function hasAnyTimeValue(array $values): bool
    {
        return AttendanceTime::hasAnyTimeValue($values);
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
        return AttendanceTime::formatNumber($value);
    }

}
