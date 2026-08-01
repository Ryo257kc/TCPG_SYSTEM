<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/payslip_item.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel">
            <form method="get" class="filter-row payroll-filter-row">
                <label for="month">日付:</label>
                <select id="month" name="month">
                    @forelse (($availableMonths ?? []) as $month)
                    <option value="{{ $month }}" @selected($selectedMonth===$month)>
                        {{ \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->format('Y年n月') }}
                    </option>
                    @empty
                    <option value="{{ $selectedMonth }}">{{ $selectedMonth }}</option>
                    @endforelse
                </select>
                <button type="submit">表示</button>
                <button type="button" onclick="window.print()">印刷</button>
            </form>

            @include('shared.payroll.payslip_item', [
            'rawRows' => $rawRows,
            'targetStaffName' => $targetStaffName,
            'storeName' => $storeName,
            'companyName' => $companyName,
            'targetTaxCategory' => $targetTaxCategory ?? '',
            'isBonus' => $isBonus ?? false,
            ])
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
