<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 算定基礎一覧</title>
    @include('admin_v2.report.partials.page_style')
    @include('admin_v2.report.partials.santei_page_style')
</head>
<body>
<div class="page-shell">
    @include('admin_v2.report.partials.santei_header')
    @include('admin_v2.report.partials.santei_filters')
    @include('admin_v2.report.partials.santei_table')
</div>
</body>
</html>
