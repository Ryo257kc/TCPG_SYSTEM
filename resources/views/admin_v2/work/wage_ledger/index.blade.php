<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TCPG SYSTEM {{ !empty($isBonus) ? '賞与' : '給与' }}賃金台帳</title>
  <!-- <link rel="stylesheet" href="{{ asset('css/admin_v2/wage_ledger.css') }}"> -->
  <style>
    @page {
      size: A4 portrait;
      margin: 8mm;
    }

    :root {
      --line: #b7bcc4;
      --text: #1f2a37;
      --muted: #5f6b7a;
      --paper: #ffffff;
      --screen-bg: #e9e9e9;
      --header-bg: #f4f6f8;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      color: var(--text);
      background: var(--screen-bg);
      font-family: "Yu Mincho", "Hiragino Mincho ProN", serif;
      font-size: 13px;
      line-height: 1.4;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .print-toolbar {
      padding: 10px 16px;
      background: #fff;
      border-bottom: 1px solid #ddd;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .print-preview-bg {
      background: var(--screen-bg);
      padding: 16px 0;
    }

    .wage-ledger-print-page {
      width: 194mm;
      min-height: 281mm;
      margin: 0 auto 10px;
      padding: 8mm;
      background: var(--paper);
    }

    .company-page {
      break-after: page;
      page-break-after: always;
    }

    .company-page:last-child {
      break-after: auto;
      page-break-after: auto;
    }

    .sheet-head {
      display: grid;
      grid-template-columns: 1fr auto 1fr;
      align-items: start;
      gap: 12px;
      margin-bottom: 4px;
    }

    .sheet-title {
      font-size: 19px;
      font-weight: 600;
      letter-spacing: 0.04em;
    }

    .sheet-center {
      text-align: center;
      font-size: 13px;
      line-height: 1.35;
    }

    .sheet-company {
      text-align: right;
      font-size: 13px;
      padding-top: 2px;
    }

    .sheet-page-note {
      margin-top: 2px;
      font-size: 11px;
      color: var(--muted);
    }

    .ledger-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      background: #fff;
    }

    .ledger-table th,
    .ledger-table td {
      border: 1px solid var(--line);
      padding: 2px 1px;
      vertical-align: middle;
      font-size: 12px;
    }

    .ledger-table th {
      background: var(--header-bg);
      font-size: 9px;
      font-weight: 500;
      text-align: left;
      padding-top: 4px;
      padding-bottom: 4px;
    }

    .ledger-table td.num,
    .ledger-table th.num {
      text-align: right;
    }

    .ledger-total-row td,
    .ledger-total-row th {
      background: #f1f3f5;
      font-weight: 600;
    }

    .ledger-table-portrait .item-col {
      width: 88px;
      text-align: left;
    }

    .ledger-table-portrait .person-col,
    .ledger-table-portrait .total-col {
      width: 52px;
      text-align: center;
    }

    .ledger-person-id {
      font-size: 10px;
      font-weight: 600;
      margin-bottom: 6px;
      line-height: 1.3;
    }

    .ledger-person-name {
      font-size: 12px;
      font-weight: 600;
      line-height: 1.3;
    }

    .ledger-total-name {
      font-size: 9px;
      font-weight: 600;
    }

    .empty {
      border: 1px solid var(--line);
      padding: 20px;
      text-align: center;
      color: var(--muted);
    }

    .ledger-summary-cell {
      background: #f3f4f6;
      font-weight: 700;
    }

    .ledger-store-total-cell {
      color: #1f2937;
    }

    .ledger-grand-total-cell {
      background: #e5e7eb;
      color: #111827;
    }

    @media print {
      body {
        background: #fff;
      }

      .print-toolbar {
        display: none !important;
      }

      .print-preview-bg {
        background: #fff;
        padding: 0;
      }

      .wage-ledger-print-page {
        width: auto;
        min-height: auto;
        margin: 0;
        padding: 0;
      }

      .company-page {
        break-after: page;
        page-break-after: always;
      }

      .company-page:last-child {
        break-after: auto;
        page-break-after: auto;
      }

      thead {
        display: table-header-group;
      }

      tr {
        break-inside: avoid;
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<div class="print-toolbar no-print">
  <button type="button" class="btn" onclick="window.print()">印刷</button>
</div>

<div class="print-preview-bg">
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
  <div class="wage-ledger-print-page">
    @if ($groupedCompanies === [])
    <div class="empty">対象データがありません。</div>
    @else
    @foreach ($groupedCompanies as $company)
    @php
    $allowanceEntries = is_array($allowanceEntries ?? null) ? $allowanceEntries : [];
    $excludedAllowanceKeys = [
    'allowance_amo_1',
    'allowance_amo_2',
    'traffic_addition',
    ];
    $excludedAllowanceNames = [
    '基本給',
    '役員報酬',
    '賞与',
    ];
    $dynamicAllowanceRows = [];
    foreach ($allowanceEntries as $entry) {
    $entryKey = trim((string) ($entry['key'] ?? ''));
    $entryName = trim((string) ($entry['name'] ?? ''));
    if (
    $entryKey === ''
    || $entryName === ''
    || in_array($entryKey, $excludedAllowanceKeys, true)
    || in_array($entryName, $excludedAllowanceNames, true)
    ) {
    continue;
    }
    $dynamicAllowanceRows[] = [
    'label' => $entryName,
    'key' => $entryKey,
    'type' => 'money',
    'sum' => true,
    ];
    }

    $rowDefs = array_merge(
    [
    ['label' => '総出勤日数', 'key' => 'work_in_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
    ['label' => '平日出勤日数', 'key' => 'work_in_num_net', 'type' => 'num', 'digits' => 0, 'sum' => true],
    ['label' => '休日出勤日数', 'key' => 'work_holiday_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
    ['label' => '欠勤日数', 'key' => 'absence_num', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ['label' => '総出勤時間', 'key' => 'work_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ['label' => '所定時間', 'key' => 'work_time_net', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ['label' => '休日出勤時間', 'key' => 'holiday_work_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ['label' => '残業時間', 'key' => 'overtime', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ['label' => '有休日数', 'key' => 'holiday_true', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ['label' => '有休残日数', 'key' => 'holiday_true_num', 'type' => 'num', 'digits' => 2, 'sum' => false],
    ['label' => '遅刻・早退時間', 'key' => 'late_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
    ],

    !empty($isBonus)
    ?[['label' => '賞与額', 'key' => 'bonus_amount', 'type' => 'money', 'sum' => true]]:[],

    [
    ['label' => '基本給', 'key' => 'basic_salary', 'type' => 'money', 'sum' => true],
    ['label' => '役員報酬', 'key' => 'allowance_amo_2', 'type' => 'money', 'sum' => true],
    ],
    $dynamicAllowanceRows,
    [
    ['label' => '課税対象額合計', 'key' => 'taxation_sum', 'type' => 'money', 'sum' => true],
    ['label' => '非課税対象額合計', 'key' => 'not_taxation_sum', 'type' => 'money', 'sum' => true],
    ['label' => '支給合計', 'key' => 'supply_sum', 'type' => 'money', 'sum' => true],
    ['label' => '健康保険料', 'key' => 'kenpo', 'type' => 'money', 'sum' => true],
    ['label' => '介護保険料', 'key' => 'kaigo', 'type' => 'money', 'sum' => true],
    ['label' => '子ども支援金', 'key' => 'child_support_funds', 'type' => 'money', 'sum' => true],
    ['label' => '厚生年金保険料', 'key' => 'kounen', 'type' => 'money', 'sum' => true],
    ['label' => '雇用保険料', 'key' => 'koyou', 'type' => 'money', 'sum' => true],
    ['label' => '社会保険料合計', 'key' => 'syaho_sum', 'type' => 'money', 'sum' => true],
    ['label' => '住民税', 'key' => 'resident_tax', 'type' => 'money', 'sum' => true],
    ['label' => '所得税額', 'key' => 'income_tax', 'type' => 'money', 'sum' => true],
    ['label' => '控除項目1', 'key' => 'koujyo_1', 'type' => 'money', 'sum' => true],
    ['label' => '控除合計', 'key' => 'deduction_sum', 'type' => 'money', 'sum' => true],
    ['label' => '年末調整', 'key' => 'adjustment_year_end', 'type' => 'money', 'sum' => true],
    ['label' => '立替経費精算', 'key' => 'cost_liquidation', 'type' => 'money', 'sum' => true],
    ['label' => '差引支給額合計', 'key' => 'transfer_amount', 'type' => 'money', 'sum' => true],
    ['label' => '健保標準報酬月額', 'key' => 'kenpo_monthly_amo', 'type' => 'money', 'sum' => true],
    ['label' => '厚年標準報酬月額', 'key' => 'kounen_monthly_amo', 'type' => 'money', 'sum' => true],
    ]
    );

    $formatValue = static function (array $row, array $def): string {
    $raw = $row[$def['key']] ?? null;
    $type = $def['type'] ?? 'text';
    if ($type === 'money') {
    $text = trim((string) $raw);
    if ($text === '') {
    return '-';
    }
    $text = str_replace([',', ' '], '', $text);
    return is_numeric($text) ? number_format((float) $text) : $text;
    }
    if ($type === 'num') {
    return number_format((float) $raw, (int) ($def['digits'] ?? 0));
    }
    $value = trim((string) $raw);
    return $value !== '' ? $value : '-';
    };

    $formatTotal = static function (array $rows, array $def): string {
    if (($def['sum'] ?? false) !== true) {
    return '-';
    }
    if (in_array($def['key'] ?? '', ['kenpo_monthly_amo', 'kounen_monthly_amo'], true)) {
    return '-';
    }
    $sum = 0.0;
    foreach ($rows as $row) {
    $sum += (float) ($row[$def['key']] ?? 0);
    }
    if (($def['type'] ?? '') === 'money') {
    return number_format($sum);
    }
    return number_format($sum, (int) ($def['digits'] ?? 0));
    };

    $storeGroups = [];
    foreach ($company['rows'] as $row) {
    $storeKey = trim((string) ($row['store_name'] ?? ''));
    $storeKey = $storeKey !== '' ? $storeKey : '未設定';
    if (!isset($storeGroups[$storeKey])) {
    $storeGroups[$storeKey] = [];
    }
    $storeGroups[$storeKey][] = $row;
    }

    $columns = [];
    foreach ($storeGroups as $storeName => $storeRows) {
    foreach ($storeRows as $row) {
    $columns[] = [
    'type' => 'staff',
    'label' => $row['staff_name'],
    'sub' => $row['staff_id'],
    'row' => $row,
    ];
    }
    $columns[] = [
    'type' => 'store_total',
    'label' => $storeName,
    'sub' => '店舗計',
    'rows' => $storeRows,
    ];
    }
    $columns[] = [
    'type' => 'grand_total',
    'label' => '総合計',
    'sub' => '',
    'rows' => $company['rows'],
    ];

    $pages = array_chunk($columns, 8);
    @endphp

    @foreach ($pages as $pageIndex => $pageColumns)
    @php
    $displayColumns = $pageColumns;

    while (count($displayColumns) < 8) {
      $displayColumns[]=[ 'type'=> 'blank',
      'label' => '',
      'sub' => '',
      ];
      }

      // このページに表示される列を全部チェック対象にする
      // staff列だけでなく、店舗計と総合計も見る
      $pageRowsForZeroCheck = [];

      foreach ($displayColumns as $displayColumn) {
      $columnType = (string) ($displayColumn['type'] ?? '');

      if ($columnType === 'staff') {
      $pageRowsForZeroCheck[] = (array) ($displayColumn['row'] ?? []);
      continue;
      }

      if ($columnType === 'store_total' || $columnType === 'grand_total') {
      foreach ((array) ($displayColumn['rows'] ?? []) as $totalSourceRow) {
      $pageRowsForZeroCheck[] = (array) $totalSourceRow;
      }
      }
      }

      $hideWhenAllZeroKeys = [
      'late_deduction',
      'absence_deduction',
      ];

      foreach ($dynamicAllowanceRows as $dynamicAllowanceRow) {
      $dynamicKey = trim((string) ($dynamicAllowanceRow['key'] ?? ''));

      if ($dynamicKey !== '') {
      $hideWhenAllZeroKeys[] = $dynamicKey;
      }
      }

      $hideWhenAllZeroKeys = array_values(array_unique($hideWhenAllZeroKeys));

      $visibleRowDefs = array_values(array_filter($rowDefs, static function (array $item) use ($pageRowsForZeroCheck, $hideWhenAllZeroKeys): bool {
      $key = trim((string) ($item['key'] ?? ''));

      if ($key === '') {
      return true;
      }

      // 非表示対象にしていない項目は常に表示
      if (!in_array($key, $hideWhenAllZeroKeys, true)) {
      return true;
      }

      // このページにデータ列がなければ表示
      if ($pageRowsForZeroCheck === []) {
      return true;
      }

      foreach ($pageRowsForZeroCheck as $pageRow) {
      $value = $pageRow[$key] ?? null;
      $text = trim((string) $value);

      if ($text === '') {
      continue;
      }

      $numeric = str_replace([',', ' '], '', $text);

      if (is_numeric($numeric)) {
      if ((float) $numeric !== 0.0) {
      return true;
      }

      continue;
      }

      if ($text !== '-' && $text !== '0') {
      return true;
      }
      }

      return false;
      }));
      @endphp

      <section class="company-page">
        <header class="sheet-head">
          <div class="sheet-title">{{ !empty($isBonus) ? '賞与' : '給与' }}賃金台帳</div>
          <div class="sheet-center">
            <div>{{ $paymentMonthText }}</div>
            <div>{{ $paymentDateText }} 支払</div>
          </div>
          <div class="sheet-company">
            <div>{{ $company['company_name'] }}</div>
            <div class="sheet-page-note">{{ $pageIndex + 1 }} / {{ count($pages) }} ページ</div>
          </div>
        </header>

        <table class="ledger-table ledger-table-portrait">
          <thead>
            <tr class="ledger-total-row">
              <th class="item-col">項目</th>
              @foreach ($displayColumns as $column)
              <th class="person-col">
                @if ($column['type'] === 'staff')
                <div class="ledger-person-id">{{ $column['sub'] }}</div>
                <div class="ledger-person-name">{{ $column['label'] }}</div>
                @elseif ($column['type'] === 'store_total')
                <div class="ledger-person-id">{{ count((array) ($column['rows'] ?? [])) }}人</div>
                <div class="ledger-person-name ledger-total-name">{{ $column['label'] }}</div>
                @elseif ($column['type'] === 'grand_total')
                <div class="ledger-person-id">{{ count((array) ($column['rows'] ?? [])) }}人</div>
                <div class="ledger-person-name ledger-total-name">{{ $column['label'] }}</div>
                @else
                <div class="ledger-person-id">&nbsp;</div>
                <div class="ledger-person-name">&nbsp;</div>
                @endif
              </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($visibleRowDefs as $item)
            <tr class="{{ in_array($item['key'], ['taxation_sum', 'not_taxation_sum', 'supply_sum', 'syaho_sum', 'deduction_sum', 'transfer_amount'], true) ? 'ledger-total-row' : '' }}">
              <td class="item-col">{{ $item['label'] }}</td>
              @foreach ($displayColumns as $column)
              @if ($column['type'] === 'staff')
              <td class="{{ ($item['type'] ?? '') === 'text' ? '' : 'num' }}">{{ $formatValue($column['row'], $item) }}</td>
              @elseif ($column['type'] === 'store_total' || $column['type'] === 'grand_total')
              <td class="num ledger-summary-cell {{ $column['type'] === 'grand_total' ? 'ledger-grand-total-cell' : 'ledger-store-total-cell' }}">
                {{ $formatTotal($column['rows'], $item) }}
              </td>
              @else
              <td>&nbsp;</td>
              @endif
              @endforeach
            </tr>
            @endforeach
          </tbody>
        </table>
      </section>
      @endforeach
      @endforeach
      @endif
  </div>
</div>

</body>

</html>
