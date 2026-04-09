<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 売上</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/sales_item.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')

<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 売上</div>
    </div>

    <section class="panel sales-panel">
        <form method="get" action="{{ url('/admin/sales') }}" class="sales-filter-row">
            <div class="sales-field">
                <label for="sales-target-month">対象月</label>
                <input id="sales-target-month" name="target_month" type="month" value="{{ $targetMonth ?? now()->format('Y-m') }}">
            </div>

            <div class="sales-field sales-company-field">
                <label for="sales-company">会社</label>
                <select id="sales-company" name="company_id">
                    <option value="">会社を選択</option>
                    @foreach (($companyOptions ?? []) as $company)
                        <option value="{{ $company['company_id'] }}" @selected(($selectedCompanyId ?? '') === $company['company_id'])>{{ $company['company_name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sales-actions">
                <button type="submit" class="btn btn-primary">表示</button>
                <a
                    href="{{ url('/admin/sales/pdf') . '?' . http_build_query(['target_month' => ($targetMonth ?? now()->format('Y-m')), 'company_id' => ($selectedCompanyId ?? '')]) }}"
                    class="btn"
                    target="_blank"
                    rel="noopener noreferrer"
                >PDF</a>
            </div>
        </form>

        @include('shared.sales.sales_table_item', [
            'salesRows' => $salesRows ?? [],
            'grandTotal' => $grandTotal ?? 0,
        ])
    </section>
</div>
</body>
</html>
