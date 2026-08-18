<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 日報印刷</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        /* @page {
            size: A4 portrait;
            margin: 8mm;
        }

        body {
            margin: 0;
            color: #111;
            background: #e9e9e9;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
            font-size: 11px;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .print-actions {
            padding: 8px;
        }

        .print-page {
            width: 194mm;
            min-height: 281mm;
            margin: 0 auto 8px;
            padding: 8mm;
            background: #fff;
            box-sizing: border-box;
        } */

        .cover-page {
            display: flex;
            flex-direction: column;
            page-break-after: always;
        }

        .report-header {
            align-items: end;
            border-bottom: 3px solid #111;
            display: flex;
            justify-content: space-between;
            margin-bottom: 5mm;
            padding: 0 6mm 2mm;
        }

        .report-title {
            font-size: 26px;
            letter-spacing: 0.08em;
        }

        .report-date {
            font-size: 15px;
        }

        .cover-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9mm;
        }

        .cover-lines {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 3mm;
        }

        .cover-lines th,
        .cover-lines td {
            border-bottom: 1px solid #222;
            padding: 1.35mm 1.8mm;
            vertical-align: middle;
        }

        .cover-lines th {
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            white-space: nowrap;
        }

        .cover-lines td {
            font-size: 12px;
            text-align: right;
        }

        .cover-lines .section-head th,
        .cover-lines .section-head td {
            border-bottom: 2px solid #222;
            font-size: 14px;
            font-weight: 700;
        }

        .cover-lines .detail-row td {
            font-size: 11px;
            padding-left: 5mm;
        }

        .cover-bottom {
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 4mm;
            margin-top: auto;
        }

        .cover-note {
            border: 1px solid #222;
            height: 24mm;
            padding: 1.5mm;
            white-space: pre-wrap;
        }

        .cover-people {
            border: 1px solid #222;
            margin-top: 2mm;
            padding: 2mm 9mm;
        }

        .people-row {
            display: grid;
            grid-template-columns: 1fr 42px 18px;
            line-height: 1.35;
        }

        .cover-people .people-child {
            font-size: 12px;
            padding-left: 7mm;
            position: relative;
        }

        .cover-people .people-child::before {
            border-left: 2px solid #111;
            content: "";
            height: 1.55em;
            left: 3mm;
            position: absolute;
            top: 0;
        }

        .cover-people .people-note {
            font-size: 11px;
            text-align: center;
        }

        .cover-sales {
            border: 2px solid #222;
            padding: 4mm 7mm;
            text-align: center;
        }

        .cover-sales-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2mm;
            font-size: 15px;
            font-weight: 700;
        }

        .cover-sales-small {
            font-size: 13px;
            margin-bottom: 1.2mm;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #fff;
        }

        .print-table th,
        .print-table td {
            border: 0;
            border-bottom: 1px solid #222;
            padding: 2.5px 3px;
            text-align: center;
            vertical-align: middle;
            word-break: break-word;
        }

        .print-table th {
            background: #fff;
            border-bottom: 2px solid #222;
            font-weight: 400;
        }

        .print-table .border-left {
            border-left: 2px solid #222;
        }

        .daily-order-new {
            background-color: #9ed8ff !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .daily-order-new-like {
            background-color: #fff36a !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        .text-right {
            text-align: right !important;
        }

        .text-left {
            text-align: left !important;
        }

        .total-row th,
        .total-row td {
            font-weight: 700;
            background: #f7f7f7;
        }

        .line {
            line-height: 3em;
        }

        @media print {
            /* body {
                background: #fff;
            }

            .print-actions {
                display: none;
            }

            .print-page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            } */

            .cover-page {
                min-height: 271mm;
                display: flex;
                flex-direction: column;
            }

            .cover-bottom {
                margin-top: auto;
            }
        }
    </style>
</head>

<body>
    @php
    $moneyToInt = static fn($value): int => (int) str_replace(',', '', (string) $value);
    $formatYen = static fn($value): string => '￥ ' . number_format((int) $value);
    $isModifiedPrint = ($printMode ?? 'default') === 'modified';

    $printRowsCollection = collect($printRows ?? []);
    if ($isModifiedPrint) {
    $printRowsCollection = $printRowsCollection
    ->map(function ($row) {
    if ((int) ($row['ch'] ?? 0) !== 0) {
    $row['自費計'] = '0';
    $row['メニュー集計'] = '';
    }

    return $row;
    })
    ->values();
    }

    $privateTreatmentRowsCollection = collect($privateTreatmentRows ?? []);
    if ($isModifiedPrint) {
    $privateTreatmentRowsCollection = $privateTreatmentRowsCollection
    ->groupBy(fn($row) => trim((string) ($row['メニュー'] ?? '')))
    ->map(function ($rows) use ($moneyToInt) {
    $firstRow = $rows->first() ?? [];
    $selfPayTotal = $rows->sum(fn($row) => $moneyToInt($row['自費の合計'] ?? 0));
    $countTotal = $rows->sum(fn($row) => (int) str_replace(',', '', (string) ($row['メニューのカウント'] ?? 0)));

    return [
    'メニュー' => trim((string) ($firstRow['メニュー'] ?? '')),
    'メニューのカウント' => (string) $countTotal,
    '自費の合計' => number_format($selfPayTotal),
    'charge' => trim((string) ($firstRow['charge'] ?? '')),
    'raw_自費の合計' => $selfPayTotal,
    ];
    })
    ->values();
    }

    $chargeUseRows = collect($privateTreatmentRows ?? [])->filter(fn($row) => (int) ($row['charge'] ?? 0) === 1);
    $cashRegisterRowsCollection = collect($cashRegisterRows ?? []);
    $cashRegisterGroups = $cashRegisterRowsCollection->groupBy(fn($row) => trim((string) ($row['項目'] ?? '')));
    $privateTreatmentTotal = $privateTreatmentRowsCollection->sum(fn($row) => $moneyToInt($row['自費の合計'] ?? 0));
    $chargeDepositTotal = $cashRegisterRowsCollection
    ->filter(fn($row) => trim((string) ($row['項目'] ?? '')) === 'チャージ金')
    ->sum(fn($row) => $moneyToInt($row['金額の合計'] ?? 0));
    $visibleCashRegisterGroups = $isModifiedPrint
    ? $cashRegisterGroups->reject(fn($rows, $item) => trim((string) $item) === 'チャージ金')
    : $cashRegisterGroups;
    $chargeUseTotal = $chargeUseRows->sum(fn($row) => $moneyToInt($row['自費の合計'] ?? 0));
    $registerDeductionTotal = $cashRegisterRowsCollection
    ->filter(fn($row) => !in_array(trim((string) ($row['項目'] ?? '')), ['チャージ金', '物品販売'], true))
    ->sum(fn($row) => $moneyToInt($row['金額の合計'] ?? 0));

    $modifiedReportAmount = $printRowsCollection->sum(function ($row) use ($moneyToInt) {
    $insuranceAmount = $moneyToInt($row['保険負担計'] ?? 0);
    $receiptDifference = $moneyToInt($row['レセ差額'] ?? 0);
    $differenceAmount = $moneyToInt($row['差額'] ?? 0);

    return ($insuranceAmount + $receiptDifference) !== 0
    ? ($insuranceAmount - $differenceAmount + $receiptDifference)
    : 0;
    });

    $reportAmount = $isModifiedPrint
    ? $modifiedReportAmount
    : $moneyToInt($printTotals['保険負担計'] ?? 0);
    $registerAmount = $isModifiedPrint
    ? $modifiedReportAmount
    : $moneyToInt($dailySummary['レジ'] ?? 0);
    $counterTotal = $reportAmount + $privateTreatmentTotal;
    $counterDifference = $reportAmount - $registerAmount;
    $dailySalesAmount = $counterTotal - $registerDeductionTotal;
    $bankDepositAmount = $isModifiedPrint
    ? $dailySalesAmount - $moneyToInt($printTotals['カード計'] ?? 0)
    : $dailySalesAmount - $chargeUseTotal + $chargeDepositTotal;
    $bankDepositDifference = $bankDepositAmount - $moneyToInt($dailySummary['銀行預入金額'] ?? 0);

    $displayInsuranceAmount = static function (array $row) use ($moneyToInt, $isModifiedPrint): int {
    if (array_key_exists('_display_insurance_amount', $row)) {
    return (int) $row['_display_insurance_amount'];
    }

    if (!$isModifiedPrint) {
    return $moneyToInt($row['保険負担計'] ?? 0);
    }

    $insuranceAmount = $moneyToInt($row['保険負担計'] ?? 0);
    $receiptDifference = $moneyToInt($row['レセ差額'] ?? 0);
    $differenceAmount = $moneyToInt($row['差額'] ?? 0);

    return ($insuranceAmount + $receiptDifference) !== 0
    ? ($insuranceAmount - $differenceAmount + $receiptDifference)
    : 0;
    };

    $displayReceiptBurdenAmount = static function (array $row) use ($moneyToInt, $isModifiedPrint): int {
    if (array_key_exists('_display_receipt_burden_amount', $row)) {
    return (int) $row['_display_receipt_burden_amount'];
    }

    $receiptBurden = $moneyToInt($row['レセ負担金'] ?? 0);

    if (!$isModifiedPrint) {
    return $receiptBurden;
    }

    $receiptBurdenCheckedValue = trim((string) ($row['負担金ch'] ?? ''));
    $receiptBurdenChecked = !in_array(strtolower($receiptBurdenCheckedValue), ['', '0', 'false'], true);
    $receiptDifference = $moneyToInt($row['レセ差額'] ?? 0);

    if ($receiptBurdenChecked) {
    return 0;
    }

    return $receiptBurden + (($receiptBurden !== 0 || $receiptBurdenChecked) ? 0 : $receiptDifference);
    };

    $displayReceiptBurdenTotal = $printRowsCollection->sum(fn($row) => $displayReceiptBurdenAmount($row));
    $displayInsuranceTotal = $printRowsCollection->sum(fn($row) => $displayInsuranceAmount($row));
    $displayPrintRowsCollection = $printRowsCollection
    ->groupBy(fn($row) => trim((string) ($row['患者名'] ?? '')))
    ->map(function ($rows) use ($moneyToInt, $displayInsuranceAmount, $displayReceiptBurdenAmount) {
    $firstRow = $rows->first() ?? [];
    $menuLabels = $rows
    ->map(fn($row) => trim((string) ($row['メニュー集計'] ?? '')))
    ->filter(fn($value) => $value !== '')
    ->unique()
    ->values()
    ->all();
    $staffLabels = $rows
    ->map(fn($row) => trim((string) ($row['担当者'] ?? '')))
    ->filter(fn($value) => $value !== '')
    ->unique()
    ->values()
    ->all();

    $firstRow['メニュー集計'] = implode(' / ', $menuLabels);
    $firstRow['担当者'] = implode(' / ', $staffLabels);
    $firstRow['自費計'] = number_format($rows->sum(fn($row) => $moneyToInt($row['自費計'] ?? 0)));
    $firstRow['請求金額計'] = number_format($rows->sum(fn($row) => $moneyToInt($row['請求金額計'] ?? 0)));
    $firstRow['_display_insurance_amount'] = $rows->sum(fn($row) => $displayInsuranceAmount($row));
    $firstRow['_display_receipt_burden_amount'] = $rows->sum(fn($row) => $displayReceiptBurdenAmount($row));

    return $firstRow;
    })
    ->values();
    $displayVisitCount = $isModifiedPrint
    ? (string) (
    ((int) preg_replace('/\D/', '', (string) ($dailySummaryPeopleTotals['自費人数'] ?? '0')))
    + ((int) preg_replace('/\D/', '', (string) ($dailySummaryPeopleTotals['保険人数'] ?? '0')))
    + ((int) preg_replace('/\D/', '', (string) ($dailySummaryPeopleTotals['保険証忘れ'] ?? '0')))
    )
    : (string) ($dailySummaryPeopleTotals['来院人数'] ?? '');
    @endphp

    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>

    <section class="print-page cover-page">
        <div class="report-header">
            <div class="report-title">{{ $dailySummary['日報集計店舗'] }}　日報</div>
            <div class="report-date">{{ $dailySummary['日付'] }}</div>
        </div>

        <div class="cover-grid">
            <div>
                <table class="cover-lines">
                    <tbody>
                        <tr class="section-head">
                            <th>窓口合計</th>
                            <th>合計(自費含む)</th>
                            <td>{{ $formatYen($counterTotal) }}</td>
                        </tr>
                        <tr class="detail-row">
                            <td>{{ $formatYen($reportAmount) }}</td>
                            <td>日報</td>
                            <td></td>
                        </tr>
                        <tr class="detail-row">
                            <td>{{ $formatYen($registerAmount) }}</td>
                            <td>レジ</td>
                            <td>(誤差　{{ number_format($counterDifference) }})</td>
                        </tr>
                        <tr class="section-head">
                            <th>自費治療金</th>
                            <th>合計</th>
                            <td>{{ $formatYen($privateTreatmentTotal) }}</td>
                        </tr>
                        @foreach ($privateTreatmentRowsCollection as $row)
                        <tr class="detail-row">
                            <td>{{ $formatYen($moneyToInt($row['自費の合計'] ?? 0)) }}</td>
                            <td>{{ $row['メニューのカウント'] }}件</td>
                            <td>{{ $row['メニュー'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                @if ($visibleCashRegisterGroups->isNotEmpty())
                <table class="cover-lines">
                    <tbody>
                        @foreach ($visibleCashRegisterGroups as $item => $rows)
                        <tr class="section-head">
                            <th>{{ $item }}</th>
                            <th>合計</th>
                            <td>{{ $formatYen($rows->sum(fn($row) => $moneyToInt($row['金額の合計'] ?? 0))) }}</td>
                        </tr>
                        @foreach ($rows as $row)
                        <tr class="detail-row">
                            <td>{{ $formatYen($moneyToInt($row['金額の合計'] ?? 0)) }}</td>
                            <td colspan="2">{{ $row['品名'] }}</td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        <div class="cover-bottom">
            <div>
                <div class="cover-note">《備考》
                    {{ $dailySummary['備考'] }}
                </div>
                <div class="cover-people">
                    <div class="people-row">
                        <span>予約人数</span><span>{{ $dailySummaryPeopleTotals['予約人数'] }}</span><span>人</span>
                    </div>
                    <div class="people-row">
                        <span>来院人数</span><span>{{ $displayVisitCount }}</span><span>人</span>
                    </div>
                    <div class="people-row people-child">
                        <span>自費人数</span><span>{{ $dailySummaryPeopleTotals['自費人数'] }}</span><span>人</span>
                    </div>
                    <div class="people-row people-child">
                        <span>保険人数</span><span>{{ $dailySummaryPeopleTotals['保険人数'] }}</span><span>人</span>
                    </div>
                    <div class="people-row people-child">
                        <span>保険証忘れ</span><span>{{ $dailySummaryPeopleTotals['保険証忘れ'] }}</span><span>人</span>
                    </div>
                    <div class="people-row">
                        <span>交通事故</span><span>{{ $dailySummaryPeopleTotals['交通事故'] }}</span><span>人</span>
                    </div>
                    <div class="people-note">(自賠責　{{ $dailySummaryPeopleTotals['自賠責'] }}人　・　健康保険　{{ $dailySummaryPeopleTotals['健康保険'] }}人)</div>
                </div>
            </div>
            <div class="cover-sales">
                @if ($isModifiedPrint)
                <div class="line">
                    @endif
                    <div class="cover-sales-row">
                        <span>本日売上金</span>
                        <span>{{ $formatYen($dailySalesAmount) }}</span>
                    </div>
                    <div class="cover-sales-small">(内、カード　{{ $printTotals['カード計'] }})</div>
                    @if (!$isModifiedPrint)
                    <div class="cover-sales-row">
                        <span>本日チャージ金</span>
                        <span>{{ $formatYen($chargeDepositTotal) }}</span>
                    </div>
                    <div class="cover-sales-row">
                        <span>本日チャージ利用</span>
                        <span>{{ $formatYen($chargeUseTotal) }}</span>
                    </div>
                    @endif
                    <div class="cover-sales-row">
                        <span>銀行預入金額</span>
                        <span>{{ $formatYen($bankDepositAmount) }}</span>
                    </div>
                    @if (!$isModifiedPrint)
                    <div class="cover-sales-small">誤差　{{ number_format($bankDepositDifference) }}</div>
                    @else
                    <div class="cover-sales-row">
                        <span>誤差</span>
                        <span>なし</span>
                    </div>
                    @endif

                    <div class="cover-sales-small">確定日：{{ $dailySummary['確定日'] }}</div>
                </div>
                @if ($isModifiedPrint)
            </div>
            @endif
        </div>
    </section>

    <section class="print-page">
        <div class="report-header">
            <div class="report-title">{{ $dailySummary['日報集計店舗'] }}　日報</div>
            <div class="report-date">{{ $dailySummary['日付'] }}</div>
        </div>

        <table class="print-table">
            <colgroup>
                <col style="width: 40px;">
                <col style="width: 34px;">
                <col style="width: 86px;">
                <col style="width: 34px;">
                <col style="width: 58px;">
                <col>
                <col style="width: 64px;">
                <col style="width: 64px;">
                <col style="width: 70px;">
                <col style="width: 58px;">
            </colgroup>
            <thead>
                <tr>
                    <th>時刻</th>
                    <th>No</th>
                    <th>患者名</th>
                    <th>割合</th>
                    <th>レセ負担金</th>
                    <th class="border-left">施術内容・自費治療内容</th>
                    <th>自費</th>
                    <th>保険負担</th>
                    <th>請求金額</th>
                    <th>担当者</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($displayPrintRowsCollection as $row)
                @php
                $dailyOrderClass = match (trim((string) ($row['新患'] ?? ''))) {
                '新患' => 'daily-order-new',
                '新患扱い' => 'daily-order-new-like',
                default => '',
                };
                @endphp
                <tr>
                    <td>{{ $row['時刻'] }}</td>
                    <td class="{{ $dailyOrderClass }}">{{ $loop->iteration }}</td>
                    <td>{{ $row['患者名'] }}</td>
                    <td>{{ $row['割合'] }}</td>
                    <td class="text-right">{{ $formatYen($displayReceiptBurdenAmount($row)) }}</td>
                    <td class="text-left border-left">{{ $row['メニュー集計'] }}</td>
                    <td class="text-right">{{ $row['自費計'] }}</td>
                    <td class="text-right">{{ $formatYen($displayInsuranceAmount($row)) }}</td>
                    <td class="text-right">{{ $row['請求金額計'] }}</td>
                    <td>{{ $row['担当者'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10">表示できる明細データがありません。</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <th colspan="4">合計</th>
                    <td class="text-right">{{ $formatYen($displayReceiptBurdenTotal) }}</td>
                    <td class="border-left"></td>
                    <td class="text-right">{{ $formatYen($printRowsCollection->sum(fn($row) => $moneyToInt($row['自費計'] ?? 0))) }}</td>
                    <td class="text-right">{{ $formatYen($displayInsuranceTotal) }}</td>
                    <td class="text-right">{{ $formatYen($printRowsCollection->sum(fn($row) => $moneyToInt($row['請求金額計'] ?? 0))) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </section>
</body>

</html>
