<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 売上一覧</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sales_print_item.css') }}">
</head>

<body>
    <button type="button" class="print-button" onclick="window.print()">このページを印刷する</button>
    @include('shared.sales.sales_print_item', [
    'stores' => $stores ?? [],
    'targetMonth' => $targetMonth ?? '',
    'grandTotal' => $grandTotal ?? 0,
    'companyName' => $companyName ?? '',
    ])
</body>

</html>