<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $keyword = trim((string) $request->query('q', ''));

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.section', '=', 'st.store_code')
            ->select([
                DB::raw('ms.staff_id as staff_id'),
                'ms.staff_name',
                DB::raw('ms.staff_name_furi as staff_name_kana'),
                DB::raw('ms.section as store_code'),
                DB::raw('st.store_name as store_name'),
                DB::raw('ms.employment as employment_status'),
                DB::raw('ms.is_store_management_user as is_store_manager'),
                'ms.is_daily_report_user',
                DB::raw('ms.tai_date as retire_date'),
            ])
            ->orderBy('ms.staff_id');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('ms.staff_code', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_id', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_name', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.section', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()
            ->map(fn ($r) => [
                'staff_id' => (string) ($r->staff_id ?? ''),
                'staff_name' => (string) ($r->staff_name ?? ''),
                'staff_name_kana' => (string) ($r->staff_name_kana ?? ''),
                'store_code' => (string) ($r->store_code ?? ''),
                'store_name' => (string) ($r->store_name ?? ''),
                'employment_status' => (string) ($r->employment_status ?? ''),
                'is_store_manager' => (int) ($r->is_store_manager ?? 0),
                'is_daily_report_user' => (int) ($r->is_daily_report_user ?? 0),
                'retire_date' => (string) ($r->retire_date ?? ''),
            ])
            ->all();

        return view('admin.master.staff.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'mx_staffs',
        ]);
    }
}
