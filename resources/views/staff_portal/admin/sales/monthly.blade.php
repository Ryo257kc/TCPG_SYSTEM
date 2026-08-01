<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 先生別月報</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">先生別月報</h2>
            </div>

            <form method="get" class="filter-row">
                <label for="month">対象月</label>
                <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                <label for="staff_id">名前</label>
                <select id="staff_id" name="staff_id">
                    <option value="" @selected($selectedStaffId==='' )></option>
                    @foreach ($staffOptions as $staff)
                    <option value="{{ $staff['staff_id'] }}" @selected($staff['staff_id']===$selectedStaffId)>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
                    @endforeach
                </select>
                <button type="submit">表示</button>
            </form>

            @if ($hasSearched && $selectedStaffName !== '')
            <div class="meta">名前：{{ $selectedStaffName }}</div>
            @endif

            @if ($errorMessage !== '')
            <div class="status">{{ $errorMessage }}</div>
            @endif

            @if ($selectedStaffId !== '' && $rowCount === 0)
            <div class="empty">「{{ $selectedMonth }}」の売上はありません。</div>
            @elseif ($selectedStaffId !== '')
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>保険人数</th>
                            <th>保険合計</th>
                            <th>自費人数</th>
                            <th>自費合計</th>
                            <th>店舗</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['date_label'] }}</td>
                            <td>{{ number_format($row['insurance_count']) }}</td>
                            <td class="num">{{ number_format($row['insurance_total']) }}</td>
                            <td>{{ number_format($row['private_count']) }}</td>
                            <td class="num">{{ number_format($row['private_total']) }}</td>
                            <td>{{ $row['store_name'] }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td><b>合計</b></td>
                            <td><b>{{ number_format($totals['insurance_count']) }}</b></td>
                            <td class="num"><b>{{ number_format($totals['insurance_total']) }}</b></td>
                            <td><b>{{ number_format($totals['private_count']) }}</b></td>
                            <td class="num"><b>{{ number_format($totals['private_total']) }}</b></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h2 style="text-align:right; margin-top:12px;">合計：{{ number_format($totals['grand']) }}円</h2>
            @endif

            <div>
                <a href="{{ route('store.sales') }}" class="btn btn_back">戻る</a>
            </div>
        </section>


    </main>
</body>

</html>
