<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\DepartmentV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentV2Controller extends Controller
{
    public function __construct(private readonly DepartmentV2Service $service)
    {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $data = $this->service->list($keyword);

        return view('admin_v2.master.department.index', [
            'keyword' => $keyword,
            'rows' => $data['rows'],
            'rowCount' => count($data['rows']),
            'source' => $data['source'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_no' => ['required', 'string', 'max:50'],
            'store_category' => ['nullable', 'string', 'max:255'],
            'official_store_no' => ['nullable', 'string', 'max:50'],
            'receipt_store_no' => ['nullable', 'string', 'max:50'],
            'has_receipt' => ['nullable', 'in:1'],
            'bank_account_selection' => ['nullable', 'string', 'max:255'],
            'closed_on' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $updated = $this->service->update($validated);

        return redirect()
            ->route('admin.master.department', ['q' => (string) ($validated['q'] ?? '')])
            ->with('status', $updated > 0 ? '更新しました。' : '変更はありません。');
    }
}
