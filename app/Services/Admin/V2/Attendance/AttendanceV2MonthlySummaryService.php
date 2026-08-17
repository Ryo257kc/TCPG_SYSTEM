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
    private const PAID_LEAVE = "\u{6709}\u{4F11}";
    private const PAID_LEAVE_HALF = "\u{6709}\u{534A}";
    private const SUBSTITUTE_HOLIDAY = "\u{632F}\u{4F11}";

    /**
     * 出勤扱いにしない「休みの日」区分かどうか。
     *
     * 月次集計（このクラス）だけでなく、日別勤怠テーブルの所定時間表示
     * （AttendanceV2DailyTableItemBuilder）でも、change_scheduledが空の時に
     * シフト予定時間へフォールバックする判定に使う（休みの日はフォールバックさせない）。
     */
    public function isRestCategory(string $attendanceCategory): bool
    {
        return in_array(
            trim($attendanceCategory),
            [self::ABSENCE, self::PAID_LEAVE, self::PAID_LEAVE_HALF, self::SUBSTITUTE_HOLIDAY],
            true
        );
    }

    /**
     * 勤怠チェック用の検算差分。
     *
     * シフト予定 − 休みの日の予定時間 − 遅早時間 + 残業 + 休日出勤の実働時間 が
     * 実働時間(change_scheduled_total)と一致するはず、という運用チェック式（Access時代から
     * ユーザーが手動でやっていたもの）をここに集約する。0でなければ本人申請の変更実績が
     * シフト・残業・休出等の入力と合ってない可能性がある（2026-08-17）。
     *
     * 要確認：休日出勤の日にシフトが残っている（shift_scheduled_totalにその日の時間が
     * 入ったまま）場合、この式は意図的にズレを検知する。休日出勤にする日はシフト側を
     * 消すのが正しい運用のため、シフトが残っているのはデータ不備であり、検算式側で
     * 相殺してはいけない（2026-08-17、staff069・2026年7月でユーザー確認：シフトの
     * 組み間違いに休出フラグだけ立てて所定を消し忘れていた実例）。
     *
     * @param array<string,mixed> $summary
     */
    public function reconciliationDiff(array $summary): float
    {
        $shiftTotal = (float) ($summary['shift_scheduled_total'] ?? 0);
        $restShiftHours = (float) ($summary['rest_shift_hours_total'] ?? 0);
        $lateEarly = (float) ($summary['late_early_time'] ?? 0);
        $overtime = (float) ($summary['overtime_total'] ?? 0);
        $holidayWorkActual = (float) ($summary['work_time_total'] ?? 0) - (float) ($summary['work_time_total_net'] ?? 0);
        $changeScheduledTotal = (float) ($summary['change_scheduled_total'] ?? 0);

        $expected = $shiftTotal - $restShiftHours - $lateEarly + $overtime + $holidayWorkActual;

        return round($expected - $changeScheduledTotal, 2);
    }

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
            'work_days_net' => 0.0,
            'work_time_total_net' => 0.0,
            'absence_days' => 0.0,
            'holiday_work_days' => 0.0,
            'holiday_work_time' => 0.0,
            'late_early_days' => 0.0,
            'late_early_time' => 0.0,
            'paid_leave_days' => 0.0,
            'rest_shift_hours_total' => 0.0,
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
                'work_days_net',
                'work_time_total_net',
                'absence_days',
                'holiday_work_days',
                'holiday_work_time',
                'late_early_days',
                'late_early_time',
                'paid_leave_days',
                'rest_shift_hours_total',
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
            // work_in_num_net/work_time_net：休日出勤を除いた出勤日数・実働時間（2026-08-17新設）。
            // work_in_num/work_timeは休日出勤分を含む総勤務日数・総実働時間として維持したまま、
            // 別カラムに休日出勤を除いた値を追加で保存する（既存列の意味は変えない）。
            'work_in_num_net' => round((float) ($summary['work_days_net'] ?? 0), 2),
            // 所定時間：平日実働時間からさらに残業時間を除いた、基本給計算(PayrollV2RecalculateService::
            // basicSalaryAmount())と同じ「通常時間のみ」の値（2026-08-17）。
            'work_time_net' => round(max(0.0, (float) ($summary['work_time_total_net'] ?? 0) - (float) ($summary['overtime_total'] ?? 0)), 2),
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
        $isAbsent = $attendanceCategory === self::ABSENCE;
        $isHolidayWork = in_array($attendanceCategory, [self::HOLIDAY_WORK, self::LEGAL_HOLIDAY_WORK], true);
        // 要確認：有休・有半・振休は「休みの日」であり出勤ではない。change_scheduledに
        // シフト予定時間へのフォールバックで値が入ってしまうことがあり（例：有休で
        // change_start/endが空のためshift_scheduledの8時間が使われる）、除外しないと
        // 出勤日数・実働時間・change_scheduled_total（勤怠一覧の「実働時間」列）に
        // 休みの日まで反映されてしまう（2026-08-17）。resolvedChangeScheduled()自体に
        // 渡し、シフト予定時間へのフォールバックを休みの日はさせないようにする。
        // ただし有半は半日だけ休みで残り半日は実際に働くため、hasWork自体は塞がない
        // （塞ぐと有半で働いた分の時間が出勤日数・実働時間から消えてしまう）。
        $isFullRestDay = in_array($attendanceCategory, [self::ABSENCE, self::PAID_LEAVE, self::SUBSTITUTE_HOLIDAY], true);
        $isHalfPaidLeave = $attendanceCategory === self::PAID_LEAVE_HALF;
        $isRestCategory = $isFullRestDay || $isHalfPaidLeave;
        $changeScheduled = $this->resolvedChangeScheduled($row, $shiftScheduled, $isRestCategory);
        $hasWork = !$isFullRestDay && $changeScheduled > 0;

        if ($hasWork) {
            // work_days/work_time_totalはAccess時代から「総勤務日数・総実働時間」（休日出勤分も
            // 含む合算値）として明細・賃金台帳に出力され続けている。holiday_work_days/
            // holiday_work_timeはその内訳（休日出勤分だけ）を別行で見せるための値であり、
            // ここから差し引く（totalから除外する）と過去の集計と数字の意味が変わってしまう
            // ため、合算のまま維持する（2026-08-17、一度分離したが要望により合算へ戻した）。
            $summary['work_days'] = 1.0;
            $summary['work_time_total'] = $changeScheduled;

            if (!$isHolidayWork) {
                $summary['work_days_net'] = 1.0;
                $summary['work_time_total_net'] = $changeScheduled;
            }
        }

        if ($isAbsent) {
            $summary['absence_days'] = trim((string) ($row->holiday_category ?? '')) === AttendanceV2HolidayCategoryService::CATEGORY_HALF_DAY ? 0.5 : 1.0;
        }

        if ($isFullRestDay) {
            // 検算用：休みの日にシフトが本来入っていた分の時間。reconciliationDiff()で使う。
            $summary['rest_shift_hours_total'] = $shiftScheduled;
        } elseif ($isHalfPaidLeave) {
            // 有半はシフトのうち実際に働いた分(changeScheduled)を除いた残り半分だけが
            // 休んだ時間になる（丸ごとシフト分を引くと働いた分まで検算からズレてしまう）。
            $summary['rest_shift_hours_total'] = max(0.0, $shiftScheduled - $changeScheduled);
        }

        if ($isHolidayWork) {
            // 要確認：休日出勤時間は元々work_type_time（手入力欄で空のことが多い）を使っていて、
            // change_scheduledから計算したwork_time_total/work_time_total_netの差分と食い違う
            // ことがあった（work_type_timeが空だと0円になるが、実際は打刻時刻から時間が
            // 計算できていた）。同じchangeScheduledを使い、総実働時間から平日実働時間を引いた
            // 値と必ず一致するようにする（2026-08-17）。
            $summary['holiday_work_days'] = 1.0;
            $summary['holiday_work_time'] = $changeScheduled;
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

    private function resolvedChangeScheduled(object $row, float $shiftScheduled, bool $isRestCategory = false): float
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

        // 休みの日（有休・有半・振休・欠勤）は、change_scheduled/change_start等が空でも
        // シフト予定時間へフォールバックしない（休みの日にシフト時間が実働扱いされてしまうため）。
        if ($isRestCategory) {
            return 0.0;
        }

        return $shiftScheduled;
    }

    private function scheduledHours(
        mixed $startValue,
        mixed $leaveValue,
        mixed $breakOutValue,
        mixed $endValue,
    ): float {
        return AttendanceTime::scheduledHours($startValue, $leaveValue, $breakOutValue, $endValue);
    }

    private function hasAnyTimeValue(array $values): bool
    {
        return AttendanceTime::hasAnyTimeValue($values);
    }

    private function toFloat(mixed $value): float
    {
        $text = trim((string) $value);

        return $text === '' || !is_numeric($text) ? 0.0 : (float) $text;
    }
}
