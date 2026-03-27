@php
  $summary = (array) (($selectedRow['summary'] ?? []) ?: []);
  $kihon = (array) (($selectedRow['kihon'] ?? []) ?: []);
  $shaho = (array) (($selectedRow['shaho'] ?? []) ?: []);
  $staffMaster = (array) (($selectedRow['staff_master'] ?? []) ?: []);
  $bonusCalc = (array) (($selectedRow['bonus_calc'] ?? []) ?: []);

  $money = static function (array $source, string $key): string {
      $value = $source[$key] ?? 0;
      $number = is_numeric($value) ? (float) $value : 0.0;
      return number_format($number, 0);
  };

  $moneyFlexible = static function (array $source, string $key): string {
      $value = $source[$key] ?? null;
      if ($value === null || $value === '') {
          return '0';
      }

      $number = is_numeric($value) ? (float) $value : 0.0;
      $plain = floor($number) === $number
          ? (string) (int) $number
          : rtrim(rtrim(sprintf('%.3F', $number), '0'), '.');

      if (str_contains($plain, '.')) {
          [$intPart, $decimalPart] = explode('.', $plain, 2);
          return number_format((int) $intPart) . '.' . $decimalPart;
      }

      return number_format((int) $plain);
  };

  $raw = static function (array $source, string $key): string {
      $value = $source[$key] ?? '';
      if ($value === null) {
          return '';
      }
      return trim((string) $value);
  };

  $inputValue = static function (array $source, string $key): string {
      $value = $source[$key] ?? '';
      if ($value === null) {
          return '';
      }

      if (is_int($value)) {
          return number_format($value);
      }

      if (is_float($value)) {
          $plain = floor($value) === $value
              ? (string) (int) $value
              : rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
          if (str_contains($plain, '.')) {
              [$intPart, $decimalPart] = explode('.', $plain, 2);
              return number_format((int) $intPart) . '.' . $decimalPart;
          }
          return number_format((int) $plain);
      }

      $text = trim((string) $value);
      if ($text === '') {
          return '';
      }

      if (!is_numeric(str_replace(',', '', $text))) {
          return $text;
      }

      $number = (float) str_replace(',', '', $text);
      $plain = floor($number) === $number
          ? (string) (int) $number
          : rtrim(rtrim(sprintf('%.10F', $number), '0'), '.');

      if (str_contains($plain, '.')) {
          [$intPart, $decimalPart] = explode('.', $plain, 2);
          return number_format((int) $intPart) . '.' . $decimalPart;
      }

      return number_format((int) $plain);
  };

  $display = static function (array $source, string $key, string $fallback = '-'): string {
      $value = $source[$key] ?? '';
      if ($value === null) {
          return $fallback;
      }
      $text = trim((string) $value);
      return $text !== '' ? $text : $fallback;
  };

  $transferAmountCalc = (is_numeric($summary['bonus_amo'] ?? null) ? (float) $summary['bonus_amo'] : 0.0)
      - (is_numeric($summary['deduction_sum'] ?? null) ? (float) $summary['deduction_sum'] : 0.0)
      - (is_numeric($summary['transfer_balance'] ?? null) ? (float) $summary['transfer_balance'] : 0.0);
  $summary['transfer_amount_calc'] = $transferAmountCalc;
  $staffMemo = trim((string) ($staffMaster['memo'] ?? ''));

  $sources = [
      'summary' => $summary,
      'kihon' => $kihon,
      'shaho' => $shaho,
      'staff' => $staffMaster,
      'bonus_calc' => $bonusCalc,
      'selected' => (array) $selectedRow,
  ];

  $infoSections = [
      '基本情報' => [
          ['label' => '雇用区分', 'type' => 'text', 'source' => 'selected', 'key' => 'division'],
          ['label' => '会社', 'type' => 'text', 'source' => 'selected', 'key' => 'company_name'],
          ['label' => '店舗', 'type' => 'text', 'source' => 'selected', 'key' => 'store_name'],
      ],
      '税情報' => [
          ['label' => '税額', 'type' => 'text', 'source' => 'staff', 'key' => 'tax_amount'],
          ['label' => '扶養人数', 'type' => 'text', 'source' => 'summary', 'key' => 'fuyo_sum'],
      ],
      '自動計算項目' => [
          ['label' => '当月別賞与', 'type' => 'money', 'source' => 'bonus_calc', 'key' => 'same_month_other_bonus'],
          ['label' => '健保 年度累計標準賞与額', 'type' => 'money', 'source' => 'bonus_calc', 'key' => 'kenpo_fiscal_standard_after'],
          ['label' => '健保 今回計算対象額', 'type' => 'money', 'source' => 'bonus_calc', 'key' => 'kenpo_target_standard'],
          ['label' => '健保上限到達', 'type' => 'flag', 'source' => 'bonus_calc', 'key' => 'kenpo_cap_hit'],
          ['label' => '厚年 同月合算標準賞与額', 'type' => 'money', 'source' => 'bonus_calc', 'key' => 'same_month_standard_after'],
          ['label' => '厚年 今回計算対象額', 'type' => 'money', 'source' => 'bonus_calc', 'key' => 'kounen_target_standard'],
          ['label' => '厚年以上限到達', 'type' => 'flag', 'source' => 'bonus_calc', 'key' => 'kounen_cap_hit'],
      ],
  ];

  $editSections = [
      '支給' => [
          ['label' => '賞与税額', 'key' => 'bonus_tax', 'total' => true, 'readonly' => true],
          ['label' => '賞与額', 'key' => 'bonus_amo', 'total' => false],
          ['label' => '課税支給合計', 'key' => 'taxation_sum', 'total' => true],
          ['label' => '非課税支給合計', 'key' => 'not_taxation_sum', 'total' => true],
          ['label' => '支給合計', 'key' => 'supply_sum', 'total' => true],
      ],
      '控除' => [
          ['label' => '健康保険料', 'key' => 'kenpo', 'total' => false],
          ['label' => '介護保険料', 'key' => 'kaigo', 'total' => false],
          ['label' => '厚生年金保険料', 'key' => 'kounen', 'total' => false],
          ['label' => '雇用保険料', 'key' => 'koyou', 'total' => false],
          ['label' => '労保対象合計', 'key' => 'rouho_target_sum', 'total' => true, 'readonly' => true],
          ['label' => '所得税', 'key' => 'income_tax', 'total' => false],
          ['label' => '社保控除計', 'key' => 'syaho_sum', 'total' => true],
          ['label' => '控除合計', 'key' => 'deduction_sum', 'total' => true],
      ],
      '結果' => [
          ['label' => '振込残額', 'key' => 'transfer_balance', 'total' => false],
          ['label' => '振込額', 'key' => 'transfer_amount_calc', 'total' => true],
      ],
  ];
@endphp
<div class="bonus-layout">
  <div class="bonus-main-col">
    <div class="bonus-sections bonus-sections-main">
      @foreach ($editSections as $title => $items)
        <section class="card">
          <h3 class="bonus-grid-title">{{ $title }}</h3>
          <table class="bonus-kv">
            @foreach ($items as $item)
              <tr class="{{ !empty($item['total']) ? 'total-row' : '' }}">
                <td>{{ $item['label'] }}</td>
                <td>
                  @if ($item['key'] === 'transfer_amount_calc')
                    <span>{{ number_format($transferAmountCalc, 0) }}</span>
                  @elseif (!empty($item['readonly']))
                    <span class="view-only">{{ $item['key'] === 'bonus_tax' ? $moneyFlexible($summary, $item['key']) : $money($summary, $item['key']) }}</span>
                  @else
                    <span class="view-only">{{ $money($summary, $item['key']) }}</span>
                    <input class="edit-input edit-only" data-key="{{ $item['key'] }}" value="{{ $inputValue($summary, $item['key']) }}">
                  @endif
                </td>
              </tr>
            @endforeach
          </table>
        </section>
      @endforeach
    </div>

    <section class="memo-card">
      <div class="memo-label">明細備考</div>
      <div class="memo-value view-only" title="{{ $summary['kyuyo_memo'] ?? '' }}">{{ ($summary['kyuyo_memo'] ?? '') !== '' ? $summary['kyuyo_memo'] : '-' }}</div>
      <textarea class="memo-input edit-input text edit-only" data-key="kyuyo_memo">{{ $raw($summary, 'kyuyo_memo') }}</textarea>
    </section>
    <section class="memo-card readonly-card">
      <div class="memo-label">基本情報メモ</div>
      <div class="memo-value" title="{{ $staffMemo }}">{{ $staffMemo !== '' ? $staffMemo : '-' }}</div>
    </section>
  </div>

  <div class="bonus-side-col">
    <div class="bonus-meta-stack">
      @foreach ($infoSections as $title => $items)
      <section class="card readonly-card bonus-meta-card">
        <h3 class="bonus-grid-title">{{ $title }}</h3>
        <table class="bonus-kv">
          @foreach ($items as $item)
            @php $source = $sources[$item['source']] ?? []; @endphp
            <tr>
              <td>{{ $item['label'] }}</td>
              <td>
                @if (($item['type'] ?? 'text') === 'money')
                  {{ $money($source, $item['key']) }}
                @elseif (($item['type'] ?? 'text') === 'flag')
                  {{ ((int) ($source[$item['key']] ?? 0)) === 1 ? 'あり' : '-' }}
                @else
                  {{ $display($source, $item['key']) }}
                @endif
              </td>
            </tr>
          @endforeach
        </table>
      </section>
      @endforeach
    </div>
  </div>
</div>
