<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 売上一覧</title>
    <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/sales_print_item.css') }}"> -->
    <style>
        @page {
            size: A4 landscape;
            margin: 7mm 4mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e7eb;
            color: #1f2937;
            font-family: "Yu Mincho", "Hiragino Mincho ProN", "MS PMincho", serif;
            font-size: 14.5px;
            line-height: 1.32;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            width: 289mm;
            margin: 0 auto;
            padding: 3mm 6mm;
            background: #fff;
        }

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 5mm;
        }

        .page-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .page-meta {
            display: flex;
            gap: 8mm;
            align-items: flex-end;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .department-grid {
            display: flex;
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 4mm 8mm;
        }

        .department-card {
            width: calc((100% - 24mm) / 4);
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .department-head {
            padding: 6px 8px 5px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .store-name {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }

        .department-name {
            margin-top: 2px;
            font-size: 14.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 1px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-table td:first-child {
            width: 1%;
            white-space: nowrap;
            color: #334155;
            padding-right: 1px;
        }

        .summary-table td:last-child {
            width: 1%;
            white-space: nowrap;
            text-align: right;
            font-variant-numeric: tabular-nums;
            padding-left: 1px;
        }

        .summary-table .total-row td {
            font-size: 14.5px;
            font-weight: 700;
            color: #111827;
            background: #f8fafc;
        }

        .summary-table .grand-row td {
            font-size: 16px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    @include('shared.sales.sales_print_item', [
    'stores' => $stores ?? [],
    'targetMonth' => $targetMonth ?? '',
    'grandTotal' => $grandTotal ?? 0,
    'companyName' => $companyName ?? '',
    ])
</body>

</html>
