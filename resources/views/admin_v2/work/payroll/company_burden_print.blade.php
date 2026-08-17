<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会社負担一覧{{ ($isBonus ?? false) ? '（賞与）' : '' }}</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        .print-page {
            width: 297mm;
            min-height: 210mm;
        }

        /* 要確認：print.cssの.print-page.landscapeがpadding:8mm 10mmを持っていて
           （このファイルのローカル.print-pageはpaddingを上書きしていなかった）、
           画面プレビュー時に幅297mmのつもりが実質277mm相当まで狭くなり、
           表がはみ出す原因になっていた。ここで明示的に上書きする（2026-08-15）。 */
        .print-page.landscape {
            width: 297mm;
            padding: 6mm;
        }

        .company-burden-title {
            margin: 0 0 5mm;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .company-burden-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 4mm;
            font-size: 12px;
            font-weight: 700;
        }

        .company-burden-group {
            margin-top: 5mm;
        }

        .company-burden-group:first-of-type {
            margin-top: 0;
        }

        .company-burden-heading {
            margin: 0 0 2mm;
            font-size: 13px;
            font-weight: 700;
        }

        .company-burden-table {
            font-size: 11px;
            table-layout: fixed;
            width: 100%;
        }

        .company-burden-table th,
        .company-burden-table td {
            padding: 1px 2px;
            line-height: 1.25;
            overflow: hidden;
            white-space: nowrap;
        }

        .company-burden-table .name {
            text-align: left;
        }

        .company-burden-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .company-burden-table .group-self {
            background: #eaf4ff;
        }

        .company-burden-table .group-office {
            background: #fff2cc;
        }

        .company-burden-table .group-total {
            background: #eef7e8;
        }

        .company-burden-table tfoot th,
        .company-burden-table tfoot td {
            font-weight: 700;
            background: #f3f4f6;
        }

        @media print {
            .print-page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">印刷</button>
    @php
    $money = static fn($value): string => (float) $value == 0.0 ? '' : number_format((float) $value);
    @endphp
    <section class="print-page landscape">
        <h1 class="company-burden-title">会社負担一覧{{ ($isBonus ?? false) ? '（賞与）' : '' }}</h1>
        <div class="company-burden-meta">
            <div>{{ ($isBonus ?? false) ? '支給日' : '対象月：' . $selectedMonth . ' / 支給日' }}：{{ $selectedPaymentDate }}</div>
            <div>{{ $companyLabel !== '' ? $companyLabel : '全社' }}　{{ now()->format('Y/n/j H:i:s') }} 現在</div>
        </div>

        @if (empty($groupedStores))
        <div class="empty-message">表示するデータがありません。</div>
        @else
        @foreach ($groupedStores as $group)
        <section class="company-burden-group">
            <h2 class="company-burden-heading">
                <!-- {{ $group['company_name'] !== '' ? $group['company_name'] : '会社未設定' }}
                / -->
                {{ $group['store_name'] !== '' ? $group['store_name'] : '部署未設定' }}
            </h2>
            <table class="print-table company-burden-table">
                <colgroup>
                    <col style="width: 7mm;">
                    <col style="width: 22mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 15mm;">
                    <col style="width: 15mm;">
                    <col style="width: 13mm;">
                    <col style="width: 15mm;">
                    <col style="width: 17mm;">
                    <col style="width: 17mm;">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">氏名</th>
                        <th colspan="2">健康保険</th>
                        <th colspan="2">介護保険</th>
                        <th colspan="2">子ども支援金</th>
                        <th colspan="2">厚生年金</th>
                        <th colspan="2">雇用保険</th>
                        <th rowspan="2">自己負担合計</th>
                        <th rowspan="2">児童手当拠出金</th>
                        <th rowspan="2">労災保険</th>
                        <th rowspan="2">会社負担合計</th>
                        <th rowspan="2">差引支給合計</th>
                        <th rowspan="2" class="group-total">総合計</th>
                    </tr>
                    <tr>
                        <th class="group-self">自己</th>
                        <th class="group-office">会社</th>
                        <th class="group-self">自己</th>
                        <th class="group-office">会社</th>
                        <th class="group-self">自己</th>
                        <th class="group-office">会社</th>
                        <th class="group-self">自己</th>
                        <th class="group-office">会社</th>
                        <th class="group-self">自己</th>
                        <th class="group-office">会社</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['rows'] as $row)
                    <tr>
                        <td class="num">{{ $row['staff_id'] }}</td>
                        <td class="name">{{ $row['staff_name'] !== '' ? $row['staff_name'] : $row['staff_id'] }}</td>
                        <td class="num group-self">{{ $money($row['kenpo_self']) }}</td>
                        <td class="num group-office">{{ $money($row['kenpo_office']) }}</td>
                        <td class="num group-self">{{ $money($row['kaigo_self']) }}</td>
                        <td class="num group-office">{{ $money($row['kaigo_office']) }}</td>
                        <td class="num group-self">{{ $money($row['child_support_self']) }}</td>
                        <td class="num group-office">{{ $money($row['child_support_funds']) }}</td>
                        <td class="num group-self">{{ $money($row['kounen_self']) }}</td>
                        <td class="num group-office">{{ $money($row['kounen_office']) }}</td>
                        <td class="num group-self">{{ $money($row['koyou_self']) }}</td>
                        <td class="num group-office">{{ $money($row['koyou_office']) }}</td>
                        <td class="num group-self">{{ $money($row['self_total']) }}</td>
                        <td class="num group-office">{{ $money($row['jidou_office']) }}</td>
                        <td class="num group-office">{{ $money($row['rousai_office']) }}</td>
                        <td class="num group-office">{{ $money($row['office_total']) }}</td>
                        <td class="num">{{ $money($row['transfer_amount']) }}</td>
                        <td class="num group-total">{{ $money($row['total_with_burden']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">部署合計</th>
                        <td class="num">{{ $money($group['totals']['kenpo_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['kenpo_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['kaigo_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['kaigo_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['child_support_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['child_support_funds'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['kounen_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['kounen_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['koyou_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['koyou_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['self_total'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['jidou_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['rousai_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['office_total'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['transfer_amount'] ?? 0) }}</td>
                        <td class="num">{{ $money($group['totals']['total_with_burden'] ?? 0) }}</td>
                    </tr>
                    {{-- 要確認：Accessのスクショで確認した「N名」行の下段（部署合計行のすぐ下、
                    colspanで実際の列幅にそのまま揃えた合算額）を再現。健保+介護＝4列分、
                    子供支援＝2列分、厚生年金＝2列分、雇用保険＝2列分でそれぞれ自己+会社を合算。 --}}
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="4" class="num">{{ $money(($group['totals']['kenpo_total'] ?? 0) + ($group['totals']['kaigo_total'] ?? 0)) }}</td>
                        <td colspan="2" class="num">{{ $money($group['totals']['child_support_total'] ?? 0) }}</td>
                        <td colspan="2" class="num">{{ $money($group['totals']['kounen_total'] ?? 0) }}</td>
                        <td colspan="2" class="num">{{ $money($group['totals']['koyou_total'] ?? 0) }}</td>
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        @endforeach

        <section class="company-burden-group">
            <h2 class="company-burden-heading">総合計</h2>
            {{-- 要確認：Accessのスクショ（8名行＋その下のcolspan合算行＋引落し社会保険料）を
            そのまま再現。健保+介護+子供支援は6列分・厚生年金2列分・雇用保険2列分でそれぞれ
            自己+会社を合算。引落し社会保険料＝健保total+介護total+厚年total+子供支援total+
            児童手当拠出金（雇用保険・労災は労働保険として別払いのため含めない）。
            2026-08-15、スクショの実数値（¥581,569）とほぼ一致することを検算済み。 --}}
            @php
            $insuranceWithdrawalTotal = ($grandTotals['kenpo_total'] ?? 0)
            + ($grandTotals['kaigo_total'] ?? 0)
            + ($grandTotals['kounen_total'] ?? 0)
            + ($grandTotals['child_support_total'] ?? 0)
            + ($grandTotals['jidou_office'] ?? 0);
            @endphp
            <table class="print-table company-burden-table">
                <colgroup>
                    <col style="width: 9mm;">
                    <col style="width: 22mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 13mm;">
                    <col style="width: 15mm;">
                    <col style="width: 15mm;">
                    <col style="width: 13mm;">
                    <col style="width: 15mm;">
                    <col style="width: 17mm;">
                    <col style="width: 17mm;">
                </colgroup>
                <tbody>
                    <tr>
                        <th colspan="2">{{ count($groupedStores) > 0 ? array_sum(array_map(static fn($g) => count($g['rows']), $groupedStores)) : 0 }}名</th>
                        <td class="num">{{ $money($grandTotals['kenpo_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['kenpo_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['kaigo_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['kaigo_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['child_support_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['child_support_funds'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['kounen_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['kounen_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['koyou_self'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['koyou_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['self_total'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['jidou_office'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['rousai_office'] ?? 0) }}</td>
                        <td class="num group-office">{{ $money($grandTotals['office_total'] ?? 0) }}</td>
                        <td class="num">{{ $money($grandTotals['transfer_amount'] ?? 0) }}</td>
                        <td class="num group-total">{{ $money($grandTotals['total_with_burden'] ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="6" class="num">{{ $money(($grandTotals['kenpo_total'] ?? 0) + ($grandTotals['kaigo_total'] ?? 0) + ($grandTotals['child_support_total'] ?? 0)) }}</td>
                        <td colspan="2" class="num">{{ $money($grandTotals['kounen_total'] ?? 0) }}</td>
                        <td colspan="2" class="num">{{ $money($grandTotals['koyou_total'] ?? 0) }}</td>
                        <td colspan="4" class="group-total">引落し社会保険料</td>
                        <td colspan="2" class="num group-total">{{ $money($insuranceWithdrawalTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
        @endif
    </section>
</body>

</html>
