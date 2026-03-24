<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 賞与支払届一覧</title>
    @include('admin_v2.report.partials.page_style')
    @include('admin_v2.report.partials.santei_page_style')
    @include('admin_v2.report.partials.bonus_payment_page_style')
</head>
<body>
<div class="page-shell">
    @include('admin_v2.report.partials.bonus_payment_header')
    @include('admin_v2.report.partials.bonus_payment_filters')
    @include('admin_v2.report.partials.bonus_payment_table')
</div>
</body>
</html>
