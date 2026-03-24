<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM 有休管理</title>
    @include('admin_v2.paid_leave.partials.page_style')
</head>
<body>
<div class="page-shell">
    @include('admin_v2.paid_leave.partials.header')
    @include('admin_v2.paid_leave.partials.filters')
    @include('admin_v2.paid_leave.partials.summary_cards')
    @include('admin_v2.paid_leave.partials.history_table')
</div>

@include('admin_v2.paid_leave.partials.page_script')
</body>
</html>
