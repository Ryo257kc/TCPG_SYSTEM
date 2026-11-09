<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>返戻・回収不可 確認シート</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eee; color: #111; font-family: "Yu Mincho", "Meiryo", serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .toolbar { padding: 12px; text-align: center; font: 13px sans-serif; }
        .sheet { width: 277mm; margin: 0 auto 15px; padding: 8mm; background: #fff; }
        h1 { margin: 0 0 14px; font-size: 20px; }
        .meta { display: flex; flex-wrap: wrap; gap: 16px; margin: 6px 0; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10px; line-height: 1.3; }
        th, td { border-bottom: 1px solid #ccc; padding: 3px; overflow-wrap: anywhere; }
        th { font-weight: 400; text-align: left; }
        tbody tr:nth-child(even) { background: #eee; }
        .number { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .total { font-weight: 700; border-top: 2px solid #777; background: #fff !important; }
        .stamp { margin-top: 12px; font-size: 10px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; margin: 0; padding: 0; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">印刷・PDF保存</button> 印刷画面の保存先で「PDFに保存」を選べます。</div>
    <main class="sheet">
        <h1>返戻・回収不可</h1>
        <div class="meta">
            <span>決算月：{{ str_replace('-', '/', $paymentMonth) }}</span>
            <span>処理状況：{{ ['unprocessed' => '未処理', 'processed' => '処理済み'][$returnStatus] }}</span>
            <span>会社：{{ collect($companyOptions)->firstWhere('company_id', $selectedCompanyId)['company_name'] ?? 'すべて' }}</span>
            <span>店舗：{{ $selectedStoreCategory !== '' ? $selectedStoreCategory : 'すべて' }}</span>
            <span>{{ count($uncollectedRows) }}件</span>
        </div>
        <table>
            <colgroup>
                <col style="width:8%"><col style="width:8%"><col style="width:7%"><col style="width:21%"><col style="width:4%">
                <col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:9%"><col style="width:9%"><col style="width:10%">
            </colgroup>
            <thead><tr><th>施術月</th><th>保険者番号</th><th>保険区分</th><th>保険者名称</th><th class="center">回収<br>不可</th><th class="number">請求金額</th><th class="number">修正額</th><th class="number">返戻額</th><th>処理日</th><th>店舗</th><th>社名</th></tr></thead>
            <tbody>
                @forelse ($uncollectedRows as $row)
                <tr>
                    <td>{{ $row['treatment_month'] }}</td><td>{{ $row['insurer_number'] }}</td><td>{{ $row['insurance_type'] }}</td><td>{{ $row['insurer_name'] }}</td>
                    <td class="center">{{ $row['is_uncollectible'] ? '☑' : '□' }}</td>
                    <td class="number">{{ $row['claim_amount'] }}</td><td class="number">{{ $row['adjustment_amount'] }}</td><td class="number">{{ $row['returned_amount'] }}</td>
                    <td>{{ $row['returned_journal_processed_at'] }}</td><td>{{ $row['store_category'] }}</td><td>{{ str_replace('株式会社', '㈱', $row['company_name']) }}</td>
                </tr>
                @empty
                <tr><td colspan="11">対象データがありません。</td></tr>
                @endforelse
                <tr class="total"><td colspan="5">合計</td><td class="number">{{ $returnTotals['claim_amount'] }}</td><td class="number">{{ $returnTotals['adjustment_amount'] }}</td><td class="number">{{ $returnTotals['returned_amount'] }}</td><td colspan="3"></td></tr>
            </tbody>
        </table>
        <div class="stamp">出力日時：{{ now()->format('Y/m/d H:i') }}</div>
    </main>
</body>
</html>
