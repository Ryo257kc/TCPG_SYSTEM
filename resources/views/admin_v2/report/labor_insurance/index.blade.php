<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 年度更新確認</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/report.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/labor_insurance.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM 年度更新確認</div>
    </div>

    <section class="panel report-main-panel">
        @include('admin_v2.report.labor_insurance.filters')
        @include('admin_v2.report.labor_insurance.summary')
        @include('admin_v2.report.labor_insurance.table')
        @include('admin_v2.report.labor_insurance.formula_note')
    </section>

    @include('admin_v2.report.labor_insurance.popup_script')
</div>
</body>
</html>
