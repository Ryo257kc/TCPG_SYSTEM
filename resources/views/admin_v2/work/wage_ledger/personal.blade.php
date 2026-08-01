<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TCPG SYSTEM 個人賃金台帳</title>
  <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>

<body>
  <button type="button" class="print-button" onclick="window.print()">印刷</button>

  @php
  $allowanceEntries = is_array($allowanceEntries ?? null) ? $allowanceEntries : [];
  $excludedAllowanceKeys = [
  'allowance_amo_1',
  'allowance_amo_2',
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
  ['label' => '出勤日数', 'key' => 'work_in_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
  ['label' => '実働時間', 'key' => 'work_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
  ['label' => '欠勤日数', 'key' => 'absence_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
  ['label' => '残業時間', 'key' => 'overtime', 'type' => 'num', 'digits' => 2, 'sum' => true],
  ['label' => '休日出勤時間', 'key' => 'holiday_work_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
  ['label' => '休日出勤日数', 'key' => 'work_holiday_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
  ['label' => '有休日数', 'key' => 'holiday_true', 'type' => 'num', 'digits' => 2, 'sum' => true],
  ['label' => '有休残日数', 'key' => 'holiday_true_num', 'type' => 'num', 'digits' => 2, 'sum' => false],
  ['label' => '遅刻・早退時間', 'key' => 'late_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
  ['label' => '賞与額', 'key' => 'bonus_amount', 'type' => 'money', 'sum' => true],
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
  ['label' => '厚生年金保険料', 'key' => 'kounen', 'type' => 'money', 'sum' => true],
  ['label' => '雇用保険料', 'key' => 'koyou', 'type' => 'money', 'sum' => true],
  ['label' => '社会保険料合計', 'key' => 'syaho_sum', 'type' => 'money', 'sum' => true],
  ['label' => '住民税', 'key' => 'resident_tax', 'type' => 'money', 'sum' => true],
  ['label' => '所得税額', 'key' => 'income_tax', 'type' => 'money', 'sum' => true],
  ['label' => '控除項目1', 'key' => 'koujyo_1', 'type' => 'money', 'sum' => true],
  ['label' => '控除合計', 'key' => 'deduction_sum', 'type' => 'money', 'sum' => true],
  ['label' => '年末調整', 'key' => 'adjustment_year_end', 'type' => 'money', 'sum' => true],
  ['label' => '立替精算等', 'key' => 'cost_liquidation', 'type' => 'money', 'sum' => true],
  ['label' => '差引支給額合計', 'key' => 'transfer_amount', 'type' => 'money', 'sum' => true],
  ['label' => '健保標準報酬月額', 'key' => 'kenpo_monthly_amo', 'type' => 'money', 'sum' => false],
  ['label' => '厚年標準報酬月額', 'key' => 'kounen_monthly_amo', 'type' => 'money', 'sum' => false],
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
  $sum = 0.0;
  foreach ($rows as $row) {
  $sum += (float) ($row[$def['key']] ?? 0);
  }
  if (($def['type'] ?? '') === 'money') {
  return number_format($sum);
  }
  return number_format($sum, (int) ($def['digits'] ?? 0));
  };

  $ledgerRows = array_values((array) ($ledgerRows ?? []));
  $pages = array_chunk($ledgerRows, 6);
  $staffInfo = (array) ($staffInfo ?? []);
  @endphp

  @if ($ledgerRows === [])
  <section class="print-page">
    <h1 class="print-title">個人賃金台帳</h1>
    <div class="print-meta">
      <span>{{ $selectedStaffId ?? '' }} {{ $staffInfo['staff_name'] ?? '' }}</span>
      <span>{{ $selectedStartMonthText ?? '' }} 以降</span>
    </div>
    <div class="empty-message">対象データがありません。</div>
  </section>
  @else
  @foreach ($pages as $pageIndex => $pageRows)
  @php
  $isLastPage = $pageIndex === count($pages) - 1;
  @endphp
  <section class="print-page">
    <h1 class="print-title">個人賃金台帳</h1>
    <div class="print-meta">
      <span>{{ $staffInfo['company_name'] ?? '' }} {{ $staffInfo['store_name'] ?? '' }}</span>
      <span>{{ $selectedStartMonthText ?? '' }} 以降</span>
    </div>
    <div class="print-meta">
      <span>{{ $selectedStaffId ?? '' }} {{ $staffInfo['staff_name'] ?? '' }}</span>
      <span>{{ $pageIndex + 1 }} / {{ count($pages) }} ページ</span>
    </div>

    <table class="print-table">
      <thead>
        <tr>
          <th>項目</th>
          @foreach ($pageRows as $row)
          <th>
            {{ $row['column_label'] ?? '' }}<br>
            {{ $row['payment_date'] ?? '' }}
          </th>
          @endforeach
          @if ($isLastPage)
          <th>合計</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @foreach ($rowDefs as $item)
        <tr>
          <th>{{ $item['label'] }}</th>
          @foreach ($pageRows as $row)
          <td style="text-align:right;">{{ $formatValue($row, $item) }}</td>
          @endforeach
          @if ($isLastPage)
          <td style="text-align:right;">{{ $formatTotal($ledgerRows, $item) }}</td>
          @endif
        </tr>
        @endforeach
      </tbody>
    </table>
  </section>
  @endforeach
  @endif
</body>

</html>
