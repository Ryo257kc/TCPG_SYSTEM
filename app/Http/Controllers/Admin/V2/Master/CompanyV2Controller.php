<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CompanyV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyV2Controller extends Controller
{
    public function __construct(private readonly CompanyV2Service $service)
    {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $data = $this->service->list($keyword);

        return view('admin_v2.master.company.index', [
            'keyword' => $keyword,
            'rows' => $data['rows'],
            'rowCount' => count($data['rows']),
            'source' => 'mx_companies',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'company_name' => ['required', 'string', 'max:200'],
            'company_code' => ['nullable', 'string', 'max:50'],
            'company_name_kana' => ['nullable', 'string', 'max:200'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'office_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'fax' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $this->service->update($v);

        return redirect()->route('admin.master.company', ['q' => (string) ($v['q'] ?? '')])->with('status', '更新しました。');
    }
}