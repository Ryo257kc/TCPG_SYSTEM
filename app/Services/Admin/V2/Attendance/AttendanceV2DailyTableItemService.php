<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2DailyTableItemService
{
    public function __construct(
        private readonly AttendanceV2DailyService $dailyService,
    ) {
    }

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
        $dailyRows = $this->dailyService->rows($timeCardKeys, $year, $month);

        return [
            'dailyRows' => $dailyRows,
            'dailySummary' => $dailyRows === [] ? null : $this->dailyService->summary($dailyRows),
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
        return ['振出', '代出', '振休', '代休', '欠勤', '有休', '有半', '休出', '法出', '出張', '遅早'];
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
            ->filter(static fn (array $row): bool => $row['value'] !== '')
            ->unique('value')
            ->values()
            ->all();
    }
}
