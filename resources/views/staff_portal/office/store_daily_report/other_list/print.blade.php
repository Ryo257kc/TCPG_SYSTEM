<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>その他一覧 印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        .print-table th {
            background: #f7f7f7;
            font-weight: 700;
            text-align: center;
        }

        .print-footer-total td {
            border-top: 2px solid #555;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <main class="print-page">
        <div class="print-title">
            <span>{{ $selectedStore !== '' ? $selectedStore : '全店舗' }}</span>
            <span>その他一覧</span>
        </div>

        <div class="print-meta">{{ $targetMonth }}</div>

        <table class="print-table">
            <colgroup>
                <col style="width: 60px;">
                <col style="width: 60px;">
                <col style="width: 200px;">
                <col style="width: 60px;">
            </colgroup>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>項目</th>
                    <th>品名</th>
                    <th>金額</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($printRows ?? []) as $row)
                <tr>
                    <td>{{ $row['日付'] }}</td>
                    <td>{{ $row['項目'] }}</td>
                    <td>{{ $row['品名'] }}</td>
                    <td class="right">{{ $row['金額'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">対象データがありません。</td>
                </tr>
                @endforelse
                <tr class="print-footer-total">
                    <td colspan="3" class="text-right">総合計</td>
                    <td class="text-right">{{ number_format((float) ($totalAmount ?? 0)) }}</td>
                </tr>
            </tbody>
        </table>
    </main>
</body>

</html>
