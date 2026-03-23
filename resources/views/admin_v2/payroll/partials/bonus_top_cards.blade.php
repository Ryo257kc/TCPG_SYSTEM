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
