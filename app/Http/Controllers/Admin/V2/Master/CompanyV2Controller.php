<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CompanyV2RouhoService;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Master\CompanyV2ShahoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyV2Controller extends Controller
{
    public function __construct(
        private readonly CompanyV2Service $service,
        private readonly CompanyV2ShahoService $shahoService,
        private readonly CompanyV2RouhoService $rouhoService,
    ) {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedTab = trim((string) $request->query('tab', 'company'));
        $data = $this->service->list($keyword);
        $rows = $data['rows'];

        if ($selectedCompanyId === '' && $rows !== []) {
            $selectedCompanyId = (string) ($rows[0]['company_id'] ?? '');
        }

        $selectedRow = null;
        foreach ($rows as $row) {
            if ((string) ($row['company_id'] ?? '') === $selectedCompanyId) {
                $selectedRow = $row;
                break;
            }
        }

        $allowedTabs = ['company', 'syaho', 'rouho', 'mayor'];
        if (!in_array($selectedTab, $allowedTabs, true)) {
            $selectedTab = 'company';
        }

        $shaho = ['latest' => null, 'rows' => []];
        $rouho = ['latest' => null, 'rows' => []];
        if ($selectedCompanyId !== '') {
            $shaho = $this->shahoService->listByCompanyId($selectedCompanyId);
            $rouho = $this->rouhoService->listByCompanyId($selectedCompanyId);
        }

        return view('admin_v2.master.company.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedTab' => $selectedTab,
            'selectedRow' => $selectedRow,
            'selectedSyahoRow' => $shaho['latest'],
            'syahoRows' => $shaho['rows'],
            'selectedRouhoRow' => $rouho['latest'],
            'rouhoRows' => $rouho['rows'],
            'rowCount' => count($rows),
            'source' => 'mx_companies',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'company_name' => ['required', 'string', 'max:200'],
            'company_name_kana' => ['nullable', 'string', 'max:200'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'office_number' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:100'],
            'fax' => ['nullable', 'string', 'max:100'],
            'ceo_title' => ['nullable', 'string', 'max:100'],
            'ceo_name_kana' => ['nullable', 'string', 'max:200'],
            'ceo_name' => ['nullable', 'string', 'max:200'],
            'health_office_code' => ['nullable', 'string', 'max:100'],
            'pension_office_code' => ['nullable', 'string', 'max:100'],
            'corporate_number' => ['nullable', 'string', 'max:100'],
            'insurer_number' => ['nullable', 'string', 'max:100'],
            'insurer_name' => ['nullable', 'string', 'max:255'],
            'insurer_address' => ['nullable', 'string', 'max:255'],
            'employment_office_number' => ['nullable', 'string', 'max:100'],
            'labor_insurance_number' => ['nullable', 'string', 'max:100'],
            'labor_install_category' => ['nullable', 'string', 'max:100'],
            'labor_install_date' => ['nullable', 'string', 'max:100'],
            'labor_office_category' => ['nullable', 'string', 'max:100'],
            'labor_industry_category' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $this->service->update($v);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'company',
        ])->with('status', '会社情報を更新しました。');
    }

    public function storeShaho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:200'],
            'kenpo_apply_date' => ['nullable', 'date'],
            'kenpo_kyuyo' => ['nullable', 'string', 'max:50'],
            'kenpo_shoyo' => ['nullable', 'string', 'max:50'],
            'kaigo_apply_date' => ['nullable', 'date'],
            'kaigo_kyuyo' => ['nullable', 'string', 'max:50'],
            'kaigo_shoyo' => ['nullable', 'string', 'max:50'],
            'kou_apply_date' => ['nullable', 'date'],
            'kou_kyuyo' => ['nullable', 'string', 'max:50'],
            'kou_shoyo' => ['nullable', 'string', 'max:50'],
            'jidou_apply_date' => ['nullable', 'date'],
            'jidou_kyuyo' => ['nullable', 'string', 'max:50'],
            'jidou_shoyo' => ['nullable', 'string', 'max:50'],
            'basic_payment_days' => ['nullable', 'string', 'max:50'],
        ]);

        $this->shahoService->create($v);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'syaho',
        ])->with('status', '社会保険を追加しました。');
    }

    public function updateShaho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'syaho_no' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:200'],
            'kenpo_apply_date' => ['nullable', 'date'],
            'kenpo_kyuyo' => ['nullable', 'string', 'max:50'],
            'kenpo_shoyo' => ['nullable', 'string', 'max:50'],
            'kaigo_apply_date' => ['nullable', 'date'],
            'kaigo_kyuyo' => ['nullable', 'string', 'max:50'],
            'kaigo_shoyo' => ['nullable', 'string', 'max:50'],
            'kou_apply_date' => ['nullable', 'date'],
            'kou_kyuyo' => ['nullable', 'string', 'max:50'],
            'kou_shoyo' => ['nullable', 'string', 'max:50'],
            'jidou_apply_date' => ['nullable', 'date'],
            'jidou_kyuyo' => ['nullable', 'string', 'max:50'],
            'jidou_shoyo' => ['nullable', 'string', 'max:50'],
            'basic_payment_days' => ['nullable', 'string', 'max:50'],
        ]);

        $this->shahoService->update($v);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'syaho',
        ])->with('status', '社会保険を更新しました。');
    }

    public function deleteShaho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'syaho_no' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $this->shahoService->delete((string) $v['company_id'], (string) $v['syaho_no']);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'syaho',
        ])->with('status', '社会保険を削除しました。');
    }

    public function storeRouho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:200'],
            'rou_industry' => ['nullable', 'string', 'max:100'],
            'aggregate_period' => ['nullable', 'string', 'max:100'],
            'rou_apply_date' => ['nullable', 'date'],
            'general_st' => ['nullable', 'string', 'max:50'],
            'general_of' => ['nullable', 'string', 'max:50'],
            'nourin_st' => ['nullable', 'string', 'max:50'],
            'nourin_of' => ['nullable', 'string', 'max:50'],
            'kensetu_st' => ['nullable', 'string', 'max:50'],
            'kensetu_of' => ['nullable', 'string', 'max:50'],
            'rousai' => ['nullable', 'string', 'max:50'],
        ]);

        $this->rouhoService->create($v);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'rouho',
        ])->with('status', '労働保険を追加しました。');
    }

    public function updateRouho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'rou_no' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:200'],
            'rou_industry' => ['nullable', 'string', 'max:100'],
            'aggregate_period' => ['nullable', 'string', 'max:100'],
            'rou_apply_date' => ['nullable', 'date'],
            'general_st' => ['nullable', 'string', 'max:50'],
            'general_of' => ['nullable', 'string', 'max:50'],
            'nourin_st' => ['nullable', 'string', 'max:50'],
            'nourin_of' => ['nullable', 'string', 'max:50'],
            'kensetu_st' => ['nullable', 'string', 'max:50'],
            'kensetu_of' => ['nullable', 'string', 'max:50'],
            'rousai' => ['nullable', 'string', 'max:50'],
        ]);

        $this->rouhoService->update($v);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'rouho',
        ])->with('status', '労働保険を更新しました。');
    }

    public function deleteRouho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'company_id' => ['required', 'string', 'max:50'],
            'rou_no' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $this->rouhoService->delete((string) $v['company_id'], (string) $v['rou_no']);

        return redirect()->route('admin.master.company', [
            'q' => (string) ($v['q'] ?? ''),
            'company_id' => (string) ($v['company_id'] ?? ''),
            'tab' => 'rouho',
        ])->with('status', '労働保険を削除しました。');
    }
}