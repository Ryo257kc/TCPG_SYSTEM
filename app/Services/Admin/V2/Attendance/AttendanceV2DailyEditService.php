<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2DailyEditService
{
    /**
     * @var array<string, string> 呼び出し元の$values配列キー => mx_time_cardsのカラム名
     */
    private const TEXT_COLUMNS = [
        'attendance_category' => 'work_type',
        'work_store' => 'work_store',
        'shift_start' => 'shift_start',
        'shift_leave' => 'shift_leave',
        'shift_break_out' => 'shift_break_out',
        'shift_end' => 'shift_end',
        'change_start' => 'change_start',
        'change_leave' => 'change_leave',
        'change_break_out' => 'change_break_out',
        'change_end' => 'change_end',
        'timecard_note' => 'timecard_note',
        'return_note' => 'return_note',
    ];

    /** @var array<string, string> */
    private const NUMBER_COLUMNS = [
        'category_time' => 'work_type_time',
        'paid_leave_used' => 'paid_leave_used',
        'change_scheduled' => 'change_scheduled',
        'overtime' => 'overtime',
    ];

    // 勤怠の日別編集を mx_time_cards へ保存するサービス。
    // 月次集計は保存後に AttendanceV2MonthlySummaryService が読み直して作るため、ここでは日別値の保存だけを担当する。
    //
    // 管理側（admin/attendance）はシフト・実績・備考等をまとめて編集するフル編集フォームで、
    // 毎回全項目を$valuesに含めて呼ぶ。一方スタッフ側（実績変更）はchange_*・overtime・
    // timecard_note等の一部項目しか扱わない部分編集フォームで、shift_*等のキーを
    // そもそも$valuesに含めない。以前は「キーが無ければnull」を$payloadに詰めていたため、
    // スタッフが実績変更を保存するたびにshift_*等が意図せずNULLで上書きされ、
    // シフトが消えたように見えるバグになっていた。呼び出し元が明示的に渡したキーだけを
    // 更新対象にすることで、扱っていない項目には触れないようにする。
    public function update(array $values): int
    {
        $payload = [];

        foreach (self::TEXT_COLUMNS as $inputKey => $column) {
            if (array_key_exists($inputKey, $values)) {
                $payload[$column] = $this->nullableText($values[$inputKey]);
            }
        }

        foreach (self::NUMBER_COLUMNS as $inputKey => $column) {
            if (array_key_exists($inputKey, $values)) {
                $payload[$column] = $this->nullableNumber($values[$inputKey]);
            }
        }

        if (array_key_exists('paid_leave_requested_at', $values)) {
            $payload['paid_leave_requested_at'] = $values['paid_leave_requested_at'];
        }

        if (array_key_exists('holiday_category', $values)) {
            $payload['holiday_category'] = trim((string) $values['holiday_category']);
        }

        if (array_key_exists('is_returned', $values)) {
            $payload['is_returned'] = (int) $values['is_returned'];
        }

        if ($payload === []) {
            return 0;
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [(string) $values['time_card_key']])
            ->whereRaw('CONVERT(date, work_date) = ?', [(string) $values['work_date']])
            ->update($payload);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableNumber(mixed $value): ?float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return is_numeric($text) ? (float) $text : null;
    }
}
