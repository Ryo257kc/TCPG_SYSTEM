<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>請求書</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 10mm;
        }

        .invoice-print-page {
            font-size: 12px;
        }

        .invoice-title {
            margin: 0 0 10mm;
            text-align: center;
            font-size: 24px;
            letter-spacing: 0.2em;
            font-weight: 700;
        }

        .invoice-head {
            display: grid;
            grid-template-columns: 1fr 68mm;
            gap: 12mm;
            margin-bottom: 8mm;
        }

        .invoice-recipient {
            font-size: 16px;
            border-bottom: 1px solid #111;
            padding-bottom: 2mm;
        }

        .invoice-message {
            margin-top: 6mm;
            font-size: 12px;
        }

        .invoice-total-box {
            display: inline-grid;
            grid-template-columns: auto 1fr;
            margin-top: 10mm;
            border: 1px solid #111;
            font-size: 18px;
            font-weight: 700;
        }

        .invoice-total-box span {
            padding: 2mm 4mm;
        }

        .invoice-total-box span:first-child {
            border-right: 1px solid #111;
            background: #f3f4f6;
        }

        .invoice-source {
            position: relative;
            display: grid;
            gap: 2mm;
            align-content: start;
            min-height: 42mm;
        }

        .invoice-date {
            text-align: right;
        }

        .invoice-company {
            margin-top: 5mm;
            font-size: 15px;
            font-weight: 700;
        }

        .invoice-company-sub {
            line-height: 1.6;
        }

        .invoice-seal {
            position: absolute;
            right: 4mm;
            top: 18mm;
            width: 24mm;
            height: 24mm;
            object-fit: contain;
        }

        .invoice-subject {
            margin-bottom: 6mm;
            border-bottom: 1px solid #111;
            padding-bottom: 2mm;
            font-size: 14px;
        }

        .invoice-table {
            font-size: 11px;
            width: 99.6%;
        }

        .invoice-table th,
        .invoice-table td {
            padding: 2mm;
        }

        .invoice-table .text {
            text-align: left;
        }

        .invoice-table .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .invoice-summary {
            display: grid;
            grid-template-columns: 1fr 62mm;
            gap: 8mm;
            margin-top: 6mm;
        }

        .invoice-bank {
            border: 1px solid #111;
            padding: 3mm;
            line-height: 1.7;
            min-height: 24mm;
        }

        .invoice-summary-table th,
        .invoice-summary-table td {
            padding: 2mm 3mm;
        }

        .invoice-summary-table {
            width: 99.6%;
        }

        .invoice-summary-table th {
            text-align: left;
            background: #f3f4f6;
        }

        .invoice-summary-table td {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .invoice-remarks {
            margin-top: 6mm;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .invoice-fee-note {
            margin-top: 2mm;
            font-size: 11px;
        }

        @media print {
            .invoice-print-page {
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

    @if (!$invoice)
    <section class="print-page invoice-print-page">
        <div class="empty-message">請求書データがありません。</div>
    </section>
    @else
    @php
    $sealPath = trim((string) ($invoice['billing_seal_image_path'] ?? ''));
    if ($sealPath !== '' && !str_contains($sealPath, '/') && !str_contains($sealPath, '\\')) {
    $sealPath = 'document/seals/' . $sealPath;
    }
    $sealSrc = $sealPath !== ''
    ? (preg_match('/^https?:\/\//', $sealPath) ? $sealPath : asset(ltrim($sealPath, '/')))
    : '';
    @endphp
    <section class="print-page invoice-print-page">
        <h1 class="invoice-title">請求書</h1>

        <div class="invoice-head">
            <div>
                <div class="invoice-recipient">{{ $invoice['recipient_name'] }} 御中</div>
                <div class="invoice-message">下記の通り、御請求申し上げます。</div>
                <div class="invoice-total-box">
                    <span>御請求額</span>
                    <span>￥{{ $totals['grand_total'] ?: '0' }}</span>
                </div>
            </div>

            <div class="invoice-source">
                <div class="invoice-date">請求日：{{ $invoice['invoice_date_label'] }}</div>
                <div class="invoice-company">{{ str_replace('㈱', '株式会社', $invoice['billing_company_name'] ?: $invoice['billing_source']) }}</div>
                <div class="invoice-company-sub">
                    @if (($invoice['billing_postal_code'] ?? '') !== '')
                    〒{{ $invoice['billing_postal_code'] }}<br>
                    @endif
                    @if (($invoice['billing_company_address'] ?? '') !== '')
                    {{ $invoice['billing_company_address'] }}<br>
                    @endif
                    TEL：{{ $invoice['billing_tel'] ?: '079-422-6600' }}<br>
                    FAX：{{ $invoice['billing_fax'] ?: '079-422-6661' }}
                </div>
                @if ($sealSrc !== '')
                <img class="invoice-seal" src="{{ $sealSrc }}" alt="印影">
                @endif
            </div>
        </div>

        <div class="invoice-subject">件名：{{ $invoice['subject'] }}</div>

        <table class="print-table invoice-table">
            <colgroup>
                <col style="width: 26%;">
                <col style="width: 12%;">
                <col style="width: 16%;">
                <col style="width: 7%;">
                <col style="width: 7%;">
                <col style="width: 12%;">
                <col style="width: 12%;">
                <col style="width: 8%;">
            </colgroup>
            <thead>
                <tr>
                    <th>摘要</th>
                    <th>区分</th>
                    <th>店舗</th>
                    <th>数量</th>
                    <th>単位</th>
                    <th>単価</th>
                    <th>合計</th>
                    <th>税</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($details as $detail)
                <tr>
                    <td class="text">{{ $detail['item_name'] }}</td>
                    <td>{{ $detail['category'] }}</td>
                    <td>{{ $detail['store_name'] }}</td>
                    <td class="num">{{ $detail['quantity'] }}</td>
                    <td>{{ $detail['unit'] }}</td>
                    <td class="num">{{ $detail['unit_price'] }}</td>
                    <td class="num">{{ $detail['total_amount'] }}</td>
                    <td>{{ $detail['tax_category'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">明細がありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="invoice-summary">
            <div class="invoice-bank">
                <strong>お振込先</strong><br>
                {{ $invoice['billing_bank_name'] }}<br>
                {!! nl2br(e($invoice['billing_bank_account'])) !!}<br>
                {{ str_replace('㈱', '株式会社', $invoice['billing_company_name'] ?: $invoice['billing_source']) }}
                <div class="invoice-fee-note">※振込手数料は貴社にてご負担くださいますようお願いいたします。</div>
            </div>

            <table class="print-table invoice-summary-table">
                <tr>
                    <th>小計</th>
                    <td>{{ $totals['subtotal'] ?: '0' }}</td>
                </tr>
                <tr>
                    <th>消費税</th>
                    <td>{{ $totals['tax'] ?: '0' }}</td>
                </tr>
                <tr>
                    <th>総合計</th>
                    <td>{{ $totals['grand_total'] ?: '0' }}</td>
                </tr>
            </table>
        </div>

        @if (($invoice['remarks'] ?? '') !== '')
        <div class="invoice-remarks">{{ $invoice['remarks'] }}</div>
        @endif
    </section>
    @endif
</body>

</html>
