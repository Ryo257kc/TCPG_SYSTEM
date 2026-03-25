<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 離職票確認</title>
    @include('admin_v2.report.partials.page_style')
    @include('admin_v2.report.partials.labor_insurance_page_style_clean')
    @include('admin_v2.report.partials.rishoku_page_style')
</head>
<body>
<div class="page-shell">
    @include('admin_v2.report.partials.rishoku_header_v2')
    @include('admin_v2.report.partials.rishoku_filters')
    @include('admin_v2.report.partials.rishoku_summary')
    @include('admin_v2.report.partials.rishoku_table_v6')
</div>
</body>
</html>

