<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - その他一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .other-list-filter {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-start;
        }

        .other-list-filter fieldset {
            align-items: center;
            display: inline-flex;
            gap: 10px;
            margin: 0;
            width: auto;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">その他一覧</h2>
            </div>

            <form method="get" action="{{ route('office.store_daily_report.other_list') }}" class="filter-row other-list-filter">
                <label>
                    日付
                    <input type="month" name="target_month" value="{{ $targetMonth }}">
                </label>


                <fieldset>
                    <legend>フィルタ</legend>
                    @foreach (['' => '全て', 'hidden' => '非表示', 'visible' => '表示'] as $value => $label)
                    <label>
                        <input type="radio" name="visibility_filter" value="{{ $value }}" @checked(($visibilityFilter ?? '' )===$value)>
                        {{ $label }}
                    </label>
                    @endforeach
                </fieldset>

                <button type="submit" class="btn">表示</button>
                <a class="btn" target="_blank" rel="noopener" href="{{ route('office.store_daily_report.other_list.print', ['target_month' => $targetMonth, 'レジ店舗' => $selectedStore]) }}">印刷</a>
            </form>

            <div class="table-wrap">
                <table class="data-table f_size14">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>項目</th>
                            <th>品名</th>
                            <th>レジ店舗</th>
                            <th>金額</th>
                            <th>表示金額</th>
                            <th>非表示</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($otherListRows ?? []) as $row)
                        <tr>
                            <td>{{ $row['日付'] }}</td>
                            <td>{{ $row['項目'] }}</td>
                            <td>{{ $row['品名'] }}</td>
                            <td>{{ $row['レジ店舗'] }}</td>
                            <td class="text-right">{{ $row['金額'] }}</td>
                            <td class="text-right">{{ $row['金額1'] }}</td>
                            <td class="text-center">{{ $row['非表示ch'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">対象データがありません。</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('office.store_daily_report') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
