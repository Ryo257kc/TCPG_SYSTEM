<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 売上</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/data_table.css') }}">
    <style>
        .sales-filter-row {
            display: grid;
            grid-template-columns: minmax(170px, 220px) minmax(240px, 360px) auto;
            gap: 10px;
            align-items: end;
        }

        .sales-field {
            display: grid;
            gap: 6px;
        }

        .sales-field label {
            font-size: 12px;
            color: #5b6474;
            font-weight: 600;
        }

        .sales-field input,
        .sales-field select {
            height: 40px;
            padding: 0 12px;
            border: 1px solid #cfd7e3;
            border-radius: 8px;
            background: #fff;
            color: #1f2937;
            font-size: 13px;
            box-sizing: border-box;
        }

        .sales-company-field {
            min-width: 0;
        }

        .sales-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .sales-actions .btn {
            min-width: 92px;
        }

        .sales-table-card {
            display: grid;
            gap: 10px;
        }

        .sales-table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sales-head-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sales-grand-total {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1;
        }

        .sales-table-card .meta-count,
        .sales-table-card .sales-grand-total,
        .sales-table-card .num {
            text-align: right;
        }

        @media (max-width: 840px) {
            .sales-filter-row {
                grid-template-columns: 1fr;
            }

            .sales-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    @include('admin_v2.shared.global_nav')

    <div class="wrap">
        <div class="top">
            <div class="title">TCPG SYSTEM 売上</div>
        </div>

        <section class="panel admin-viewport-panel">
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
                    <button type="submit" class="btn">表示</button>
                    <a
                        href="{{ url('/admin/sales/pdf') . '?' . http_build_query(['target_month' => ($targetMonth ?? now()->format('Y-m')), 'company_id' => ($selectedCompanyId ?? '')]) }}"
                        class="btn"
                        target="_blank"
                        rel="noopener noreferrer">印刷</a>
                    <a
                        href="{{ url('/admin/sales/csv') . '?' . http_build_query(['target_month' => ($targetMonth ?? now()->format('Y-m')), 'company_id' => ($selectedCompanyId ?? '')]) }}"
                        class="btn">CSV DL</a>
                </div>
            </form>

            @include('shared.sales.sales_table_item', [
            'salesRows' => $salesRows ?? [],
            'companyTotals' => $companyTotals ?? [],
            'grandTotal' => $grandTotal ?? 0,
            ])
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </div>
</body>

</html>