<div class="summary-grid">
  @include('admin_v2.payroll.partials.memo_kyuyo')
  <section class="total-card"><div class="k">&#25903;&#32102;&#21512;&#35336;</div><div class="v">{{ number_format($supplySum, 0) }}</div></section>
  <section class="total-card"><div class="k">&#12381;&#12398;&#20182;&#21512;&#35336;</div><div class="v">{{ number_format($otherSum, 0) }}</div></section>
  <section class="total-card"><div class="k">&#25511;&#38500;&#21512;&#35336;</div><div class="v">{{ number_format($deductionSum, 0) }}</div></section>
  <section class="total-card"><div class="k">&#24046;&#24341;&#25903;&#32102;&#38989;</div><div class="v">{{ number_format($netPay, 0) }}</div></section>
</div>