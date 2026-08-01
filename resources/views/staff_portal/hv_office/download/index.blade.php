<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - ダウンロード</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        /* .download-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .download-toolbar label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .download-toolbar input,
        .download-toolbar select {
            min-height: 30px;
        } */
    </style>
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">ダウンロード</h2>
            </div>

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="get" action="{{ route('hv_office.download') }}" class="toolbar">
                <label>
                    月度
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>
                <select name="daily_report_store_name">
                    <option value="">店舗選択</option>
                    @foreach($daily_report_store_name_options as $option)
                    <option value="{{ $option }}" {{ $daily_report_store_name === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="csv_type">
                    <option value="acupuncture" {{ $csv_type === 'acupuncture' ? 'selected' : '' }}>鍼灸レセCSV</option>
                    <option value="massage" {{ $csv_type === 'massage' ? 'selected' : '' }}>マッサージレセCSV</option>
                </select>
                <button type="submit" class="btn">表示</button>
                @if($csv_type === 'massage' || $daily_report_store_name !== '')
                <a class="btn" href="{{ route('hv_office.download.csv', ['target_month' => $target_month, 'daily_report_store_name' => $daily_report_store_name, 'csv_type' => $csv_type]) }}">CSVダウンロード</a>
                @endif
            </form>

            @if($csv_type === 'acupuncture' && $daily_report_store_name === '')
            <div class="empty">店舗を選択してください。</div>
            @elseif($rows->count() === 0)
            <div class="empty">「{{ $target_month }}」のデータがありません。</div>
            @else
            <div class="table-wrap staff-viewport-list-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>患者名</th>
                            <th>施設名</th>
                            <th>申請日</th>
                            <th>施術分類</th>
                            <th>施術</th>
                            <th>担当</th>
                            <th>店舗</th>
                            <th>施術期間自</th>
                            <th>施術期間至</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php
                        $addedAt = $row->added_at ? \Carbon\Carbon::parse($row->added_at) : null;
                        $receipt_home_visit_distance = $csv_type === 'massage'
                        ? trim((string) ($row->receipt_home_visit_distance_ma ?? ''))
                        : trim((string) ($row->receipt_home_visit_distance ?? ''));
                        $treatmentName = match ($receipt_home_visit_distance) {
                        '〇' => '通所',
                        '◎' => '通所(往療)',
                        '①' => '施術1',
                        '②' => '施術2',
                        '③' => '施術3',
                        '④' => '施術4',
                        default => '',
                        };
                        @endphp
                        <tr>
                            <td>{{ $row->common_id }}</td>
                            <td>{{ $row->patient_name }}</td>
                            <td>{{ $row->facility_name }}</td>
                            <td>{{ $addedAt ? $addedAt->format('Y/m/d') : '' }}</td>
                            <td>{{ $receipt_home_visit_distance }}</td>
                            <td>{{ $treatmentName }}</td>
                            <td>{{ $row->daily_report_billing_staff_display_name_ja ?: $row->daily_report_billing_staff_staff_name }}</td>
                            <td>{{ $row->daily_report_store_name }}</td>
                            <td>{{ $addedAt ? $addedAt->copy()->startOfMonth()->format('Y/m/d') : '' }}</td>
                            <td>{{ $addedAt ? $addedAt->copy()->endOfMonth()->format('Y-m-d') : '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>
