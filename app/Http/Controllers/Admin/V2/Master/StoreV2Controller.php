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
        $data = $this->service->list($keyword);

        return view('admin_v2.master.store.index', [
            'keyword' => $keyword,
            'rows' => $data['rows'],
            'companyOptions' => $data['companyOptions'],
            'rowCount' => count($data['rows']),
            'source' => 'mx_stores',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'store_code' => ['required', 'string', 'max:50'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', 'max:50'],
            'store_short_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_closed' => ['nullable', 'in:0,1'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $updated = $this->service->update($v);
        return redirect()->route('admin.master.store', ['q' => (string) ($v['q'] ?? '')])->with('status', $updated > 0 ? '更新しました。' : '変更はありません。');
    }
}