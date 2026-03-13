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
      <button class="btn primary" type="button" id="confirm-btn">解除</button>
    </div>
  @else
    @if ($attendanceConfirmed)
    <div class="card-status-actions">
      <button class="btn primary" type="button" id="confirm-btn">確定</button>
    </div>
    @else
    <div class="card-status-note" id="attendance-confirm-note">勤怠確定後に給与確定できます</div>
    @endif
  @endif
</section>
</div>
