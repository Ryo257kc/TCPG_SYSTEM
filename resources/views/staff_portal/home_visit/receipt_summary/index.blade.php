<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 領収一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .receipt-summary-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .receipt-summary-toolbar label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .receipt-summary-toolbar input,
        .receipt-summary-toolbar select {
            min-height: 30px;
        }

        .w-keyword {
            flex: 1 1 180px;
        }

        .w-filter {
            flex: 1 1 120px;
        }

        .receipt-summary-message {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 20px 0;
            padding: 14px;
            border: 1px solid #e3c8a8;
            border-radius: 8px;
            background: #fff7ed;
        }

        .receipt-summary-actions {
            display: flex;
            justify-content: flex-end;
            margin: 0;
            flex: 0 0 auto;
        }
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel sales-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">領収金額 集計</h2>
            </div>

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($existingCount > 0)
            <div class="receipt-summary-message">「{{ $target_month }}」は既に集計されています。</div>
            @elseif($rows->count() === 0)
            <div class="receipt-summary-message">「{{ $target_month }}」のデータがありません。</div>
            @else
            <div class="receipt-summary-message">
                下記の内容で集計してよければ、「確定」ボタンを押してください。
                <div class="receipt-summary-actions">
                    <form method="post" action="{{ route('home_visit.receipt_summary.confirm') }}" onsubmit="return confirm('この内容で確定しますか？');">
                        @csrf
                        <input type="hidden" name="target_month" value="{{ $target_month }}">
                        <input type="hidden" name="summary_type" value="{{ $summary_type }}">
                        <button type="submit" class="btn">確定</button>
                    </form>
                </div>
            </div>

            <form method="get" action="{{ route('home_visit.receipt_summary') }}" class="receipt-summary-toolbar">
                <label>
                    月度
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>
                <label>
                    内容選択
                    <select name="summary_type">
                        <option value="保険" {{ $summary_type === '保険' ? 'selected' : '' }}>保険</option>
                        <option value="自費" {{ $summary_type === '自費' ? 'selected' : '' }}>自費</option>
                    </select>
                </label>
                <input class="w-keyword" type="text" name="patient_name" value="{{ $patient_name }}" placeholder="患者名で検索">
                <select class="w-filter" name="daily_report_store_name">
                    <option value="">店舗</option>
                    @foreach($daily_report_store_name_options as $option)
                    <option value="{{ $option }}" {{ $daily_report_store_name === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <select class="w-filter" name="daily_report_billing_staff">
                    <option value="">担当</option>
                    @foreach($daily_report_billing_staff_options as $option)
                    <option value="{{ $option['staff_id'] }}" {{ (string) $daily_report_billing_staff === (string) $option['staff_id'] ? 'selected' : '' }}>{{ $option['display_name_ja'] !== '' ? $option['display_name_ja'] : $option['staff_name'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn">表示</button>
                <a href="{{ route('home_visit.receipt_summary') }}" class="btn">クリア</a>
            </form>
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 50px;">
                        <col style="width: 60px;">
                        <col style="width: 30px;">
                        <col style="width: 30px;">
                        <col style="width: 30px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                        <col style="width: 60px;">
                        <col style="width: 40px;">
                        <col style="width: 40px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>月度</th>
                            <th>患者名</th>
                            <th>距離</th>
                            <th>割合</th>
                            <th>助成<br>上限回数</th>
                            <th>領収合計</th>
                            <th>集金額</th>
                            <th>往診町名</th>
                            <th>担当</th>
                            <th>往診店舗</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php
                        $receipt_total = $summary_type === '保険'
                        ? (float) ($row->copayment_amount_total ?? 0)
                        : (float) ($row->private_fee_total ?? 0);
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->target_month)->format('Y-n') }}</td>
                            <td>{{ $row->patient_name }}</td>
                            <td>{{ (float) ($row->standard_distance ?? 0) == 0.0 ? '' : rtrim(rtrim(number_format((float) $row->standard_distance, 1, '.', ''), '0'), '.') }}</td>
                            <td>{{ $row->burden_ratio }}</td>
                            <td>{{ number_format((float) ($row->subsidy_limit_count ?? 0)) }}</td>
                            <td>{{ number_format($receipt_total) }}</td>
                            <td>{{ number_format($receipt_total) }}</td>
                            <td>{{ $row->daily_report_town_name }}</td>
                            <td>{{ $row->daily_report_billing_staff_display_name_ja ?: $row->daily_report_billing_staff }}</td>
                            <td>{{ $row->daily_report_store_name }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="home-visit-counter-footer margin_t20">
                <button type="button" class="btn_back" onclick="history.back()">戻る</button>
            </div>
        </section>
    </main>
</body>

</html>
