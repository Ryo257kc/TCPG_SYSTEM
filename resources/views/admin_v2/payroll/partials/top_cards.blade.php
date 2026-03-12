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
  $attendanceChecked = ((int) ($cardSummary['attendance_checked'] ?? 0)) === 1;
@endphp
<section class="card"><div class="k">&#23550;&#35937;&#32773;</div><div class="v name-value">{{ $selectedRow['staff_id'] }} {{ $selectedRow['staff_name'] }}</div></section>
<section class="card"><div class="k">&#25903;&#32102;&#26085;</div><div class="v">{{ $cardPaymentDate }}</div></section>
<section class="card"><div class="k">&#38599;&#29992;&#21306;&#20998;</div><div class="v">{{ $selectedRow['division'] !== '' ? $selectedRow['division'] : 'N/A' }}</div></section>
<section class="card"><div class="k">&#20250;&#31038; / &#24215;&#33303;</div><div class="v">{{ $selectedRow['company_name'] }}<br>{{ $selectedRow['store_name'] }}</div></section>
<section class="card">
  <div class="k">&#32102;&#19982;&#30906;&#23450;&#29366;&#24907;</div>
  @if ($attendanceChecked)
    <div class="card-status-actions">
      <button class="btn primary" type="button" id="confirm-btn">&#30906;&#23450;</button>
    </div>
  @else
    <div class="card-status-note">&#21220;&#24608;&#26410;&#30906;&#23450;</div>
  @endif
</section>
</div>
