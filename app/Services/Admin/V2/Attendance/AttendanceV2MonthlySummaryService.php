<?php

namespace App\Services\Admin\V2\Attendance;

use App\Support\AttendanceTime;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 勤怠の月次集計を一か所に集約するサービス。
 *
 * 勤怠一覧、日別サマリー、給与反映で同じ計算結果を使うため、
 * 勤務日数・有休・残業・給与反映項目の計算式はこのクラスを正とする。
 */
class AttendanceV2MonthlySummaryService
{
    // 勤怠計算の現在の基準サービス。
    // 勤怠一覧、日別サマリー、給与への勤怠反映はここで作った月次集計を使う。
    // 勤務日数・有休・欠勤・残業などの式を変える時は、まずこのクラスを見る。
    private const ABSENCE = "\u{6B20}\u{52E4}";
    private const HOLIDAY_WORK = "\u{4F11}\u{51FA}";
    private const LEGAL_HOLIDAY_WORK = "\u{6CD5}\u{51FA}";
    private const LATE = "\u{9045}\u{523B}";
    private const EARLY_LEAVE = "\u{65E9}\u{9000}";
    private const LATE_EARLY = "\u{9045}\u{65E9}";
    private const CLOSED = "\u{4F11}\u{696D}";

    /**
     * タイムカードキーごとの月次集計。
     *
     * 画面一覧はスタッフに複数タイムカードキーが紐づくため、
     * まずキー単位で同じ集計結果を作ってからスタッフ単位に合算する。
     *
     * @param list<string> $timeCardKeys
     * @return array<string,array<string,mixed>>
     */
    public function summaryMapByTimeCardKey(int $year, int $month, array $timeCardKeys = []): array
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return [];
        }

        $rows = $this->timeCards($year, $month, $timeCardKeys);
        $map = [];

        foreach ($rows as $row) {
            $timeCardKey = trim((string) ($row->time_card_key ?? ''));
            if ($timeCardKey === '') {
                continue;
            }

            $map[$timeCardKey] ??= $this->emptySummary();
            $map[$timeCardKey] = $this->addSummaries(
                $map[$timeCardKey],
                $this->summaryForRow($row),
            );
        }

        return $map;
    }

    /**
     * 指定したタイムカードキーをまとめた月次集計。
     *
     * 日別明細の下に出すサマリーや、給与反映前のスタッフ単位集計で使う。
     *
     * @param list<string> $timeCardKeys
     * @return array<string,mixed>
     */
    public function summaryForTimeCardKeys(array $timeCardKeys, int $year, int $month): array
    {
        $summary = $this->emptySummary();
        foreach ($this->summaryMapByTimeCardKey($year, $month, $timeCardKeys) as $timeCardSummary) {
            $summary = $this->addSummaries($summary, $timeCardSummary);
        }

        return $summary;
    }

    /**
     * 空の集計値。
     *
     * 表示側・給与反映側で同じキーを使えるよう、画面用と給与保存用の元値を同時に持つ。
     *
     * @return array<string,mixed>
     */
    public function emptySummary(): array
    {
        return [
            'attendance_checked' => false,
            'work_days' => 0.0,
            'absence_days' => 0.0,
            'holiday_work_days' => 0.0,
            'holiday_work_time' => 0.0,
            'late_early_days' => 0.0,
            'late_early_time' => 0.0,
            'paid_leave_days' => 0.0,
            'shift_scheduled_total' => 0.0,
            'actual_scheduled_total' => 0.0,
            'change_scheduled_total' => 0.0,
            'work_time_total' => 0.0,
            'overtime_total' => 0.0,
            'night_overtime_total' => 0.0,
            'days_closed' => 0.0,
            'time_closed' => 0.0,
            'category_totals' => [],
        ];
    }

    /**
     * 2つの月次集計を合算する。
     *
     * 複数タイムカードキーを1人のスタッフにまとめるときの共通処理。
     *
     * @param array<string,mixed> $base
     * @param array<string,mixed> $add
     * @return array<string,mixed>
     */
    public function addSummaries(array $base, array $add): array
    {
        $base['attendance_checked'] = ($base['attendance_checked'] ?? false) || ($add['attendance_checked'] ?? false);

        foreach (
            [
                'work_days',
                'absence_days',
                'holiday_work_days',
                'holiday_work_time',
                'late_early_days',
                'late_early_time',
                'paid_leave_days',
                'shift_scheduled_total',
                'actual_scheduled_total',
                'change_scheduled_total',
                'work_time_total',
                'overtime_total',
                'night_overtime_total',
                'days_closed',
                'time_closed',
            ] as $key
        ) {
            $base[$key] = (float) ($base[$key] ?? 0) + (float) ($add[$key] ?? 0);
        }

        foreach (($add['category_totals'] ?? []) as $category => $total) {
            $base['category_totals'][$category] = (float) ($base['category_totals'][$category] ?? 0) + (float) $total;
        }

        ksort($base['category_totals']);

        return $base;
    }

    /**
     * 給与明細へ保存する項目へ変換する。
     *
     * 給与テーブルの古い列名はここで吸収し、呼び出し元は計算式を持たない。
     *
     * @param array<string,mixed> $summary
     * @return array<string,float|int>
     */
    public function payrollPayload(array $summary): array
    {
        $workDays = (float) ($summary['work_days'] ?? 0);
        $holidayWorkDays = (float) ($summary['holiday_work_days'] ?? 0);
        $paidLeaveDays = (float) ($summary['paid_leave_days'] ?? 0);
        $closedDays = (float) ($summary['days_closed'] ?? 0);

        return [
            'work_in_num' => round($workDays, 2),
            'absence_num' => round((float) ($summary['absence_days'] ?? 0), 2),
            'work_horiday_num' => round($holidayWorkDays, 2),
            'work_time' => round((float) ($summary['work_time_total'] ?? 0), 2),
            'work_time_num' => round((float) ($summary['holiday_work_time'] ?? 0), 2),
            'overtime' => round((float) ($summary['overtime_total'] ?? 0), 2),
            'late_time' => round((float) ($summary['late_early_time'] ?? 0), 2),
            'night_over_time' => round((float) ($summary['night_overtime_total'] ?? 0), 2),
            'horiday_true' => round($paidLeaveDays, 2),
            'days_closed' => round($closedDays, 2),
            'time_closed' => round((float) ($summary['time_closed'] ?? 0), 2),
            'work_kiso_num' => round($workDays + $holidayWorkDays + $paidLeaveDays + $closedDays, 2),
        ];
    }

    /**
     * 1日分のタイムカードを月次集計形式へ変換する。
     *
     * 変更予定時間は、保存済みの change_scheduled があればそれを優先し、
     * なければ変更時刻から計算、変更時刻もなければシフト予定時間を使う。
     *
     * @return array<string,mixed>
     */
    private function summaryForRow(object $row): array
    {
        $summary = $this->emptySummary();
        $summary['attendance_checked'] = true;

        $attendanceCategory = trim((string) ($row->work_type ?? ''));
        $categoryTime = $this->toFloat($row->work_type_time ?? 0);
        $shiftScheduled = $this->scheduledHours(
            $row->shift_start ?? null,
            $row->shift_leave ?? null,
            $row->shift_break_out ?? null,
            $row->shift_end ?? null,
        );
        $actualScheduled = $this->scheduledHours(
            $row->actual_start ?? null,
            $row->actual_leave ?? null,
            $row->actual_break_out ?? null,
            $row->actual_end ?? null,
        );
        $changeScheduled = $this->resolvedChangeScheduled($row, $shiftScheduled);
        $isAbsent = $attendanceCategory === self::ABSENCE;
        $hasWork = !$isAbsent && $changeScheduled > 0;

        if ($hasWork) {
            $summary['work_days'] = 1.0;
            $summary['work_time_total'] = $changeScheduled;
        }

        if ($isAbsent) {
            $summary['absence_days'] = trim((string) ($row->holiday_category ?? '')) === AttendanceV2HolidayCategoryService::CATEGORY_HALF_DAY ? 0.5 : 1.0;
        }

        if (in_array($attendanceCategory, [self::HOLIDAY_WORK, self::LEGAL_HOLIDAY_WORK], true)) {
            $summary['holiday_work_days'] = 1.0;
            $summary['holiday_work_time'] = $categoryTime;
        }

        if (in_array($attendanceCategory, [self::LATE, self::EARLY_LEAVE, self::LATE_EARLY], true)) {
            $summary['late_early_days'] = 1.0;
            $summary['late_early_time'] = $categoryTime;
        }

        if ($attendanceCategory === self::CLOSED) {
            $summary['days_closed'] = 1.0;
            $summary['time_closed'] = $categoryTime;
        }

        $summary['paid_leave_days'] = $this->toFloat($row->paid_leave_used ?? 0);
        $summary['shift_scheduled_total'] = $shiftScheduled;
        $summary['actual_scheduled_total'] = $actualScheduled;
        $summary['change_scheduled_total'] = $changeScheduled;
        $summary['overtime_total'] = $this->toFloat($row->overtime ?? 0);
        $summary['night_overtime_total'] = $this->toFloat($row->night_overtime ?? 0);

        if ($attendanceCategory !== '' && $categoryTime > 0) {
            $summary['category_totals'][$attendanceCategory] = $categoryTime;
        }

        return $summary;
    }

    /**
     * 勤怠テーブルから集計対象の行を取得する。
     *
     * 夜間残業の列名が環境で揺れているため、存在する列を night_overtime に揃える。
     *
     * @param list<string> $timeCardKeys
     * @return \Illuminate\Support\Collection<int,object>
     */
    private function timeCards(int $year, int $month, array $timeCardKeys)
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->select([
                DB::raw('LTRIM(RTRIM(staff_name)) as time_card_key'),
                'work_date',
                'holiday_category',
                'work_type',
                'work_type_time',
                'paid_leave_used',
                'shift_start',
                'shift_leave',
                'shift_break_out',
                'shift_end',
                'actual_start',
                'actual_leave',
                'actual_break_out',
                'actual_end',
                'change_start',
                'change_leave',
                'change_break_out',
                'change_end',
                'change_scheduled',
                'overtime',
            ])
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereNotNull('staff_name')
            ->whereRaw('LTRIM(RTRIM(staff_name)) <> ?', ['']);

        $timeCardKeys = array_values(array_unique(array_filter(array_map(
            static fn($timeCardKey): string => trim((string) $timeCardKey),
            $timeCardKeys
        ), static fn(string $timeCardKey): bool => $timeCardKey !== '')));

        if ($timeCardKeys !== []) {
            $query->whereIn(DB::raw('LTRIM(RTRIM([staff_name]))'), $timeCardKeys);
        }

        if (Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'night_overtime')) {
            $query->addSelect('night_overtime');
        } elseif (Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'night_over_time')) {
            $query->addSelect(DB::raw('[night_over_time] as night_overtime'));
        }

        return $query->orderBy('work_date')->get();
    }

    private function resolvedChangeScheduled(object $row, float $shiftScheduled): float
    {
        $rawChangeScheduled = trim((string) ($row->change_scheduled ?? ''));
        if ($rawChangeScheduled !== '') {
            return $this->toFloat($rawChangeScheduled);
        }

        if ($this->hasAnyTimeValue([
            $row->change_start ?? null,
            $row->change_leave ?? null,
            $row->change_break_out ?? null,
            $row->change_end ?? null,
        ])) {
            return $this->scheduledHours(
                $row->change_start ?? null,
                $row->change_leave ?? null,
                $row->change_break_out ?? null,
                $row->change_end ?? null,
            );
        }

        return $shiftScheduled;
    }

    private function scheduledHours(
        mixed $startValue,
        mixed $leaveValue,
        mixed $breakOutValue,
        mixed $endValue,
    ): float {
        $startMinutes = $this->parseTimeMinutes($startValue);
        $endMinutes = $this->parseTimeMinutes($endValue);
        if ($startMinutes === null || $endMinutes === null) {
            return 0.0;
        }

        $leaveMinutes = $this->parseTimeMinutes($leaveValue);
        $breakOutMinutes = $this->parseTimeMinutes($breakOutValue);
        if ($leaveMinutes !== null && $breakOutMinutes !== null) {
            return (
                $this->minutesBetween($startMinutes, $leaveMinutes)
                + $this->minutesBetween($breakOutMinutes, $endMinutes)
            ) / 60;
        }

        return $this->minutesBetween($startMinutes, $endMinutes) / 60;
    }

    private function parseTimeMinutes(mixed $value): ?int
    {
        return AttendanceTime::parseMinutes($value);
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

    private function minutesBetween(int $startMinutes, int $endMinutes): int
    {
        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return max(0, $endMinutes - $startMinutes);
    }

    private function toFloat(mixed $value): float
    {
        $text = trim((string) $value);

        return $text === '' || !is_numeric($text) ? 0.0 : (float) $text;
    }
}
