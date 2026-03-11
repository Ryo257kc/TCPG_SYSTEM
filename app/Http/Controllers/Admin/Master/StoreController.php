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
            ->table('dbo.mx_stores')
            ->select([
                'store_code',
                'store_name',
                'company_id',
                'company_name',
                'store_short_name',
                'phone',
                'is_closed',
                DB::raw('[No] as legacy_no'),
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
                'company_id' => trim((string) ($r->company_id ?? '')),
                'company_name' => (string) ($r->company_name ?? ''),
                'store_short_name' => (string) ($r->store_short_name ?? ''),
                'phone' => (string) ($r->phone ?? ''),
                'is_closed' => (int) ($r->is_closed ?? 0),
                'legacy_no' => (string) ($r->legacy_no ?? ''),
            ])
            ->all();

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->select(['company_id', 'company_name'])
            ->whereNotNull('company_id')
            ->whereNotNull('company_name')
            ->whereRaw("LTRIM(RTRIM(company_name)) <> ''")
            ->orderBy('company_id')
            ->get()
            ->map(fn ($r) => [
                'company_id' => trim((string) ($r->company_id ?? '')),
                'company_name' => trim((string) ($r->company_name ?? '')),
            ])
            ->filter(fn ($r) => $r['company_id'] !== '' && $r['company_name'] !== '')
            ->values()
            ->all();

        return view('admin.master.store.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'companyOptions' => $companyOptions,
            'rowCount' => count($rows),
            'source' => 'mx_stores',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $validated = $request->validate([
            'store_code' => ['required', 'string', 'max:50'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', 'max:50'],
            'store_short_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_closed' => ['nullable', 'in:0,1'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $storeCode = trim((string) ($validated['store_code'] ?? ''));
        if ($storeCode === '') {
            return redirect()->route('admin.master.store')->with('error', '店舗コードが不正です。');
        }

        $companyId = trim((string) ($validated['company_id'] ?? ''));
        $companyName = '';
        if ($companyId !== '') {
            $companyName = trim((string) (DB::connection('sqlsrv')
                ->table('dbo.mx_companies')
                ->whereRaw('LTRIM(RTRIM(CAST(company_id AS nvarchar(255)))) = ?', [$companyId])
                ->value('company_name') ?? ''));
        }

        $payload = [
            'store_name' => trim((string) ($validated['store_name'] ?? '')),
            'company_id' => $companyId === '' ? null : $companyId,
            'company_name' => $companyName,
            'store_short_name' => trim((string) ($validated['store_short_name'] ?? '')),
            'phone' => trim((string) ($validated['phone'] ?? '')),
            'is_closed' => (int) (($validated['is_closed'] ?? '0') === '1' ? 1 : 0),
        ];

        $updated = DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->whereRaw('LTRIM(RTRIM(store_code)) = ?', [$storeCode])
            ->update($payload);

        $query = array_filter([
            'q' => trim((string) ($validated['q'] ?? '')),
        ], static fn ($v) => $v !== '');

        $message = $updated > 0 ? '店舗情報を更新しました。' : '更新対象が見つかりませんでした。';
        return redirect()->route('admin.master.store', $query)->with('status', $message);
    }
}
