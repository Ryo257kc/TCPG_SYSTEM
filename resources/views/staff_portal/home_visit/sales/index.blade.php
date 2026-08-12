<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診売上</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .home-visit-sales-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .home-visit-sales-toolbar label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .home-visit-sales-toolbar input,
        .home-visit-sales-toolbar select {
            min-height: 30px;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel sales-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">往診売上</h2>
            </div>

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="get" action="{{ route('home_visit.sales') }}" class="home-visit-sales-toolbar">
                <label>
                    日付
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>
                <select name="staff_name">
                    <option value="">担当</option>
                    @foreach($staff_name_options as $option)
                    <option value="{{ $option['staff_id'] }}" {{ (string) $staff_name === (string) $option['staff_id'] ? 'selected' : '' }}>{{ $option['display_name_ja'] !== '' ? $option['display_name_ja'] : $option['staff_name'] }}</option>
                    @endforeach
                </select>
                <button type="submit" name="sort" value="monthly" class="btn">月毎</button>
                <button type="submit" name="sort" value="all" class="btn">全て</button>
                <button type="button" class="btn" disabled>月間印刷</button>
            </form>

            @if($rows->count() === 0)
            <div class="empty">「{{ $target_month }}」のデータがありません。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>施術日</th>
                            <th colspan="2">日報</th>
                            <th>売上合計</th>
                            <th>日報 確定日</th>
                            <th>担当</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php
                        $treatmentDate = $row->treatment_date ? \Carbon\Carbon::parse($row->treatment_date) : null;
                        $week = ['日', '月', '火', '水', '木', '金', '土'];
                        $is_management_fixed = (int) ($row->is_management_fixed ?? 0) === 1;
                        $confirmed_at = $is_management_fixed && $row->confirmed_at ? \Carbon\Carbon::parse($row->confirmed_at)->format('Y-m-d H:i:s') : '';
                        $sales_amount_total = $row->sales_amount_total;
                        $staffDisplayName = $row->staff_display_name_ja ?: $row->staff_master_name;
                        @endphp
                        <tr>
                            <td>{{ $treatmentDate ? $treatmentDate->format('Y-m-d') . '(' . $week[(int) $treatmentDate->format('w')] . ')' : '' }}</td>
                            <td>
                                @if($isAccounting)
                                @if($row->is_confirmed)
                                <a class="btn_small" href="{{ route('home_visit.daily_report.print', ['date' => $treatmentDate ? $treatmentDate->format('Y-m-d') : '', 'staff_name' => $row->staff_name]) }}" target="_blank" rel="noopener">プレビュー</a>
                                @else
                                未確認
                                @endif
                                @endif
                            </td>
                            <td>
                                @if($isAccounting)
                                @if($row->is_confirmed)
                                <a class="btn_small" href="{{ route('home_visit.daily_report', ['date' => $treatmentDate ? $treatmentDate->format('Y-m-d') : '', 'staff_name' => $row->staff_name]) }}">売上入力</a>
                                @else
                                未確認
                                @endif
                                @endif
                            </td>
                            <td>{{ $sales_amount_total === null ? '' : number_format((float) $sales_amount_total) }}</td>
                            <td>{{ $confirmed_at }}</td>
                            <td>{{ $staffDisplayName }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div>
                <button type="button" class="btn btn_back" onclick="history.back()">戻る</button>
            </div>
        </section>
    </main>
</body>

</html>
