<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2DailyEditService
{
    public function update(array $values): int
    {
        $payload = [
            'work_type' => $this->nullableText($values['attendance_category'] ?? null),
            'work_type_time' => $this->nullableNumber($values['category_time'] ?? null),
            'paid_leave_used' => $this->nullableNumber($values['paid_leave_used'] ?? null),
            'work_store' => $this->nullableText($values['work_store'] ?? null),
            'shift_start' => $this->nullableText($values['shift_start'] ?? null),
            'shift_leave' => $this->nullableText($values['shift_leave'] ?? null),
            'shift_break_out' => $this->nullableText($values['shift_break_out'] ?? null),
            'shift_end' => $this->nullableText($values['shift_end'] ?? null),
            'change_start' => $this->nullableText($values['change_start'] ?? null),
            'change_leave' => $this->nullableText($values['change_leave'] ?? null),
            'change_break_out' => $this->nullableText($values['change_break_out'] ?? null),
            'change_end' => $this->nullableText($values['change_end'] ?? null),
            'change_scheduled' => $this->nullableNumber($values['change_scheduled'] ?? null),
            'overtime' => $this->nullableNumber($values['overtime'] ?? null),
            'timecard_note' => $this->nullableText($values['timecard_note'] ?? null),
            'return_note' => $this->nullableText($values['return_note'] ?? null),
        ];

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
