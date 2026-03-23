<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TCPG SYSTEM 賞与計算</title>
@include('admin_v2.payroll.partials.page_style')
@include('admin_v2.payroll.partials.bonus_page_style')
</head>
<body>
@php
  $selectedRow = collect($rows)->firstWhere('staff_id', $selectedStaffId) ?: (count($rows) > 0 ? $rows[0] : null);
  $summary = (array) ($selectedRow['summary'] ?? []);
@endphp
<div class="wrap">
@include('admin_v2.payroll.partials.bonus_header')
<section class="panel">
@include('admin_v2.payroll.partials.bonus_filters')
@include('admin_v2.payroll.partials.bonus_create_inline')
<div class="count">対象者件数 {{ count($rows) }}</div>
<div class="content">
@include('admin_v2.payroll.partials.bonus_staff_list')
<main class="main bonus-main" id="bonus-main">
@if ($selectedRow)
@include('admin_v2.payroll.partials.bonus_top_cards')
@include('admin_v2.payroll.partials.bonus_actions')
@include('admin_v2.payroll.partials.bonus_sections')
@else
<div class="empty">対象スタッフがありません。</div>
@endif
</main>
</div>
</section>
</div>
@include('admin_v2.payroll.partials.bonus_page_script')
</body>
</html>
