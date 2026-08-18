<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TCPG SYSTEM {{ !empty($isBonus) ? '賞与' : '給与' }}振込先一覧</title>
  <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/transfer_list.css') }}"> -->
  <style>
    @page {
      size: A4 landscape;
      margin: 8mm;
    }

    :root {
      --line: #b7bcc4;
      --text: #1f2a37;
      --muted: #5f6b7a;
      --bg: #ffffff;
      --header-bg: #f4f6f8;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      color: var(--text);
      background: #e9e9e9;
      font-family: "Yu Mincho", "Hiragino Mincho ProN", serif;
      font-size: 14px;
      line-height: 1.4;
    }


    .print-toolbar {
      padding: 10px 16px;
      background: #fff;
      border-bottom: 1px solid #ddd;
      position: sticky;
      top: 0;
      z-index: 10;
    }


    .sheet {
      width: 277mm;
      /* A4横 */
      min-height: 190mm;
      margin: 16px auto;
      /* 中央寄せ */
      padding: 10mm;
      background: #fff;
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.15);
    }

    .sheet-head {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: start;
      gap: 16px;
      margin-bottom: 12px;
    }

    .sheet-title {
      font-size: 24px;
      font-weight: 500;
      letter-spacing: 0.06em;
    }

    .sheet-center {
      text-align: center;
      font-size: 16px;
      line-height: 1.6;
    }

    .sheet-company {
      text-align: right;
      font-size: 16px;
      padding-top: 8px;
    }

    .bank-group {
      margin-top: 6px;
    }

    .company-page {
      break-after: page;
      page-break-after: always;
    }

    .company-page:last-child {
      break-after: auto;
      page-break-after: auto;
    }

    .bank-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .bank-table th,
    .bank-table td {
      border: 1px solid var(--line);
      padding: 6px 3px;
      vertical-align: middle;
    }

    .bank-table th {
      background: var(--header-bg);
      font-size: 11px;
      font-weight: 500;
      text-align: left;
      white-space: nowrap;
    }

    .bank-table td.num,
    .bank-table th.num {
      text-align: right;
    }

    .bank-table td.center,
    .bank-table th.center {
      text-align: center;
    }

    .secondary-account-row td {
      background: var(--header-bg);
    }

    .bank-subtotal td {
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      border-top: 0;
      border-left: 0;
      border-right: 0;
      padding: 4px 0 8px;
    }

    .bank-subtotal td strong {
      font-size: 15px;
      margin-left: 6px;
    }

    .bank-subtotal-label {
      text-align: right !important;
      padding-right: 8px !important;
      white-space: nowrap;
    }

    .bank-subtotal-value {
      text-align: left !important;
      padding-left: 4px !important;
      white-space: nowrap;
    }

    .bank-subtotal-value strong {
      margin-left: 0 !important;
    }

    .resident-summary {
      flex: 1 1 0;
      margin-top: 0;
    }

    .resident-summary-title {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .resident-summary-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .resident-summary-table th,
    .resident-summary-table td {
      border: 1px solid var(--line);
      padding: 4px 6px;
    }

    .resident-summary-table th {
      background: var(--header-bg);
      font-size: 12px;
      font-weight: 500;
      text-align: left;
    }

    .resident-summary-table td.num,
    .resident-summary-table th.num {
      text-align: right;
    }

    .sheet-total {
      margin-top: 18px;
    }

    .summary-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 260px;
      gap: 14px;
      margin-top: 10px;
      align-items: start;
    }

    .company-summary-title {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .company-summary-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .company-summary-table th,
    .company-summary-table td {
      border: 1px solid var(--line);
      padding: 4px 6px;
    }

    .company-summary-table th {
      background: var(--header-bg);
      font-size: 12px;
      font-weight: 500;
      text-align: left;
      width: 150px;
    }

    .company-summary-table td.num {
      text-align: right;
      font-weight: 600;
    }

    .company-total {
      margin-top: 12px;
      font-size: 20px;
      font-weight: 600;
      text-align: right;
    }

    .sheet-total table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .sheet-total td {
      border: 1px solid var(--line);
      padding: 10px 12px;
      font-size: 18px;
      font-weight: 600;
    }

    .sheet-total td.label {
      width: 110px;
      text-align: center;
    }

    .sheet-total td.count {
      width: 120px;
      text-align: center;
    }

    .sheet-total td.num {
      text-align: right;
    }

    .sheet-total {
      display: none;
    }

    .empty {
      border: 1px solid var(--line);
      padding: 20px;
      text-align: center;
      color: var(--muted);
    }

    @media print {

      /* .sheet {
        width: auto;
        margin: 0;
        padding: 10mm 8mm;
      } */
      .print-toolbar {
        display: none !important;
      }
    }
  </style>
</head>

<body>
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

  <div class="print-toolbar no-print">
    <button type="button" class="btn" onclick="window.print()">印刷</button>
  </div>
  <div class="sheet ">
    <div class="print-preview-bg">
      @if ($groupedCompanies === [])
      <div class="empty">対象データがありません。</div>
      @else
      @foreach ($groupedCompanies as $company)
      <section class="company-page">
        <header class="sheet-head">
          <div class="sheet-title">{{ !empty($isBonus) ? '賞与' : '給与' }}振込先一覧</div>
          <div class="sheet-center">
            <div>{{ $paymentMonthText }}</div>
            <div>支払日: {{ $paymentDateText }}</div>
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
                <th>部署区分</th>
                <th>支給者名</th>
                <th>ふりがな</th>
                <th>振込銀行</th>
                <th>支店名</th>
                <th>口座番号</th>
                <th class="num">振込支払額</th>
                <th>市町村名</th>
                <th class="num">所得税額</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($group['rows'] as $row)
              @php
              $hasSecondaryAccount = !empty($row['secondary_account']);
              $primaryAmount = $hasSecondaryAccount
                ? (float) $row['transfer_amount'] - (float) $row['secondary_account']['amount']
                : (float) $row['transfer_amount'];
              @endphp
              <tr>
                <td>{{ $row['division'] !== '' ? $row['division'] : '-' }}</td>
                <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '-' }}</td>
                <td>{{ $row['staff_name_furi'] !== '' ? $row['staff_name_furi'] : '-' }}</td>
                <td>{{ $row['bank_name'] !== '' ? $row['bank_name'] : '-' }}</td>
                <td>{{ $row['bank_branch'] !== '' ? $row['bank_branch'] : '-' }}</td>
                <td class="center">{{ $row['account_no'] !== '' ? $row['account_no'] : '-' }}{{ $hasSecondaryAccount && $row['transfer_purpose'] !== '' ? '（' . $row['transfer_purpose'] . '）' : '' }}</td>
                <td class="num">{{ number_format($primaryAmount) }}</td>
                <td>{{ $row['city'] !== '' ? $row['city'] : '-' }}</td>
                <td class="num">{{ number_format((float) $row['income_tax']) }}</td>
              </tr>
              @if ($hasSecondaryAccount)
              <tr class="secondary-account-row">
                <td>-</td>
                <td></td>
                <td></td>
                <td>{{ $row['secondary_account']['bank_name'] !== '' ? $row['secondary_account']['bank_name'] : '-' }}</td>
                <td>{{ $row['secondary_account']['bank_branch'] !== '' ? $row['secondary_account']['bank_branch'] : '-' }}</td>
                <td class="center">{{ $row['secondary_account']['account_no'] !== '' ? $row['secondary_account']['account_no'] : '-' }}</td>
                <td class="num">{{ number_format((float) $row['secondary_account']['amount']) }}</td>
                <td>-</td>
                <td></td>
              </tr>
              @endif
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
                  <th>市町村名</th>
                  <th class="num">対象人数</th>
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
                  <th>人数</th>
                  <td class="num">{{ number_format((int) $company['row_count']) }}</td>
                </tr>
                <tr>
                  <th>営業店在籍者人数</th>
                  <td class="num">{{ number_format((int) $company['non_outsource_count']) }}</td>
                </tr>
                <tr>
                  <th>振込支払額合計</th>
                  <td class="num">¥{{ number_format((float) $company['transfer_total']) }}</td>
                </tr>
                <tr>
                  <th>住民税</th>
                  <td class="num">¥{{ number_format((float) $company['resident_tax_total']) }}</td>
                </tr>
                <tr>
                  <th>課税対象額合計</th>
                  <td class="num">¥{{ number_format((float) $company['taxation_total']) }}</td>
                </tr>
                <tr>
                  <th>所得税額</th>
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
  </div>

</body>

</html>
