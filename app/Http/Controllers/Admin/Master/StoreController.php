<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $keyword = trim((string) $request->query('q', ''));

        $query = DB::connection('sqlsrv')
            ->table('dbo.m_stores')
            ->select([
                'store_code',
                'store_name',
                'company_name',
                'store_short_name',
                'phone',
                'is_closed',
                'legacy_no',
            ])
            ->orderBy('store_code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('store_code', 'like', '%' . $keyword . '%')
                    ->orWhere('store_name', 'like', '%' . $keyword . '%')
                    ->orWhere('company_name', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()
            ->map(fn ($r) => [
                'store_code' => (string) ($r->store_code ?? ''),
                'store_name' => (string) ($r->store_name ?? ''),
                'company_name' => (string) ($r->company_name ?? ''),
                'store_short_name' => (string) ($r->store_short_name ?? ''),
                'phone' => (string) ($r->phone ?? ''),
                'is_closed' => (int) ($r->is_closed ?? 0),
                'legacy_no' => (string) ($r->legacy_no ?? ''),
            ])
            ->all();

        return view('admin.master.store.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'm_stores',
        ]);
    }
}
