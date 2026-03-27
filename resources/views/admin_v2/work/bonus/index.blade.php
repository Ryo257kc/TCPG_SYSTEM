<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TCPG SYSTEM 賞与計算</title>
<link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin_v2/payroll.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin_v2/bonus.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
@php
  $selectedRow = collect($rows)->firstWhere('staff_id', $selectedStaffId) ?: (count($rows) > 0 ? $rows[0] : null);
  $summary = (array) ($selectedRow['summary'] ?? []);
@endphp
<div class="wrap">
<div class="top">
  <div class="title">TCPG SYSTEM 賞与計算</div>
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
  <button class="btn btn-primary" type="submit">表示</button>
  <button class="btn" type="button" id="bonus-create-btn">賞与データ作成</button>
</form>

<div class="create-inline" id="bonus-create-inline" hidden>
  <div class="create-inline-head">
    <div class="create-inline-title">賞与データ作成</div>
    <div class="create-inline-actions">
      <button type="button" class="btn" id="bonus-create-select-all-btn">全選択</button>
      <button type="button" class="btn" id="bonus-create-clear-btn">解除</button>
      <button type="button" class="btn" id="bonus-create-close-btn">閉じる</button>
    </div>
  </div>
  <div class="create-inline-body">
    <div class="create-inline-row">
      <label for="bonus-create-payment-date">作成日</label>
      <input id="bonus-create-payment-date" type="date">
      <button type="button" class="btn" id="bonus-create-show-btn">表示</button>
      <button type="button" class="btn btn-primary" id="bonus-create-submit-btn">作成</button>
      <button type="button" class="btn" id="bonus-delete-submit-btn">削除</button>
    </div>
    <div class="create-inline-note">未作成と作成済を一覧に表示します。作成は未作成のみ、削除は作成済のみ対象です。</div>
    <div class="create-inline-list" id="bonus-create-list"></div>
    <div class="create-inline-empty" id="bonus-create-empty" hidden>対象者はありません。</div>
  </div>
</div>

<div class="count">対象件数 {{ count($rows) }}</div>
<div class="content">
<aside class="staff-list">
@php $currentStaffId = $selectedRow['staff_id'] ?? null; @endphp
@forelse ($rows as $row)
  @php $isActive = $currentStaffId !== null && $currentStaffId === ($row['staff_id'] ?? null); @endphp
  <a class="staff-item {{ $isActive ? 'active' : '' }}" href="{{ route('admin.bonus.index', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $row['staff_id']]) }}">
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
<main class="main bonus-main" id="bonus-main">
@if ($selectedRow)
@php
  $bonusSummary = (array) (($selectedRow['summary'] ?? []) ?: []);
  $paymentRaw = trim((string) ($bonusSummary['supply_month'] ?? $selectedPaymentDate ?? ''));
  $timestamp = $paymentRaw !== '' ? strtotime($paymentRaw) : false;
  $bonusPaymentDate = $timestamp !== false ? date('Y/m/d', $timestamp) : ($paymentRaw !== '' ? (preg_split('/[ T]/', $paymentRaw)[0] ?? $paymentRaw) : '-');
  $bonusAmount = (float) ($bonusSummary['bonus_amo'] ?? 0);
  $deductionSum = (float) ($bonusSummary['deduction_sum'] ?? 0);
  $transferBalance = (float) ($bonusSummary['transfer_balance'] ?? 0);
  $transferAmount = $bonusAmount - $deductionSum - $transferBalance;
@endphp
<div class="top-cards">
  <section class="card"><div class="k">対象者</div><div class="v name-value">{{ $selectedRow['staff_id'] }} {{ $selectedRow['staff_name'] }}</div></section>
  <section class="card"><div class="k">会社</div><div class="v name-value">{{ $selectedRow['company_name'] }}</div></section>
  <section class="card"><div class="k">店舗</div><div class="v name-value">{{ $selectedRow['store_name'] }}</div></section>
  <section class="card"><div class="k">支給日</div><div class="v">{{ $bonusPaymentDate }}</div></section>
  <section class="card"><div class="k">雇用区分</div><div class="v">{{ $selectedRow['division'] !== '' ? $selectedRow['division'] : 'N/A' }}</div></section>
  <section class="card"><div class="k">振込額</div><div class="v">{{ number_format($transferAmount, 0) }}</div></section>
</div>
<div class="actions">
  <button class="btn" type="button" id="edit-toggle-btn">編集</button>
  <button class="btn" type="button" id="bonus-recalculate-btn">再計算</button>
</div>
@include('admin_v2.work.bonus.sections')
@else
<div class="empty">対象スタッフがありません。</div>
@endif
</main>
</div>
</section>
</div>
@include('admin_v2.work.bonus.page_script')
</body>
</html>
