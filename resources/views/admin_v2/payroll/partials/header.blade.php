<header class="head">
<h1 class="head-title">TCPG SYSTEM 給与計算</h1>
<nav class="head-nav">
<a class="btn" href="{{ route('admin.attendance.index', ['month' => date('Y-m', strtotime($selectedMonth . '-01 -1 month')), 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">勤怠管理</a>
<a class="btn" href="{{ route('admin.payroll.index', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">給与計算</a>
<a class="btn" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
</nav>
</header>
