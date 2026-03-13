<?php

namespace App\Services\Admin\V2\Master;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CalendarV2Service
{
    private const PUBLIC_HOLIDAY_CSV_URL = 'https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv';
    private const WORK_HOLIDAY_PUBLIC = '祝日';

    /** @return array{selectedYear:string,yearOptions:list<string>,rows:list<array<string,string>>} */
    public function list(string $year): array
    {
        $currentYear = (int) now('Asia/Tokyo')->format('Y');
        $selectedYear = preg_match('/^\d{4}$/', $year) === 1 ? $year : (string) $currentYear;
        $yearValue = (int) $selectedYear;

        $existingYears = DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->selectRaw('YEAR(calendar_day) as y')
            ->groupByRaw('YEAR(calendar_day)')
            ->orderByRaw('YEAR(calendar_day) desc')
            ->get()
            ->map(fn ($row): string => sprintf('%04d', (int) $row->y))
            ->all();

        $yearOptions = collect([
            (string) ($currentYear - 1),
            (string) $currentYear,
            (string) ($currentYear + 1),
            (string) ($currentYear + 2),
            (string) ($currentYear + 3),
            $selectedYear,
            ...$existingYears,
        ])
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $fromDate = sprintf('%04d-01-01 00:00:00', $yearValue);
        $toDate = sprintf('%04d-12-31 23:59:59', $yearValue);

        $calendarMap = DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->whereBetween('calendar_day', [$fromDate, $toDate])
            ->orderBy('calendar_day')
            ->get()
            ->mapWithKeys(function ($row): array {
                $date = date('Y-m-d', strtotime((string) ($row->calendar_day ?? 'now')));

                return [
                    $date => [[
                        'public_holiday' => trim((string) ($row->public_holiday ?? '')),
                        'work_holiday' => trim((string) ($row->work_holiday ?? '')),
                    ]],
                ];
            })
            ->all();

        $weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
        $period = new DatePeriod(
            new DateTimeImmutable(sprintf('%04d-01-01', $yearValue)),
            new DateInterval('P1D'),
            (new DateTimeImmutable(sprintf('%04d-12-31', $yearValue)))->modify('+1 day')
        );

        $rows = [];
        foreach ($period as $day) {
            $date = $day->format('Y-m-d');
            $calendarRow = $calendarMap[$date][0] ?? ['public_holiday' => '', 'work_holiday' => ''];
            if ($calendarRow['public_holiday'] === '' && $calendarRow['work_holiday'] === '') {
                continue;
            }

            $rows[] = [
                'calendar_day' => $date,
                'date_label' => $day->format('Y/m/d'),
                'weekday_label' => $weekdayLabels[(int) $day->format('w')],
                'public_holiday' => $calendarRow['public_holiday'],
                'work_holiday' => $calendarRow['work_holiday'],
            ];
        }

        return [
            'selectedYear' => $selectedYear,
            'yearOptions' => $yearOptions,
            'rows' => $rows,
        ];
    }

    public function update(array $values): int
    {
        $date = (string) $values['calendar_day'];
        $publicHoliday = $this->nullableText($values['public_holiday'] ?? null);
        $workHoliday = $this->nullableText($values['work_holiday'] ?? null);

        $existing = DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->select('calendar_day', 'public_holiday', 'work_holiday')
            ->whereRaw('CONVERT(date, calendar_day) = ?', [$date])
            ->first();

        if ($existing === null) {
            if ($publicHoliday === null && $workHoliday === null) {
                return 0;
            }

            DB::connection('sqlsrv')
                ->table('dbo.mx_calendar')
                ->insert([
                    'calendar_day' => $date . ' 00:00:00',
                    'public_holiday' => $publicHoliday,
                    'work_holiday' => $workHoliday,
                ]);

            return 1;
        }

        $currentPublicHoliday = $this->nullableText($existing->public_holiday ?? null);
        $currentWorkHoliday = $this->nullableText($existing->work_holiday ?? null);

        if ($publicHoliday === null && $workHoliday === null) {
            return DB::connection('sqlsrv')
                ->table('dbo.mx_calendar')
                ->whereRaw('CONVERT(date, calendar_day) = ?', [$date])
                ->delete();
        }

        if ($currentPublicHoliday === $publicHoliday && $currentWorkHoliday === $workHoliday) {
            return 0;
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->whereRaw('CONVERT(date, calendar_day) = ?', [$date])
            ->update([
                'public_holiday' => $publicHoliday,
                'work_holiday' => $workHoliday,
            ]);
    }

    public function delete(string $date): int
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->whereRaw('CONVERT(date, calendar_day) = ?', [$date])
            ->delete();
    }

    /** @return array{inserted:int,updated:int,deleted:int} */
    public function importPublicHolidays(int $year): array
    {
        $holidays = $this->fetchPublicHolidays($year);

        $fromDate = sprintf('%04d-01-01', $year);
        $toDate = sprintf('%04d-12-31', $year);

        $existingRows = DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->select('calendar_day', 'public_holiday', 'work_holiday')
            ->whereBetween('calendar_day', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->get()
            ->mapWithKeys(function ($row): array {
                $date = date('Y-m-d', strtotime((string) ($row->calendar_day ?? 'now')));

                return [
                    $date => [[
                        'public_holiday' => $this->nullableText($row->public_holiday ?? null),
                        'work_holiday' => $this->nullableText($row->work_holiday ?? null),
                    ]],
                ];
            });

        $inserted = 0;
        $updated = 0;
        $deleted = 0;

        foreach ($holidays as $date => $name) {
            $existing = $existingRows->get($date)[0] ?? null;

            if ($existing === null) {
                DB::connection('sqlsrv')
                    ->table('dbo.mx_calendar')
                    ->insert([
                        'calendar_day' => $date . ' 00:00:00',
                        'public_holiday' => $name,
                        'work_holiday' => self::WORK_HOLIDAY_PUBLIC,
                    ]);
                $inserted++;
                continue;
            }

            if (($existing['work_holiday'] ?? null) !== null && ($existing['work_holiday'] ?? null) !== self::WORK_HOLIDAY_PUBLIC) {
                continue;
            }

            $updates = [];
            if (($existing['public_holiday'] ?? null) !== $name) {
                $updates['public_holiday'] = $name;
            }
            if (($existing['work_holiday'] ?? null) === null || ($existing['work_holiday'] ?? null) === self::WORK_HOLIDAY_PUBLIC) {
                if (($existing['work_holiday'] ?? null) !== self::WORK_HOLIDAY_PUBLIC) {
                    $updates['work_holiday'] = self::WORK_HOLIDAY_PUBLIC;
                }
            }

            if ($updates !== []) {
                DB::connection('sqlsrv')
                    ->table('dbo.mx_calendar')
                    ->whereRaw('CONVERT(date, calendar_day) = ?', [$date])
                    ->update($updates);
                $updated++;
            }
        }

        foreach ($existingRows as $date => $rows) {
            if (array_key_exists($date, $holidays)) {
                continue;
            }

            $existing = $rows[0];
            if (($existing['public_holiday'] ?? null) === null) {
                continue;
            }

            if (($existing['work_holiday'] ?? null) !== null && ($existing['work_holiday'] ?? null) !== self::WORK_HOLIDAY_PUBLIC) {
                continue;
            }

            if (($existing['work_holiday'] ?? null) === self::WORK_HOLIDAY_PUBLIC) {
                DB::connection('sqlsrv')
                    ->table('dbo.mx_calendar')
                    ->whereRaw('CONVERT(date, calendar_day) = ?', [$date])
                    ->delete();
                $deleted++;
            }
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }

    /** @return array<string,string> */
    private function fetchPublicHolidays(int $year): array
    {
        $response = Http::timeout(20)->get(self::PUBLIC_HOLIDAY_CSV_URL);

        if (! $response->successful()) {
            throw new RuntimeException('内閣府CSVの取得に失敗しました。');
        }

        $csv = mb_convert_encoding($response->body(), 'UTF-8', 'SJIS-win,UTF-8');
        $lines = preg_split("/\r\n|\n|\r/", trim($csv)) ?: [];
        $holidays = [];

        foreach ($lines as $index => $line) {
            if ($line === '') {
                continue;
            }

            $columns = str_getcsv($line);
            if (count($columns) < 2) {
                continue;
            }

            if ($index === 0 && str_contains((string) $columns[0], '国民の祝日')) {
                continue;
            }

            $date = $this->normalizeCsvDate((string) $columns[0]);
            if ($date === null || (int) substr($date, 0, 4) !== $year) {
                continue;
            }

            $name = trim((string) $columns[1]);
            if ($name === '') {
                continue;
            }

            $holidays[$date] = $name;
        }

        if ($holidays === []) {
            throw new RuntimeException('対象年の祝日が取得できませんでした。');
        }

        ksort($holidays);

        return $holidays;
    }

    private function normalizeCsvDate(string $value): ?string
    {
        $text = trim($value);
        if ($text === '') {
            return null;
        }

        $date = date_create_immutable($text);

        return $date === false ? null : $date->format('Y-m-d');
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
