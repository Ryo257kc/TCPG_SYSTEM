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

        $hasLegacyStoreNo = $this->hasColumn('m_companies', 'legacy_store_no');
        $hasCompanyCode = $this->hasColumn('m_companies', 'company_code');
        $hasCompanyNameKana = $this->hasColumn('m_companies', 'company_name_kana');
        $hasCompanyAddress = $this->hasColumn('m_companies', 'company_address');
        $hasOfficeNumber = $this->hasColumn('m_companies', 'office_number');
        $hasPhone = $this->hasColumn('m_companies', 'phone');
        $hasFax = $this->hasColumn('m_companies', 'fax');

        $selects = ['company_id', 'company_name'];
        if ($hasLegacyStoreNo) { $selects[] = 'legacy_store_no'; }
        if ($hasCompanyCode) { $selects[] = 'company_code'; }
        if ($hasCompanyNameKana) { $selects[] = 'company_name_kana'; }
        if ($hasCompanyAddress) { $selects[] = 'company_address'; }
        if ($hasOfficeNumber) { $selects[] = 'office_number'; }
        if ($hasPhone) { $selects[] = 'phone'; }
        if ($hasFax) { $selects[] = 'fax'; }

        $query = DB::connection('sqlsrv')
            ->table('dbo.m_companies')
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
            'source' => 'm_companies',
        ]);
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