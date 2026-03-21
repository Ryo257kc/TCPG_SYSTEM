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
      @php
        $allowanceEntries = is_array($allowanceEntries ?? null) ? $allowanceEntries : [];
        $allowanceLabelMap = is_array($allowanceLabelMap ?? null) ? $allowanceLabelMap : [];
        $excludedAllowanceKeys = [
            'allowance_amo_1',
            'allowance_amo_2',
        ];
        $excludedAllowanceNames = [
            '基本給',
            '役員報酬',
            '賞与額',
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
                ['label' => '雇用区分', 'key' => 'division', 'type' => 'text', 'sum' => false],
                ['label' => '出勤日数', 'key' => 'work_in_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
                ['label' => '実働時間', 'key' => 'work_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
                ['label' => '欠勤日数', 'key' => 'absence_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
                ['label' => '残業時間', 'key' => 'overtime', 'type' => 'num', 'digits' => 2, 'sum' => true],
                ['label' => '休日勤務日数', 'key' => 'work_holiday_num', 'type' => 'num', 'digits' => 0, 'sum' => true],
                ['label' => '休日勤務時間', 'key' => 'holiday_work_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
                ['label' => '有休残日数', 'key' => 'holiday_true_num', 'type' => 'num', 'digits' => 0, 'sum' => false],
                ['label' => '有休日数', 'key' => 'holiday_true', 'type' => 'num', 'digits' => 2, 'sum' => true],
                ['label' => '遅刻・早退時間', 'key' => 'late_time', 'type' => 'num', 'digits' => 2, 'sum' => true],
                ['label' => '賞与額', 'key' => 'bonus_amount', 'type' => 'money', 'sum' => true],
                ['label' => '基本給', 'key' => 'basic_salary', 'type' => 'money', 'sum' => true],
                ['label' => '役員報酬', 'key' => 'officer_com', 'type' => 'money', 'sum' => true],
            ],
            $dynamicAllowanceRows,
            [
                ['label' => '課税支給合計', 'key' => 'taxation_sum', 'type' => 'money', 'sum' => true],
                ['label' => '非課税支給合計', 'key' => 'not_taxation_sum', 'type' => 'money', 'sum' => true],
                ['label' => '支給合計', 'key' => 'supply_sum', 'type' => 'money', 'sum' => true],
                ['label' => '健康保険料', 'key' => 'kenpo', 'type' => 'money', 'sum' => true],
                ['label' => '介護保険料', 'key' => 'kaigo', 'type' => 'money', 'sum' => true],
                ['label' => '厚生年金保険料', 'key' => 'kounen', 'type' => 'money', 'sum' => true],
                ['label' => '雇用保険料', 'key' => 'koyou', 'type' => 'money', 'sum' => true],
                ['label' => '社会保険料計', 'key' => 'syaho_sum', 'type' => 'money', 'sum' => true],
                ['label' => '住民税', 'key' => 'resident_tax', 'type' => 'money', 'sum' => true],
                ['label' => '所得税', 'key' => 'income_tax', 'type' => 'money', 'sum' => true],
                ['label' => '定額減税', 'key' => 'koujyo_1', 'type' => 'money', 'sum' => true],
                ['label' => '控除合計', 'key' => 'deduction_sum', 'type' => 'money', 'sum' => true],
                ['label' => '年末調整', 'key' => 'adjustment_year_end', 'type' => 'money', 'sum' => true],
                ['label' => '立替経費清算', 'key' => 'cost_liquidation', 'type' => 'money', 'sum' => true],
                ['label' => '差引支給合計', 'key' => 'transfer_amount', 'type' => 'money', 'sum' => true],
                ['label' => '健保標準報酬月額', 'key' => 'kenpo_monthly_amo', 'type' => 'money', 'sum' => true],
                ['label' => '厚保標準報酬月額', 'key' => 'kounen_monthly_amo', 'type' => 'money', 'sum' => true],
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
              $displayColumns[] = [
                  'type' => 'blank',
                  'label' => '',
                  'sub' => '',
              ];
          }

          $pageRowsForZeroCheck = [];
          foreach ($displayColumns as $displayColumn) {
              if (($displayColumn['type'] ?? '') === 'staff') {
                  $pageRowsForZeroCheck[] = (array) ($displayColumn['row'] ?? []);
              }
          }

          $hideWhenAllZeroKeys = [
              'late_deduction',
              'absence_deduction',
          ];
          foreach ($dynamicAllowanceRows as $dynamicAllowanceRow) {
              $hideWhenAllZeroKeys[] = (string) ($dynamicAllowanceRow['key'] ?? '');
          }
          $hideWhenAllZeroKeys = array_values(array_filter(array_unique($hideWhenAllZeroKeys)));

          $visibleRowDefs = array_values(array_filter($rowDefs, static function (array $item) use ($pageRowsForZeroCheck, $hideWhenAllZeroKeys): bool {
              $key = (string) ($item['key'] ?? '');
              if ($key === '' || !in_array($key, $hideWhenAllZeroKeys, true)) {
                  return true;
              }
              foreach ($pageRowsForZeroCheck as $pageRow) {
                  $value = $pageRow[$key] ?? null;
                  $text = trim((string) $value);
                  if ($text === '') {
                      continue;
                  }
                  $numeric = str_replace([',', ' '], '', $text);
                  if (is_numeric($numeric) && (float) $numeric !== 0.0) {
                      return true;
                  }
                  if (!is_numeric($numeric) && $text !== '-' && $text !== '0') {
                      return true;
                  }
              }
              return false;
          }));
        @endphp
        <section class="company-page">
          <header class="sheet-head">
            <div class="sheet-title">給与明細</div>
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
              <tr>
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
                      <td class="num">{{ $formatTotal($column['rows'], $item) }}</td>
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
