<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 年度更新集計</title>
    @include('admin_v2.report.partials.page_style')
    @include('admin_v2.report.partials.labor_insurance_page_style_clean')
</head>
<body>
<div class="page-shell">
    @include('admin_v2.report.partials.labor_insurance_header_clean')
    @include('admin_v2.report.partials.labor_insurance_filters')
    @include('admin_v2.report.partials.labor_insurance_summary_clean')
    @include('admin_v2.report.partials.labor_insurance_table_v2')
    @include('admin_v2.report.partials.labor_insurance_formula_note_clean')
</div>
</body>
</html>
