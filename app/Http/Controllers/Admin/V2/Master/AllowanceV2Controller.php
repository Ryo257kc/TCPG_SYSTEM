<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\AllowanceV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AllowanceV2Controller extends Controller
{
    public function __construct(private readonly AllowanceV2Service $service)
    {
    }

    public function index(Request $request): View
    {
        $selectedOfficeName = trim((string) $request->query('office_name', ''));
        $data = $this->service->list($selectedOfficeName);

        return view('admin_v2.master.allowance.index', [
            'rows' => $data['rows'],
            'companyOptions' => $data['companyOptions'],
            'selectedOfficeName' => $selectedOfficeName,
            'rowCount' => count($data['rows']),
            'source' => $data['source'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'allowance_no' => ['required', 'integer'],
            'allowance_name' => ['nullable', 'string', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'amount_column_key' => ['nullable', 'string', 'max:50'],
            'office_name_filter' => ['nullable', 'string', 'max:20'],
            'rou_target' => ['nullable', 'in:1'],
            'syaho_target' => ['nullable', 'in:1'],
            'tax_target' => ['nullable', 'in:1'],
            'kotei_wage' => ['nullable', 'in:1'],
            'warimasi_kiso' => ['nullable', 'in:1'],
            'koujyo_kiso' => ['nullable', 'in:1'],
        ]);

        $status = $this->service->update(array_map(fn ($x) => is_string($x) ? $x : (string) $x, $v));
        return redirect()->route('admin.master.allowance', ['office_name' => (string) ($v['office_name_filter'] ?? '')])->with('status', $status);
    }
}