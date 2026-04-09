<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 売上一覧</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/sales_print_item.css') }}">
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
