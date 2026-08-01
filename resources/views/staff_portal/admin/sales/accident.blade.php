<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 交通事故</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">交通事故</h2>
            </div>
            <form method="get" class="filter-row">
                <label for="month">対象月</label>
                <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                <button type="submit">表示</button>
                <a class="btn" href="{{ route('admin.sales.accident.create', ['month' => $selectedMonth]) }}">新規登録</a>
            </form>

            @if ($statusMessage !== '')
            <div class="status">{{ $statusMessage }}</div>
            @endif
            @if ($errorMessage !== '')
            <div class="status">{{ $errorMessage }}</div>
            @endif

            @if ($rowCount === 0)
            <div class="empty">「{{ $selectedMonth }}」の交通事故はありません。</div>
            @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>月度</th>
                            <th>患者名</th>
                            <th>保険会社</th>
                            <th>金額</th>
                            <th>通知書</th>
                            <th>入金日</th>
                            <th>振込先</th>
                            <th>店舗</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['treatment_month'] }}</td>
                            <td>{{ $row['patient_name'] }}</td>
                            <td>{{ $row['insurer_name'] }}</td>
                            <td class="num">{{ number_format($row['amount']) }}</td>
                            <td>{{ $row['notice_date'] }}</td>
                            <td>{{ $row['paid_date'] }}</td>
                            <td>{{ $row['bank_to'] }}</td>
                            <td>{{ $row['store_name'] }}</td>
                            <td><a class="btn" href="{{ route('admin.sales.accident.edit', ['henreiNo' => $row['henrei_no'], 'month' => $selectedMonth]) }}">編集</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="back-row">
                <div>
                    <a href="{{ route('admin.sales') }}" class="btn btn_back">戻る</a>
                </div>
            </div>
        </section>


    </main>
</body>

</html>
