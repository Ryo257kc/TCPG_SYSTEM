<?php

namespace App\Services\Admin\V2\PaidLeave;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaidLeaveV2SummaryService
{
    public function __construct(
        private readonly PaidLeaveV2RemainingService $remainingService,
    ) {
    }

    public function build(string $staffId, int $selectedYear, int $page = 1, int $perPage = 20): array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return [
                'summary' => $this->emptySummary(),
                'historyRows' => [],
                'historyPager' => $this->emptyPager($page, $perPage),
            ];
        }

        $staff = (array) (DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select([
                DB::raw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) as staff_id'),
                'staff_name',
                'nyu_date',
            ])
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
            ->first() ?? []);

        $history = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->select([
                'yukyu_no',
                DB::raw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) as staff_id'),
                'remaining_day',
                'addition_day',
                'date_use',
                'days_used',
                'extinction_day',
                'lost_num',
            ])
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
            ->orderBy('yukyu_no')
            ->get();

        return [
            'summary' => $this->buildSummary($staff, $history),
            'historyRows' => $this->buildHistoryRows($history, $page, $perPage),
            'historyPager' => $this->buildHistoryPager($history, $page, $perPage),
        ];
    }

    private function buildSummary(array $staff, Collection $history): array
    {
        $joinDate = $this->dateOrNull($staff['nyu_date'] ?? null);

        $additionRows = $history->filter(fn ($row) => $this->dateOrNull($row->addition_day ?? null) !== null);
        $firstGrantDate = $joinDate?->copy()->addMonths(6);
        $lastGrantRow = $additionRows
            ->sortBy(fn ($row) => $this->dateOrNull($row->addition_day ?? null)?->format('Ymd') ?? '')
            ->last();
        $lastGrantDate = $lastGrantRow !== null ? $this->dateOrNull($lastGrantRow->addition_day ?? null) : null;
        $lastGrantDays = $lastGrantRow !== null ? $this->num($lastGrantRow->remaining_day ?? null) : 0.0;
        $nextGrantDate = ($lastGrantDate ?? $firstGrantDate)?->copy()->addYear();

        $remainingDays = $this->remainingService->remainingDays(trim((string) ($staff['staff_id'] ?? '')));
        // 要確認：有休は付与から2年で消滅するため、常に直近1回分の付与だけは保護され、
        // 残日数のうちそれを超える分は次の消滅処理で消える運命にある。上限は本来
        // 「直近の付与＋今回の新規付与」だが、残日数にはまだ今回分を含めていないので
        // 今回の付与数を知らなくても max(0, 残日数-直近の付与数) で同じ結果になる
        // （Accessの右上サマリー「消滅日数」と同じ考え方。以前はlost_numを消滅期限の年で
        // 拾っていたが、lost_numは付与のたびに手入力される実績値で見込み計算とは別物のため
        // 一致しないことがあった、2026-08-17）。
        $extinguishDays = max(0.0, $remainingDays - $lastGrantDays);
        $grantCount = (int) $additionRows->count();

        return [
            'staff_id' => trim((string) ($staff['staff_id'] ?? '')),
            'staff_name' => trim((string) ($staff['staff_name'] ?? '')),
            'join_date' => $this->formatDate($joinDate),
            'remaining_days' => $this->formatDayValue($remainingDays),
            'extinguish_days' => $this->formatDayValue($extinguishDays),
            'first_grant_date' => $this->formatDate($firstGrantDate),
            'next_grant_date' => $this->formatDate($nextGrantDate),
            'service_years_label' => $this->serviceYearsLabel($joinDate, Carbon::today()),
            'next_grant_days' => $this->formatDayValue($this->nextGrantDays($grantCount)),
            'grant_schedule' => $this->grantSchedule(),
        ];
    }

    private function buildHistoryRows(Collection $history, int $page, int $perPage): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        return $this->sortHistoryRows($history)
            ->slice($offset, $perPage)
            ->map(function ($row): array {
                return [
                    'yukyu_no' => (int) ($row->yukyu_no ?? 0),
                    'expire_date' => $this->formatDate($this->scheduledExpireDate($row)),
                    'grant_date' => $this->formatDate($this->dateOrNull($row->addition_day ?? null)),
                    'grant_days' => $this->formatDayValue($this->num($row->remaining_day ?? null)),
                    'expire_days' => $this->formatDayValue($this->num($row->lost_num ?? null)),
                    'used_date' => $this->formatDate($this->dateOrNull($row->date_use ?? null)),
                    'used_days' => $this->formatDayValue($this->num($row->days_used ?? null)),
                    'edit_addition_day' => $this->formatInputDate($this->dateOrNull($row->addition_day ?? null)),
                    'edit_remaining_day' => $this->formatInputNumber($this->numOrNull($row->remaining_day ?? null)),
                    'edit_lost_num' => $this->formatInputNumber($this->numOrNull($row->lost_num ?? null)),
                    'edit_date_use' => $this->formatInputDate($this->dateOrNull($row->date_use ?? null)),
                    'edit_days_used' => $this->formatInputNumber($this->numOrNull($row->days_used ?? null)),
                ];
            })
            ->values()
            ->all();
    }

    private function buildHistoryPager(Collection $history, int $page, int $perPage): array
    {
        $total = $this->sortHistoryRows($history)->count();
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $currentPage = min(max(1, $page), $lastPage);

        return [
            'page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $lastPage,
            'from' => $total === 0 ? 0 : (($currentPage - 1) * $perPage) + 1,
            'to' => min($total, $currentPage * $perPage),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'staff_id' => '',
            'staff_name' => '',
            'join_date' => null,
            'remaining_days' => null,
            'extinguish_days' => null,
            'first_grant_date' => null,
            'next_grant_date' => null,
            'service_years_label' => null,
            'next_grant_days' => null,
            'grant_schedule' => $this->grantSchedule(),
        ];
    }

    private function emptyPager(int $page, int $perPage): array
    {
        return [
            'page' => max(1, $page),
            'per_page' => $perPage,
            'total' => 0,
            'last_page' => 1,
            'has_prev' => false,
            'has_next' => false,
            'from' => 0,
            'to' => 0,
        ];
    }

    private function sortHistoryRows(Collection $history): Collection
    {
        return $history
            ->map(function ($row) {
                $sortDate = $this->historySortDate($row);
                return (object) [
                    'row' => $row,
                    'sort_date' => $sortDate,
                    'sort_key' => $sortDate?->format('YmdHis') ?? '',
                    'yukyu_no' => (int) ($row->yukyu_no ?? 0),
                ];
            })
            ->sort(function ($a, $b) {
                if ($a->sort_key === $b->sort_key) {
                    return $b->yukyu_no <=> $a->yukyu_no;
                }

                return strcmp($b->sort_key, $a->sort_key);
            })
            ->map(fn ($item) => $item->row)
            ->values();
    }

    // 要確認：以前は消滅期限日（addition_day+2年）も候補に入れて一番新しい日付を採用していたため、
    // 付与行が「付与日」ではなく2年後の期限日の位置に表示され、時系列が実際の出来事と
    // ズレて見えていた（付与行のlost_numは付与時点でのその回の付与分の消滅数で、期限日は
    // 参考情報の1列に過ぎない）。付与行は付与日、使用行は使用日という実際の出来事の日付だけで
    // 並べる（2026-08-17）。
    private function historySortDate(object $row): ?Carbon
    {
        return $this->dateOrNull($row->date_use ?? null)
            ?? $this->dateOrNull($row->addition_day ?? null);
    }

    private function nextGrantDays(int $grantCount): float
    {
        $schedule = [
            1 => 10,
            2 => 11,
            3 => 12,
            4 => 14,
            5 => 16,
            6 => 18,
        ];

        return (float) ($schedule[$grantCount + 1] ?? 20);
    }

    private function grantSchedule(): array
    {
        return [
            ['label' => '6ヶ月', 'days' => '10'],
            ['label' => '1年6ヶ月', 'days' => '11'],
            ['label' => '2年6ヶ月', 'days' => '12'],
            ['label' => '3年6ヶ月', 'days' => '14'],
            ['label' => '4年6ヶ月', 'days' => '16'],
            ['label' => '5年6ヶ月', 'days' => '18'],
            ['label' => '6年6ヶ月以上', 'days' => '20'],
        ];
    }

    private function serviceYearsLabel(?Carbon $joinDate, Carbon $cutoff): ?string
    {
        if ($joinDate === null || $joinDate->gt($cutoff)) {
            return null;
        }

        $diff = $joinDate->diff($cutoff);
        return $diff->y . '年' . $diff->m . 'ヶ月';
    }

    private function dateOrNull(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text);
        } catch (\Throwable) {
            return null;
        }
    }

    private function scheduledExpireDate(object $row): ?Carbon
    {
        $additionDate = $this->dateOrNull($row->addition_day ?? null);
        return $additionDate?->copy()->addYears(2);
    }

    private function formatDate(?Carbon $date): ?string
    {
        return $date?->format('Y/m/d');
    }

    private function num(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $text = trim(str_replace(',', '', (string) $value));
        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function numOrNull(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = trim(str_replace(',', '', (string) $value));
        return $text !== '' && is_numeric($text) ? (float) $text : null;
    }

    private function formatInputDate(?Carbon $date): string
    {
        return $date?->format('Y-m-d') ?? '';
    }

    private function formatInputNumber(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        if (abs($value - round($value)) < 0.0001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function formatDayValue(float $value): string
    {
        if (abs($value - round($value)) < 0.0001) {
            return number_format($value, 0);
        }

        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }
}
