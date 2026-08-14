<?php

namespace App\Services\Admin\V2\Attendance;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceV2ShiftCreateService
{
    // スタッフの基本シフトから mx_time_cards の月次行を作成するサービス。
    // 実打刻や月次計算は扱わず、未作成月の予定行作成だけを担当する。
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
        $staffIdsWithExistingRows = $this->staffIdsWithMonthRows($staffIds, $year, $month);
        $shiftMapByStaff = $this->basicShiftMapByStaff($staffIds);
        $monthDates = $this->monthDates($year, $month);

        $allPayloads = [];
        foreach ($staffIds as $staffId) {
            if (isset($staffIdsWithExistingRows[$staffId])) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            $shiftMap = $shiftMapByStaff[$staffId] ?? [];
            if ($shiftMap === []) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            $inserted = 0;
            foreach ($monthDates as $date) {
                $week = $this->jpWeek($date);
                $shift = $shiftMap[$week] ?? null;
                $holidayCategory = $this->holidayCategoryService->resolve($date, $shift, $calendarHolidayMap);

                $payload = [
                    'staff_name' => $staffId,
                    'work_date' => $date->format('Y-m-d 00:00:00'),
                    'holiday_category' => $holidayCategory,
                    'staff_request_ch' => 0,
                    'is_returned' => 0,
                    'work_store' => null,
                    'shift_start' => null,
                    'shift_leave' => null,
                    'shift_break_out' => null,
                    'shift_end' => null,
                    'timecard_note' => null,
                ];

                if (!$this->isDayOffCategory($holidayCategory) && $shift !== null) {
                    $payload['work_store'] = (string) ($shift['section'] ?? '');
                    $payload['shift_start'] = $shift['shift_in'] ?? null;
                    $payload['shift_leave'] = $shift['shift_exit'] ?? null;
                    $payload['shift_break_out'] = $shift['shift_entry'] ?? null;
                    $payload['shift_end'] = $shift['shift_out'] ?? null;
                    $payload['timecard_note'] = trim((string) ($shift['shift_memo'] ?? ''));
                }

                $allPayloads[] = $payload;
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

        // SQL Serverの1クエリあたりパラメータ上限(2100個)に収まるよう、11列×150行=1650個で分割する。
        foreach (array_chunk($allPayloads, 150) as $chunk) {
            DB::connection('sqlsrv')->table('dbo.mx_time_cards')->insert($chunk);
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

    /**
     * @param list<string> $staffIds
     * @return array<string,true> 既にその月の行があるstaff_idをキーに持つ集合
     */
    private function staffIdsWithMonthRows(array $staffIds, int $year, int $month): array
    {
        $fromDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $toDate = date('Y-m-01 00:00:00', strtotime(substr($fromDate, 0, 10) . ' +1 month'));

        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->selectRaw('DISTINCT LTRIM(RTRIM(staff_name)) as staff_name')
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), $staffIds)
            ->where('work_date', '>=', $fromDate)
            ->where('work_date', '<', $toDate)
            ->pluck('staff_name')
            ->flip()
            ->map(static fn (): bool => true)
            ->all();
    }

    /**
     * @param list<string> $staffIds
     * @return array<string,array<string,array{shift_in:mixed,shift_exit:mixed,shift_entry:mixed,shift_out:mixed,rou_time:mixed,section:mixed,shift_memo:mixed,source:object}>>
     */
    private function basicShiftMapByStaff(array $staffIds): array
    {
        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_kihon_shifts')
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), $staffIds)
            ->orderBy('staff_name')
            ->orderBy('shift_no')
            ->get();

        $byStaff = [];
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->staff_name ?? ''));
            if ($staffId === '') {
                continue;
            }

            $week = $this->normalizeWeekday((string) ($row->week ?? ''));
            if ($week === '' || isset($byStaff[$staffId][$week])) {
                continue;
            }

            $byStaff[$staffId][$week] = [
                'shift_in' => $row->shift_in ?? null,
                'shift_exit' => $row->shift_exit ?? null,
                'shift_entry' => $row->shift_entry ?? null,
                'shift_out' => $row->shift_out ?? null,
                'rou_time' => $row->rou_time ?? null,
                'section' => $row->section ?? null,
                'shift_memo' => $row->shift_memo ?? null,
                'source' => $row,
            ];
        }

        return $byStaff;
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
