<?php

namespace App\Services\Admin\V2\Attendance;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceV2ShiftCreateService
{
    public function __construct(
        private readonly AttendanceV2HolidayCategoryService $holidayCategoryService,
    ) {
    }

    /**
     * @param list<string> $staffIds
     * @return array{created:int,skipped:int,created_ids:list<string>,skipped_ids:list<string>}
     */
    public function create(int $year, int $month, array $staffIds): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'created_ids' => [],
            'skipped_ids' => [],
        ];

        $staffIds = $this->normalizeIds($staffIds);
        if ($staffIds === [] || $year < 2000 || $month < 1 || $month > 12) {
            return $result;
        }

        $calendarHolidayMap = $this->holidayCategoryService->calendarHolidayMap($year, $month);

        foreach ($staffIds as $staffId) {
            if ($this->existsMonthRows($staffId, $year, $month)) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            $shiftMap = $this->basicShiftMap($staffId);
            if ($shiftMap === []) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            $inserted = 0;
            foreach ($this->monthDates($year, $month) as $date) {
                $week = $this->jpWeek($date);
                $shift = $shiftMap[$week] ?? null;
                $holidayCategory = $this->holidayCategoryService->resolve($date, $shift, $calendarHolidayMap);

                $payload = [
                    'staff_name' => $staffId,
                    'work_date' => $date->format('Y-m-d 00:00:00'),
                    'holiday_category' => $holidayCategory,
                    'staff_request_ch' => 0,
                    'is_returned' => 0,
                ];

                if (!$this->isDayOffCategory($holidayCategory) && $shift !== null) {
                    $payload['work_store'] = (string) ($shift['section'] ?? '');
                    $payload['shift_start'] = $shift['shift_in'] ?? null;
                    $payload['shift_leave'] = $shift['shift_exit'] ?? null;
                    $payload['shift_break_out'] = $shift['shift_entry'] ?? null;
                    $payload['shift_end'] = $shift['shift_out'] ?? null;
                    $payload['shift_scheduled'] = $shift['rou_time'] ?? null;
                    $payload['timecard_note'] = trim((string) ($shift['shift_memo'] ?? ''));
                }

                DB::connection('sqlsrv')->table('dbo.mx_time_cards')->insert($payload);
                $inserted++;
            }

            if ($inserted > 0) {
                $result['created']++;
                $result['created_ids'][] = $staffId;
            } else {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
            }
        }

        return $result;
    }

    /** @return list<string> */
    private function normalizeIds(array $staffIds): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $staffIds
        ), static fn (string $id): bool => $id !== '')));
    }

    private function existsMonthRows(string $staffId, int $year, int $month): bool
    {
        $fromDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $toDate = date('Y-m-t 23:59:59', strtotime(substr($fromDate, 0, 10)));

        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->whereBetween('work_date', [$fromDate, $toDate])
            ->exists();
    }

    /** @return array<string,array{shift_in:mixed,shift_exit:mixed,shift_entry:mixed,shift_out:mixed,rou_time:mixed,section:mixed,shift_memo:mixed,source:object}> */
    private function basicShiftMap(string $staffId): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_kihon_shifts')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->orderBy('shift_no')
            ->get()
            ->reduce(function (array $carry, object $row): array {
                $week = $this->normalizeWeekday((string) ($row->week ?? ''));
                if ($week === '' || isset($carry[$week])) {
                    return $carry;
                }

                $carry[$week] = [
                    'shift_in' => $row->shift_in ?? null,
                    'shift_exit' => $row->shift_exit ?? null,
                    'shift_entry' => $row->shift_entry ?? null,
                    'shift_out' => $row->shift_out ?? null,
                    'rou_time' => $row->rou_time ?? null,
                    'section' => $row->section ?? null,
                    'shift_memo' => $row->shift_memo ?? null,
                    'source' => $row,
                ];

                return $carry;
            }, []);
    }

    /** @return list<DateTimeImmutable> */
    private function monthDates(int $year, int $month): array
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $end = $start->modify('first day of next month');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);

        return iterator_to_array($period, false);
    }

    private function jpWeek(DateTimeImmutable $date): string
    {
        return ["\u{65E5}", "\u{6708}", "\u{706B}", "\u{6C34}", "\u{6728}", "\u{91D1}", "\u{571F}"][(int) $date->format('w')];
    }

    private function normalizeWeekday(string $value): string
    {
        $trimmed = trim($value);

        return match ($trimmed) {
            '日', '日曜', '日曜日' => "\u{65E5}",
            '月', '月曜', '月曜日' => "\u{6708}",
            '火', '火曜', '火曜日' => "\u{706B}",
            '水', '水曜', '水曜日' => "\u{6C34}",
            '木', '木曜', '木曜日' => "\u{6728}",
            '金', '金曜', '金曜日' => "\u{91D1}",
            '土', '土曜', '土曜日' => "\u{571F}",
            default => $trimmed,
        };
    }

    private function isDayOffCategory(string $holidayCategory): bool
    {
        return in_array($holidayCategory, [
            AttendanceV2HolidayCategoryService::CATEGORY_HOLIDAY,
            AttendanceV2HolidayCategoryService::CATEGORY_PUBLIC_HOLIDAY,
        ], true);
    }
}
