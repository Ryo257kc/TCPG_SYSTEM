<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;

class StoreV2Service
{
    public function list(string $keyword): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_stores as st')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                'st.store_code',
                'st.store_name',
                'st.business_type',
                'st.visit_area',
                'st.company_id',
                'c.company_name',
                'st.store_short_name',
                'st.postal_code',
                'st.address_kana',
                'st.store_address',
                'st.category',
                'st.phone',
                'st.is_closed',
                'st.freee_department_name',
            ])
            ->orderBy('st.is_closed')
            ->orderBy('st.store_code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('st.store_code', 'like', '%' . $keyword . '%')
                    ->orWhere('st.store_name', 'like', '%' . $keyword . '%')
                    ->orWhere('c.company_name', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()->map(fn ($r) => [
            'store_code' => trim((string) ($r->store_code ?? '')),
            'store_name' => trim((string) ($r->store_name ?? '')),
            'business_type' => trim((string) ($r->business_type ?? '')),
            'visit_area' => trim((string) ($r->visit_area ?? '')),
            'company_id' => trim((string) ($r->company_id ?? '')),
            'company_name' => trim((string) ($r->company_name ?? '')),
            'store_short_name' => trim((string) ($r->store_short_name ?? '')),
            'postal_code' => trim((string) ($r->postal_code ?? '')),
            'address_kana' => trim((string) ($r->address_kana ?? '')),
            'store_address' => trim((string) ($r->store_address ?? '')),
            'category' => trim((string) ($r->category ?? '')),
            'phone' => trim((string) ($r->phone ?? '')),
            'is_closed' => (int) ($r->is_closed ?? 0),
            'freee_department_name' => trim((string) ($r->freee_department_name ?? '')),
        ])->all();

        $companyOptions = DB::connection('sqlsrv')->table('dbo.mx_companies')
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

        return ['rows' => $rows, 'companyOptions' => $companyOptions, 'departmentCandidatesByStoreCode' => $this->departmentCandidatesByStoreCode()];
    }

    /**
     * 店舗コード(mx_stores.store_code)ごとに、mx_departments.official_store_noが一致する
     * 部門名の候補一覧を返す。1つの店舗コードに複数の部門が対応するケース（往診系スタッフが
     * 個人ごとに別部門を持つ店舗等、2026-09-22実データで確認）があり、自動で1つに決められない
     * ため、freee_department_name欄の入力補助として候補を見せるだけに使う。
     *
     * @return array<string, list<string>>
     */
    private function departmentCandidatesByStoreCode(): array
    {
        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->whereNotNull('official_store_no')
            ->whereRaw("LTRIM(RTRIM(official_store_no)) <> ''")
            ->select(['official_store_no', 'store_category'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $storeCode = trim((string) ($row->official_store_no ?? ''));
            $category = trim((string) ($row->store_category ?? ''));
            if ($storeCode === '' || $category === '') {
                continue;
            }
            $map[$storeCode][] = $category;
        }

        return $map;
    }

    public function update(array $validated): int
    {
        $companyId = trim((string) ($validated['company_id'] ?? ''));

        return DB::connection('sqlsrv')->table('dbo.mx_stores')
            ->whereRaw('LTRIM(RTRIM(store_code)) = ?', [trim((string) ($validated['store_code'] ?? ''))])
            ->update([
                'store_name' => trim((string) ($validated['store_name'] ?? '')),
                'business_type' => trim((string) ($validated['business_type'] ?? '')),
                'visit_area' => trim((string) ($validated['visit_area'] ?? '')),
                'company_id' => $companyId === '' ? null : $companyId,
                'store_short_name' => trim((string) ($validated['store_short_name'] ?? '')),
                'postal_code' => trim((string) ($validated['postal_code'] ?? '')),
                'address_kana' => trim((string) ($validated['address_kana'] ?? '')),
                'store_address' => trim((string) ($validated['store_address'] ?? '')),
                'category' => trim((string) ($validated['category'] ?? '')),
                'phone' => trim((string) ($validated['phone'] ?? '')),
                'is_closed' => ((string) ($validated['is_closed'] ?? '0')) === '1' ? 1 : 0,
                'freee_department_name' => trim((string) ($validated['freee_department_name'] ?? '')),
            ]);
    }
}
