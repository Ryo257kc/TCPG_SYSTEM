@php
  $paymentDateText = '-';
  $paymentMonthText = '-';
  if (($selectedPaymentDate ?? '') !== '') {
      $ts = strtotime((string) $selectedPaymentDate);
      if ($ts !== false) {
          $paymentDateText = date('Y年n月j日', $ts);
          $paymentMonthText = date('Y年n月度', $ts);
      } else {
          $paymentDateText = (string) $selectedPaymentDate;
          $paymentMonthText = (string) $selectedPaymentDate;
      }
  }
@endphp
<div class="sheet">
  @if ($groupedCompanies === [])
    <div class="empty">対象データがありません。</div>
  @else
    @foreach ($groupedCompanies as $company)
      <section class="company-page">
        <header class="sheet-head">
          <div class="sheet-title">振込先一覧</div>
          <div class="sheet-center">
            <div>{{ $paymentMonthText }} 給与</div>
            <div>支給日: {{ $paymentDateText }}</div>
          </div>
          <div class="sheet-company">{{ $company['company_name'] }}</div>
        </header>

        @foreach ($company['groups'] as $group)
          <section class="bank-group">
            <table class="bank-table bank-table-narrow">
              <colgroup>
                <col style="width: 78px">
                <col style="width: 110px">
                <col style="width: 108px">
                <col style="width: 130px">
                <col style="width: 110px">
                <col style="width: 82px">
                <col style="width: 118px">
                <col style="width: 120px">
                <col style="width: 90px">
              </colgroup>
              <thead>
                <tr>
                  <th>雇用区分</th>
                  <th>名義人</th>
                  <th>ふりがな</th>
                  <th>振込先</th>
                  <th>支店名</th>
                  <th>口座番号</th>
                  <th class="num">振込支給額</th>
                  <th>市区町村</th>
                  <th class="num">所得税</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($group['rows'] as $row)
                  <tr>
                    <td>{{ $row['division'] !== '' ? $row['division'] : '-' }}</td>
                    <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '-' }}</td>
                    <td>{{ $row['staff_name_furi'] !== '' ? $row['staff_name_furi'] : '-' }}</td>
                    <td>{{ $row['bank_name'] !== '' ? $row['bank_name'] : '-' }}</td>
                    <td>{{ $row['bank_branch'] !== '' ? $row['bank_branch'] : '-' }}</td>
                    <td class="center">{{ $row['account_no'] !== '' ? $row['account_no'] : '-' }}</td>
                    <td class="num">{{ number_format((float) $row['transfer_amount']) }}</td>
                    <td>{{ $row['city'] !== '' ? $row['city'] : '-' }}</td>
                    <td class="num">{{ number_format((float) $row['income_tax']) }}</td>
                  </tr>
                @endforeach
                <tr class="bank-subtotal">
                  <td colspan="6" class="bank-subtotal-label">{{ $group['bank_name'] }} 合計</td>
                  <td class="num bank-subtotal-value"><strong>¥{{ number_format((float) $group['transfer_total']) }}</strong></td>
                  <td colspan="2"></td>
                </tr>
              </tbody>
            </table>
          </section>
        @endforeach

        <div class="summary-row">
          <section class="resident-summary">
            <div class="resident-summary-title">住民税 市町村別サマリー</div>
            <table class="resident-summary-table">
              <colgroup>
                <col style="width: 110px">
                <col>
                <col style="width: 90px">
                <col style="width: 120px">
              </colgroup>
              <thead>
                <tr>
                  <th>指定番号</th>
                  <th>市区町村</th>
                  <th class="num">対象者人数</th>
                  <th class="num">住民税合計</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($company['city_totals'] as $cityTotal)
                  <tr>
                    <td>{{ $cityTotal['specified_num'] !== '' ? $cityTotal['specified_num'] : '-' }}</td>
                    <td>{{ $cityTotal['city'] }}</td>
                    <td class="num">{{ number_format((int) $cityTotal['row_count']) }}</td>
                    <td class="num">¥{{ number_format((float) $cityTotal['resident_tax_total']) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="center">該当なし</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </section>

          <section class="company-summary">
            <div class="company-summary-title">会社合計</div>
            <table class="company-summary-table">
              <tbody>
                <tr>
                  <th>件数</th>
                  <td class="num">{{ number_format((int) $company['row_count']) }}</td>
                </tr>
                <tr>
                  <th>業務委託以外人数</th>
                  <td class="num">{{ number_format((int) $company['non_outsource_count']) }}</td>
                </tr>
                <tr>
                  <th>振込支給合計</th>
                  <td class="num">¥{{ number_format((float) $company['transfer_total']) }}</td>
                </tr>
                <tr>
                  <th>住民税</th>
                  <td class="num">¥{{ number_format((float) $company['resident_tax_total']) }}</td>
                </tr>
                <tr>
                  <th>課税支給合計</th>
                  <td class="num">¥{{ number_format((float) $company['taxation_total']) }}</td>
                </tr>
                <tr>
                  <th>所得税</th>
                  <td class="num">¥{{ number_format((float) $company['income_tax_total']) }}</td>
                </tr>
              </tbody>
            </table>
          </section>
        </div>
      </section>
    @endforeach
  @endif
</div>
