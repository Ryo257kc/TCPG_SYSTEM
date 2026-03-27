<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TCPG SYSTEM 給与計算</title>
<link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin_v2/payroll.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
@php require resource_path('views/admin_v2/work/payroll/page_state_runtime.php'); @endphp
<div class="wrap">
<div class="top">
    <div class="title">TCPG SYSTEM 給与計算</div>
</div>
<section class="panel">
<form method="GET" class="filters">
    <label for="payment_date">支給日</label>
    <select id="payment_date" name="payment_date">
        @foreach ($availablePaymentDates as $paymentDate)
            <option value="{{ $paymentDate }}" @selected($selectedPaymentDate === $paymentDate)>{{ $paymentDate }}</option>
        @endforeach
    </select>

    <label for="company_id">会社</label>
    <select id="company_id" name="company_id">
        <option value="">全社</option>
        @foreach ($companyOptions as $company)
            <option value="{{ $company }}" @selected($selectedCompanyId === $company)>{{ $company }}</option>
        @endforeach
    </select>

    <label for="staff_id">スタッフ</label>
    <select id="staff_id" name="staff_id">
        <option value=""></option>
        @foreach ($staffRows as $staff)
            <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId === $staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
        @endforeach
    </select>

    <label for="report_type">帳票</label>
    <select id="report_type" name="report_type">
        <option value=""></option>
        <option value="transfer-list">振込先一覧</option>
        <option value="wage-ledger">賃金台帳</option>
    </select>

    <button class="btn btn-primary" type="submit">表示</button>
    <button class="btn" type="button" id="payroll-create-btn">給与データ作成</button>
</form>

<div class="create-inline" id="payroll-create-inline" hidden>
  <div class="create-inline-head">
    <div class="create-inline-title">給与データ作成</div>
    <div class="create-inline-actions">
      <button type="button" class="btn" id="payroll-create-select-all-btn">全選択</button>
      <button type="button" class="btn" id="payroll-create-clear-btn">解除</button>
      <button type="button" class="btn" id="payroll-create-close-btn">閉じる</button>
    </div>
  </div>
  <div class="create-inline-body">
    <div class="create-inline-row">
      <label for="payroll-create-payment-date">作成日</label>
      <input id="payroll-create-payment-date" type="date">
      <button type="button" class="btn" id="payroll-create-show-btn">表示</button>
      <button type="button" class="btn btn-primary" id="payroll-create-submit-btn">作成</button>
      <button type="button" class="btn" id="payroll-delete-submit-btn">削除</button>
    </div>
    <div class="create-inline-note">未作成と作成済を一覧に表示します。作成は未作成のみ、削除は作成済のみ対象です。</div>
    <div class="create-inline-list" id="payroll-create-list"></div>
    <div class="create-inline-empty" id="payroll-create-empty" hidden>対象者はありません。</div>
  </div>
</div>

<div class="count">対象者件数 {{ count($rows) }}</div>
<div class="content">
<aside class="staff-list">
@php
    $currentStaffId = $selectedRow['staff_id'] ?? null;
@endphp
@forelse ($rows as $row)
    @php
        $isActive = $currentStaffId !== null && $currentStaffId === ($row['staff_id'] ?? null);
        $payrollConfirmed = ((int) (($row['summary']['edit_lock'] ?? 0))) === 1;
    @endphp
    <a class="staff-item {{ $isActive ? 'active' : '' }} {{ $payrollConfirmed ? 'payroll-confirmed' : 'payroll-unconfirmed' }}" href="{{ route('admin.payroll.index', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $row['staff_id']]) }}">
        <p class="staff-name">{{ $row['staff_name'] }}</p>
        <div class="staff-meta">
            <span>{{ $row['staff_id'] }}</span>
            <span>-</span>
            <span>{{ $row['division'] !== '' ? $row['division'] : 'N/A' }}</span>
        </div>
    </a>
@empty
    <div class="empty">対象スタッフがありません。</div>
@endforelse
</aside>
<main class="main" id="payroll-main">
@php
if (!isset($selectedRow) || $selectedRow === null) {
    $selectedRow = count($rows) > 0 ? $rows[0] : null;
}
@endphp
@if ($selectedRow)
<div class="top-cards">
@php
  $cardSummary = (array) (($selectedRow['summary'] ?? []) ?: []);
  $cardPaymentDate = $paymentDate ?? '-';
  if (!isset($paymentDate) || $cardPaymentDate === '-' || $cardPaymentDate === '') {
      $raw = trim((string) ($cardSummary['supply_month'] ?? ''));
      if ($raw !== '') {
          $ts = strtotime($raw);
          $cardPaymentDate = $ts !== false ? date('Y/m/d', $ts) : ((preg_split('/[ T]/', $raw)[0] ?? $raw));
      }
  }
  $payrollConfirmed = ((int) ($cardSummary['edit_lock'] ?? 0)) === 1;
  $attendanceConfirmed = ((int) ($cardSummary['attendance_checked'] ?? 0)) === 1;
@endphp
<section class="card"><div class="k">対象者</div><div class="v name-value">{{ $selectedRow['staff_id'] }} {{ $selectedRow['staff_name'] }}</div></section>
<section class="card"><div class="k">会社名</div><div class="v name-value">{{ $selectedRow['company_name'] }}</div></section>
<section class="card"><div class="k">店舗名</div><div class="v name-value">{{ $selectedRow['store_name'] }}</div></section>
<section class="card"><div class="k">支給日</div><div class="v">{{ $cardPaymentDate }}</div></section>
<section class="card"><div class="k">雇用区分</div><div class="v">{{ $selectedRow['division'] !== '' ? $selectedRow['division'] : 'N/A' }}</div></section>
<section class="card">
  <div class="k">給与確定状態</div>
  @if ($payrollConfirmed)
    <div class="card-status-actions">
      <button class="btn btn-primary" type="button" id="confirm-btn">解除</button>
    </div>
  @else
    @if ($attendanceConfirmed)
    <div class="card-status-actions">
      <button class="btn btn-primary" type="button" id="confirm-btn">確定</button>
    </div>
    @else
    <div class="card-status-note" id="attendance-confirm-note">勤怠確定後に給与確定できます</div>
    @endif
  @endif
</section>
</div>
<div class="actions">
  <button class="btn" type="button" id="edit-toggle-btn">編集</button>
  <button class="btn" type="button" id="attendance-reflect-btn">勤怠反映</button>
  <button class="btn" type="button" id="recalc-btn">再計算</button>
  <button class="btn" type="button" id="calc-overtime-deduction-btn">割増・控除</button>
  <button class="btn" type="button" id="calc-koyou-btn">雇用保険</button>
  <button class="btn" type="button" id="calc-income-tax-btn">所得税</button>
</div>
@include('admin_v2.work.payroll.layout_columns')
<div class="summary-grid">
  <section class="memo-card">
    <div class="memo-label">{!! $l('kyuyo_memo') !!}</div>
    <div class="memo-value" title="{{ $t('kyuyo_memo') }}">{{ $t('kyuyo_memo') }}</div>
    <textarea class="memo-input edit-input text" data-key="kyuyo_memo">{{ $t('kyuyo_memo') }}</textarea>
  </section>
  <section class="total-card"><div class="k">支給合計</div><div class="v">{{ number_format($supplySum, 0) }}</div></section>
  <section class="total-card"><div class="k">その他合計</div><div class="v">{{ number_format($otherSum, 0) }}</div></section>
  <section class="total-card"><div class="k">控除合計</div><div class="v">{{ number_format($deductionSum, 0) }}</div></section>
  <section class="total-card"><div class="k">差引支給額</div><div class="v">{{ number_format($netPay, 0) }}</div></section>
</div>
@else
<div class="empty">対象スタッフがありません。</div>
@endif
</main>
</div>
</section>
</div>
@include('admin_v2.work.payroll.page_script')
</body>
</html>
