<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TCPG SYSTEM &#32102;&#19982;&#35336;&#31639;</title>
@include('admin_v2.payroll.partials.page_style')
</head>
<body>
@php require resource_path('views/admin_v2/payroll/partials/page_state_runtime.php'); @endphp
<div class="wrap">
@include('admin_v2.payroll.partials.header')
<section class="panel">
@include('admin_v2.payroll.partials.filters')
@include('admin_v2.payroll.partials.create_inline')
<div class="count">&#23550;&#35937;&#32773;&#20214;&#25968; {{ count($rows) }}</div>
<div class="content">
@include('admin_v2.payroll.partials.staff_list')
<main class="main" id="payroll-main">
@php
if (!isset($selectedRow) || $selectedRow === null) {
    $selectedRow = count($rows) > 0 ? $rows[0] : null;
}
@endphp
@if ($selectedRow)
@include('admin_v2.payroll.partials.top_cards')
@include('admin_v2.payroll.partials.actions')
@include('admin_v2.payroll.partials.layout_columns')
@include('admin_v2.payroll.partials.summary_grid')
@else
<div class="empty">Select staff.</div>
@endif
</main>
</div>
</section>
</div>
@include('admin_v2.payroll.partials.page_script')
</body>
</html>
