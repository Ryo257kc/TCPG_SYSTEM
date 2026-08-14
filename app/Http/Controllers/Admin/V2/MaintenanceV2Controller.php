<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenanceV2Controller extends Controller
{
    public function index(): View
    {
        $windows = DB::connection('sqlsrv')
            ->table('dbo.mx_maintenance_windows')
            ->orderByDesc('start_at')
            ->get()
            ->map(fn($row): array => [
                'maintenance_id' => (int) $row->maintenance_id,
                'start_at' => $row->start_at,
                'end_at' => $row->end_at,
                'message' => trim((string) ($row->message ?? '')),
                'is_active' => now('Asia/Tokyo')->between($row->start_at, $row->end_at),
            ])
            ->all();

        return view('admin_v2.work.maintenance.index', [
            'windows' => $windows,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'message' => ['nullable', 'string', 'max:200'],
        ]);

        DB::connection('sqlsrv')->table('dbo.mx_maintenance_windows')->insert([
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'message' => trim((string) ($validated['message'] ?? '')),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.work.maintenance')->with('status', 'メンテナンス予定を追加しました。');
    }

    public function destroy(Request $request, string $maintenanceId): RedirectResponse
    {
        DB::connection('sqlsrv')
            ->table('dbo.mx_maintenance_windows')
            ->where('maintenance_id', $maintenanceId)
            ->delete();

        return redirect()->route('admin.work.maintenance')->with('status', '削除しました。');
    }
}
