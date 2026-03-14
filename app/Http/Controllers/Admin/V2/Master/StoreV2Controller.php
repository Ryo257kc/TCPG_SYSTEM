<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\StoreV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreV2Controller extends Controller
{
    public function __construct(private readonly StoreV2Service $service)
    {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $selectedStoreCode = trim((string) $request->query('store_code', ''));
        $data = $this->service->list($keyword);
        $rows = $data['rows'];

        if ($selectedStoreCode === '' && $rows !== []) {
            $selectedStoreCode = (string) ($rows[0]['store_code'] ?? '');
        }

        $selectedRow = null;
        foreach ($rows as $row) {
            if ((string) ($row['store_code'] ?? '') === $selectedStoreCode) {
                $selectedRow = $row;
                break;
            }
        }

        return view('admin_v2.master.store.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'selectedStoreCode' => $selectedStoreCode,
            'selectedRow' => $selectedRow,
            'companyOptions' => $data['companyOptions'],
            'rowCount' => count($rows),
            'source' => 'mx_stores',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_code' => ['required', 'string', 'max:50'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            'visit_area' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', 'max:50'],
            'store_short_name' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'address_kana' => ['nullable', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_closed' => ['nullable', 'in:0,1'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $updated = $this->service->update($validated);

        return redirect()
            ->route('admin.master.store', [
                'q' => (string) ($validated['q'] ?? ''),
                'store_code' => (string) ($validated['store_code'] ?? ''),
            ])
            ->with('status', $updated > 0 ? '更新しました。' : '変更はありません。');
    }
}
