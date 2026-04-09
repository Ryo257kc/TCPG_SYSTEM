<?php

$root = dirname(__DIR__);

$adminIndex = <<<'BLADE'
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 売上</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/sales/sales_item.css') }}">
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
BLADE;

$adminPrint = <<<'BLADE'
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 売上一覧</title>
    <link rel="stylesheet" href="{{ asset('css/shared/sales/sales_print_item.css') }}">
</head>
<body>
    @include('shared.sales.sales_print_item', [
        'stores' => $stores ?? [],
        'targetMonth' => $targetMonth ?? '',
        'grandTotal' => $grandTotal ?? 0,
        'companyName' => $companyName ?? '',
    ])
</body>
</html>
BLADE;

$officeIndex = <<<'BLADE'
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 売上関連</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/shared/sales/sales_item.css') }}">
</head>
<body>
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel sales-panel">
        <div class="content-head">
            <h1 class="content-title">売上関連</h1>
        </div>

        <form method="get" action="{{ route('office.sales') }}" class="sales-filter-row">
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
                    href="{{ route('office.sales.print', ['target_month' => ($targetMonth ?? now()->format('Y-m')), 'company_id' => ($selectedCompanyId ?? '')]) }}"
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
</main>
</body>
</html>
BLADE;

file_put_contents($root . '/resources/views/admin_v2/sales/index.blade.php', $adminIndex);
file_put_contents($root . '/resources/views/admin_v2/sales/print.blade.php', $adminPrint);
file_put_contents($root . '/resources/views/staff_portal/office/sales/index.blade.php', $officeIndex);
