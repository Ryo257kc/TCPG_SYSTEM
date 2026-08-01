<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>TCPG SYSTEM - 往療内訳表</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f1f1f1;
            color: #000;
            font-family: "Yu Gothic", "Meiryo", sans-serif;
            font-size: 14px;
        }

        .print-actions {
            width: 210mm;
            margin: 12px auto;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .print-actions button,
        .print-actions a {
            border: 1px solid #8da4c3;
            background: #e9f1fb;
            color: #000;
            padding: 6px 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .print-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 12px;
            padding: 18mm 16mm;
            background: #fff;
            page-break-after: always;
        }

        .print-page:last-child {
            page-break-after: auto;
        }

        .print-note-top {
            text-align: right;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .print-title {
            text-align: center;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.15em;
            margin: 8px 0 14px;
        }

        .print-header-line {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1fr;
            gap: 8px;
            align-items: end;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .print-header-line .center {
            text-align: center;
        }

        .print-header-line .right {
            text-align: right;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 14px;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #000;
            height: 31px;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .print-table th {
            text-align: center;
            font-weight: 700;
        }

        .print-table td {
            text-align: center;
        }

        .print-table .text-left {
            text-align: left;
        }

        .reason-box {
            border: 1px solid #000;
            border-top: 0;
            padding: 10px 22px;
            line-height: 1.9;
        }

        .reason-title {
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .reason-row {
            margin: 4px 0;
        }

        .reason-row .circle {
            display: inline-flex;
            width: 24px;
            height: 24px;
            border: 2px solid #000;
            border-radius: 50%;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            font-weight: 700;
        }

        .notes-box {
            border: 1px solid #000;
            border-top: 0;
            padding: 8px 22px 14px;
            line-height: 1.9;
        }

        .notes-box p {
            margin: 3px 0;
        }

        .empty-message {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 24mm 18mm;
            background: #fff;
        }

        .daily-summary-title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .daily-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px 14px;
            align-items: start;
        }

        .daily-summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        .daily-summary-table th,
        .daily-summary-table td {
            border: 1px solid #888;
            height: 18px;
            padding: 1px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .daily-summary-table th {
            font-weight: 400;
        }

        .daily-summary-table .text-left {
            text-align: left;
        }

        .daily-summary-day-break td {
            border-top: 2px solid #000;
        }

        @media print {
            body {
                background: #fff;
            }

            .print-actions {
                display: none;
            }

            .print-page,
            .empty-message {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="print-actions">
        <a href="{{ route('home_visit.monthly_visit', ['target_month' => $target_month]) }}">戻る</a>
        <button type="button" onclick="window.print()">印刷</button>
    </div>

    @if($print_type === '3')
    @php
    $dailySummaryGroups = ($dailySummaryRows ?? collect())->groupBy('daily_report_store_name');
    $dailySummaryRowsPerColumn = 44;
    @endphp

    @forelse($dailySummaryGroups as $daily_report_store_name => $storeRows)
    @php
    $chunks = $storeRows->values()->chunk($dailySummaryRowsPerColumn);
    @endphp
    <section class="print-page">
        <h1 class="daily-summary-title">{{ $targetMonthStart->format('Y.n') }} {{ $daily_report_store_name }}</h1>
        <div class="daily-summary-grid">
            @foreach($chunks as $chunk)
            <table class="daily-summary-table">
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 18%;">
                    <col style="width: 46%;">
                    <col style="width: 18%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>往療</th>
                        <th>日報施設</th>
                        <th>件数</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk->values() as $row)
                    @php
                    $previousRow = $loop->index > 0 ? $chunk->values()->get($loop->index - 1) : null;
                    $isDayBreak = $previousRow !== null && $previousRow->added_at_day !== $row->added_at_day;
                    @endphp
                    <tr class="{{ $isDayBreak ? 'daily-summary-day-break' : '' }}">
                        <td>{{ $row->added_at_day }}</td>
                        <td>{{ $row->mark }}</td>
                        <td class="text-left">{{ $row->daily_report_facility_name }}</td>
                        <td>{{ $row->count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endforeach
        </div>
    </section>
    @empty
    <div class="empty-message">
        <p>印刷対象データがありません。</p>
    </div>
    @endforelse
    @else
    @php
    $groups = $records->groupBy('patient_id');
    $targetMonthLabel = $targetMonthStart->format('Y/n') . ' 月 分';
    @endphp

    @forelse($groups as $patientRecords)
    @php
    $first = $patientRecords->first();
    $blankRows = max(0, 6 - $patientRecords->count());
    @endphp
    <section class="print-page">
        <div class="print-note-top">別添（様式第7号）</div>
        <h1 class="print-title">往療内訳表</h1>

        <div class="print-header-line">
            <div>{{ $targetMonthLabel }}</div>
            <div class="center">出張専門の施術者の場合（　）</div>
            <div class="right">（患者名：{{ $first->patient_name }}）</div>
        </div>

        <table class="print-table">
            <colgroup>
                <col style="width: 9%;">
                <col style="width: 14%;">
                <col style="width: 17%;">
                <col style="width: 26%;">
                <col style="width: 34%;">
            </colgroup>
            <thead>
                <tr>
                    <th>日付</th>
                    <th>同一日・同一<br>建物記入欄</th>
                    <th>施術者名</th>
                    <th>往療の起点</th>
                    <th>施術した場所</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patientRecords as $record)
                <tr>
                    <td>
                        @if(!empty($record->added_at))
                        {{ \Carbon\Carbon::parse($record->added_at)->format('j') }}日
                        @endif
                    </td>
                    <td>{{ $record->mark }}</td>
                    <td>{{ $record->staff_name }}</td>
                    <td class="text-left">{{ $record->start_point_address }}</td>
                    <td>{{ $record->daily_report_address }}</td>
                </tr>
                @endforeach
                @for($i = 0; $i < $blankRows; $i++)
                    <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    </tr>
                    @endfor
            </tbody>
        </table>

        <div class="reason-box">
            <div class="reason-title">
                <span>往療を必要とする理由　　介護保険の要介護度（　　　　　　　　　　　　　　）分かれば記載下さい</span>
            </div>
            <div class="reason-row"><span class="circle">1</span>独歩による公共交通機関を使っての外出が困難</div>
            <div class="reason-row">2.　認知症や視覚、内部、精神障害などにより単独での外出が困難</div>
            <div class="reason-row">3.　その他</div>
        </div>

        <div class="notes-box">
            <p>注 ・ 同上の場合は、「同上」や「〃」との記載で差し支えない。</p>
            <p>　 ・ 同一日・同一建物記入欄には、同一日に同一建物への往療に該当する場合であって、当該患者について往療料を算定している場合には「◎」を、算定していない場合には「〇」を記入すること。</p>
            <p>　 ・ 往療の起点については、個人宅は丁目までの記載で可とする。</p>
            <p>　 ・ 個人情報の取り扱いには、十分注意すること。</p>
            <p>　 ・ 出張専門の施術者の場合は、「出張専門の施術者の場合（　）」に「〇」を記入すること。</p>
        </div>
    </section>
    @empty
    <div class="empty-message">
        <p>印刷対象データがありません。</p>
    </div>
    @endforelse
    @endif
</body>

</html>
