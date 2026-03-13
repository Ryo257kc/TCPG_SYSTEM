<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CalendarV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CalendarV2Controller extends Controller
{
    public function __construct(private readonly CalendarV2Service $service)
    {
    }

    public function index(Request $request): View
    {
        $year = trim((string) $request->query('year', ''));
        $data = $this->service->list($year);

        return view('admin_v2.master.calendar.index', [
            'selectedYear' => $data['selectedYear'],
            'yearOptions' => $data['yearOptions'],
            'rows' => $data['rows'],
            'rowCount' => count($data['rows']),
            'source' => 'mx_calendar',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'calendar_day' => ['required', 'date'],
            'public_holiday' => ['nullable', 'string', 'max:100'],
            'work_holiday' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'regex:/^\d{4}$/'],
        ]);

        $updated = $this->service->update($v);
        $status = $updated > 0 ? '更新しました。' : '変更はありません。';

        return redirect()
            ->route('admin.master.calendar', ['year' => (string) ($v['year'] ?? '')])
            ->with('status', $status);
    }

    public function importPublicHolidays(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'year' => ['required', 'regex:/^\d{4}$/'],
        ]);

        try {
            $result = $this->service->importPublicHolidays((int) $v['year']);
            $status = sprintf(
                '祝日を反映しました。追加 %d / 更新 %d / 削除 %d',
                $result['inserted'],
                $result['updated'],
                $result['deleted']
            );
        } catch (Throwable $e) {
            $status = '祝日取得に失敗しました。' . $e->getMessage();
        }

        return redirect()
            ->route('admin.master.calendar', ['year' => (string) $v['year']])
            ->with('status', $status);
    }

    public function delete(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'calendar_day' => ['required', 'date'],
            'year' => ['required', 'regex:/^\d{4}$/'],
        ]);

        $deleted = $this->service->delete((string) $v['calendar_day']);
        $status = $deleted > 0 ? '削除しました。' : '削除対象はありません。';

        return redirect()
            ->route('admin.master.calendar', ['year' => (string) $v['year']])
            ->with('status', $status);
    }
}
