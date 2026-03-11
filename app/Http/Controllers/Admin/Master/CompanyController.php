<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $keyword = trim((string) $request->query('q', ''));

        $hasLegacyStoreNo = $this->hasColumn('mx_companies', 'legacy_store_no');
        $hasCompanyCode = $this->hasColumn('mx_companies', 'company_code');
        $hasCompanyNameKana = $this->hasColumn('mx_companies', 'company_name_kana');
        $hasCompanyAddress = $this->hasColumn('mx_companies', 'company_address');
        $hasOfficeNumber = $this->hasColumn('mx_companies', 'office_number');
        $hasPhone = $this->hasColumn('mx_companies', 'phone');
        $hasFax = $this->hasColumn('mx_companies', 'fax');

        $selects = ['company_id', 'company_name'];
        if ($hasLegacyStoreNo) { $selects[] = 'legacy_store_no'; }
        if ($hasCompanyCode) { $selects[] = 'company_code'; }
        if ($hasCompanyNameKana) { $selects[] = 'company_name_kana'; }
        if ($hasCompanyAddress) { $selects[] = 'company_address'; }
        if ($hasOfficeNumber) { $selects[] = 'office_number'; }
        if ($hasPhone) { $selects[] = 'phone'; }
        if ($hasFax) { $selects[] = 'fax'; }

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->select($selects)
            ->orderBy('company_id');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('company_name', 'like', '%' . $keyword . '%')
                    ->orWhere('company_code', 'like', '%' . $keyword . '%')
                    ->orWhere('office_number', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()
            ->map(fn ($r) => [
                'company_id' => (string) ($r->company_id ?? ''),
                'legacy_store_no' => $hasLegacyStoreNo ? (string) ($r->legacy_store_no ?? '') : '',
                'company_code' => $hasCompanyCode ? (string) ($r->company_code ?? '') : '',
                'company_name' => (string) ($r->company_name ?? ''),
                'company_name_kana' => $hasCompanyNameKana ? (string) ($r->company_name_kana ?? '') : '',
                'company_address' => $hasCompanyAddress ? (string) ($r->company_address ?? '') : '',
                'office_number' => $hasOfficeNumber ? (string) ($r->office_number ?? '') : '',
                'phone' => $hasPhone ? (string) ($r->phone ?? '') : '',
                'fax' => $hasFax ? (string) ($r->fax ?? '') : '',
            ])
            ->all();

        return view('admin.master.company.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'mx_companies',
            'editable' => [
                'company_code' => $hasCompanyCode,
                'company_name_kana' => $hasCompanyNameKana,
                'company_address' => $hasCompanyAddress,
                'office_number' => $hasOfficeNumber,
                'phone' => $hasPhone,
                'fax' => $hasFax,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $hasCompanyCode = $this->hasColumn('mx_companies', 'company_code');
        $hasCompanyNameKana = $this->hasColumn('mx_companies', 'company_name_kana');
        $hasCompanyAddress = $this->hasColumn('mx_companies', 'company_address');
        $hasOfficeNumber = $this->hasColumn('mx_companies', 'office_number');
        $hasPhone = $this->hasColumn('mx_companies', 'phone');
        $hasFax = $this->hasColumn('mx_companies', 'fax');

        $rules = [
            'company_id' => ['required', 'string', 'max:50'],
            'company_name' => ['required', 'string', 'max:200'],
            'q' => ['nullable', 'string', 'max:200'],
        ];
        if ($hasCompanyCode) { $rules['company_code'] = ['nullable', 'string', 'max:50']; }
        if ($hasCompanyNameKana) { $rules['company_name_kana'] = ['nullable', 'string', 'max:200']; }
        if ($hasCompanyAddress) { $rules['company_address'] = ['nullable', 'string', 'max:255']; }
        if ($hasOfficeNumber) { $rules['office_number'] = ['nullable', 'string', 'max:100']; }
        if ($hasPhone) { $rules['phone'] = ['nullable', 'string', 'max:100']; }
        if ($hasFax) { $rules['fax'] = ['nullable', 'string', 'max:100']; }

        $v = $request->validate($rules);
        $companyId = trim((string) ($v['company_id'] ?? ''));
        $updates = [
            'company_name' => trim((string) ($v['company_name'] ?? '')),
        ];
        if ($hasCompanyCode) { $updates['company_code'] = trim((string) ($v['company_code'] ?? '')); }
        if ($hasCompanyNameKana) { $updates['company_name_kana'] = trim((string) ($v['company_name_kana'] ?? '')); }
        if ($hasCompanyAddress) { $updates['company_address'] = trim((string) ($v['company_address'] ?? '')); }
        if ($hasOfficeNumber) { $updates['office_number'] = trim((string) ($v['office_number'] ?? '')); }
        if ($hasPhone) { $updates['phone'] = trim((string) ($v['phone'] ?? '')); }
        if ($hasFax) { $updates['fax'] = trim((string) ($v['fax'] ?? '')); }

        DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->whereRaw('LTRIM(RTRIM(CAST(company_id AS nvarchar(255)))) = ?', [$companyId])
            ->update($updates);

        return redirect()
            ->route('admin.master.company', ['q' => (string) ($v['q'] ?? '')])
            ->with('status', 'Company row updated.');
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv')->hasColumn($table, $column)
                || Schema::connection('sqlsrv')->hasColumn('dbo.' . $table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}

