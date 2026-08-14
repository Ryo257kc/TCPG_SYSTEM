<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 先生別日報</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">先生別日報</h2>
            </div>

            <form method="get" class="filter-row">
                <label for="date">日付</label>
                <input id="date" type="date" name="date" value="{{ $selectedDate }}">
                <!-- <span>{{ $selectedDateLabel ?? '' }}</span> -->
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

            @if ($hasSearched && $selectedStaffId !== '' && $rowCount === 0)
            <div class="empty">「{{ $selectedDate }}」の売上はありません。</div>
            @elseif ($selectedStaffId !== '')
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>時刻</th>
                            <th>患者名</th>
                            <th>保険</th>
                            <th>自費</th>
                            <th>店舗</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['at_time'] }}</td>
                            <td>{{ $row['patient_name'] }}</td>
                            <td class="num">{{ number_format($row['insurance_amount']) }}</td>
                            <td class="num">{{ number_format($row['private_amount']) }}</td>
                            <td>{{ $row['store_name'] }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3"><b>合計</b></td>
                            <td class="num"><b>{{ number_format($totals['insurance']) }}</b></td>
                            <td class="num"><b>{{ number_format($totals['private']) }}</b></td>
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
