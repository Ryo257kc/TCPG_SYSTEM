<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TCPG SYSTEM - 賞与計算</title>
  <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin_v2/payroll.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin_v2/payslip_item.css') }}">
  <style>
    .wrap {
      margin: 6px auto 18px
    }

    .bonus-main {
      display: grid;
      gap: 6px
    }

    .bonus-layout {
      display: flex;
      flex-wrap: nowrap;
      gap: 6px;
      align-items: flex-start
    }

    .bonus-main-col,
    .bonus-side-col,
    .bonus-meta-stack {
      display: grid;
      gap: 6px;
      min-width: 0
    }

    .bonus-main-col {
      flex: 1 1 auto
    }

    .bonus-side-col {
      flex: 0 0 280px
    }

    @media (max-width:980px) {
      .bonus-layout,
      .bonus-sections {
        flex-wrap: wrap
      }

      .bonus-side-col {
        flex: 1 1 280px
      }
    }

    .bonus-sections {
      display: flex;
      flex-wrap: nowrap;
      gap: 6px;
      align-items: flex-start
    }

    .bonus-sections-main .card {
      flex: 1 1 0
    }

    .bonus-sections .card {
      min-width: 0
    }


    .bonus-meta-card {
      background: #eef1f5;
      border-color: #d8dee8
    }

    .bonus-meta-card .section-title,
    .bonus-meta-card td {
      color: #5f6775
    }

  </style>
</head>

<body>
  @include('admin_v2.shared.global_nav')
  @php
  $selectedRow = collect($rows)->firstWhere('staff_id', $selectedStaffId) ?: (count($rows) > 0 ? $rows[0] : null);
  $summary = (array) ($selectedRow['summary'] ?? []);
  @endphp
  <div class="wrap">
    <div class="top">
      <div class="title">TCPG SYSTEM 賞与計算</div>
    </div>
    <section class="panel">
      <form method="GET" class="filters">
        <label for="payment_date">支給日</label>
        <select id="payment_date" name="payment_date">
          @foreach ($availablePaymentDates as $paymentDate)
          <option value="{{ $paymentDate }}" @selected($selectedPaymentDate===$paymentDate)>{{ $paymentDate }}</option>
          @endforeach
        </select>
        <label for="company_id">会社</label>
        <select id="company_id" name="company_id">
          <option value="">全社</option>
          @foreach ($companyOptions as $company)
          <option value="{{ $company }}" @selected($selectedCompanyId===$company)>{{ $company }}</option>
          @endforeach
        </select>
        <label for="staff_id">スタッフ</label>
        <select id="staff_id" name="staff_id">
          <option value=""></option>
          @foreach ($staffRows as $staff)
          <option value="{{ $staff['staff_id'] }}" @selected($selectedStaffId===$staff['staff_id'])>{{ $staff['staff_id'] }} {{ $staff['staff_name'] }}</option>
          @endforeach
        </select>
        <button class="btn btn-primary" type="submit">表示</button>
        <button class="btn" type="button" id="bonus-create-btn">賞与データ作成</button>
      </form>

      <div class="create-inline" id="bonus-create-inline" hidden>
        <div class="create-inline-head">
          <div class="create-inline-title">賞与データ作成</div>
          <div class="create-inline-actions">
            <button type="button" class="btn" id="bonus-create-select-all-btn">全選択</button>
            <button type="button" class="btn" id="bonus-create-clear-btn">解除</button>
            <button type="button" class="btn" id="bonus-create-close-btn">閉じる</button>
          </div>
        </div>
        <div class="create-inline-body">
          <div class="create-inline-row">
            <label for="bonus-create-payment-date">作成日</label>
            <input id="bonus-create-payment-date" type="date">
            <button type="button" class="btn" id="bonus-create-show-btn">表示</button>
            <button type="button" class="btn btn-primary" id="bonus-create-submit-btn">作成</button>
            <button type="button" class="btn" id="bonus-delete-submit-btn">削除</button>
          </div>
          <div class="create-inline-note">未作成と作成済を一覧に表示します。作成は未作成のみ、削除は作成済のみ対象です。</div>
          <div class="create-inline-list" id="bonus-create-list"></div>
          <div class="create-inline-empty" id="bonus-create-empty" hidden>対象者はありません。</div>
        </div>
      </div>

      <div class="count">対象件数 {{ count($rows) }}</div>
      <div class="content">
        <aside class="staff-list">
          @php $currentStaffId = $selectedRow['staff_id'] ?? null; @endphp

          @forelse ($rows as $row)
          @php
          $isActive = $currentStaffId !== null && $currentStaffId === ($row['staff_id'] ?? null);
          $bonusConfirmed = ((int) (($row['summary']['edit_lock'] ?? 0))) === 1;
          @endphp

          <a
            class="staff-item {{ $isActive ? 'active' : '' }} {{ $bonusConfirmed ? 'payroll-confirmed' : 'payroll-unconfirmed' }}"
            href="{{ route('admin.bonus.index', [
      'payment_date' => $selectedPaymentDate,
      'company_id' => $selectedCompanyId,
      'staff_id' => $row['staff_id']
    ]) }}">
            <p class="staff-name">{{ $row['staff_name'] }}</p>
            <div class="staff-meta">
              <span>{{ $row['staff_id'] }}</span>
              <span>-</span>
              <span>{{ $row['division'] !== '' ? $row['division'] : 'N/A' }}</span>
            </div>
          </a>

          @empty
          <div class="empty">対象スタッフがいません。</div>
          @endforelse
        </aside>
        <main class="main bonus-main" id="bonus-main">
          @if ($selectedRow)
          @php
          $bonusSummary = (array) (($selectedRow['summary'] ?? []) ?: []);
          $paymentRaw = trim((string) ($bonusSummary['supply_month'] ?? $selectedPaymentDate ?? ''));
          $timestamp = $paymentRaw !== '' ? strtotime($paymentRaw) : false;
          $bonusPaymentDate = $timestamp !== false ? date('Y/m/d', $timestamp) : ($paymentRaw !== '' ? (preg_split('/[ T]/', $paymentRaw)[0] ?? $paymentRaw) : '-');
          $transferAmount = (float) ($bonusSummary['transfer_amount'] ?? 0);
          $bonusConfirmed = ((int) ($summary['edit_lock'] ?? 0)) === 1;
          @endphp
          <div class="top-cards">
            <section class="card">
              <div class="k">対象者</div>
              <div class="v name-value">{{ $selectedRow['staff_id'] }} {{ $selectedRow['staff_name'] }}</div>
            </section>
            <section class="card">
              <div class="k">会社</div>
              <div class="v name-value">{{ $selectedRow['company_name'] }}</div>
            </section>
            <section class="card">
              <div class="k">店舗</div>
              <div class="v name-value">{{ $selectedRow['store_name'] }}</div>
            </section>
            <section class="card">
              <div class="k">支給日</div>
              <div class="v">{{ $bonusPaymentDate }}</div>
            </section>
            <!-- <section class="card">
              <div class="k">雇用区分</div>
              <div class="v">{{ $selectedRow['division'] !== '' ? $selectedRow['division'] : 'N/A' }}</div>
            </section> -->
            <section class="card">
              <div class="k">振込額</div>
              <div class="v">{{ number_format($transferAmount, 0) }}</div>
            </section>
            <section class="card">
              <div class="k">賞与確定状態</div>

              @if ($bonusConfirmed)
              <div class="card-status-actions">
                <button class="btn btn-primary" type="button" id="confirm-btn">解除</button>
              </div>
              @else
              <div class="card-status-actions">
                <button class="btn btn-primary" type="button" id="confirm-btn">確定</button>
              </div>
              @endif
            </section>

          </div>
          <div class="actions">
            <button class="btn" type="button" id="edit-toggle-btn" @disabled($bonusConfirmed)>編集</button>
            <button class="btn" type="button" id="bonus-recalculate-btn" @disabled($bonusConfirmed)>再計算</button>
            <a class="btn" href="{{ route('admin.bonus.transfer-list', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId]) }}" target="_blank" rel="noopener noreferrer">振込一覧</a>
            <a class="btn" href="{{ route('admin.bonus.wage-ledger', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId]) }}" target="_blank" rel="noopener noreferrer">賃金台帳</a>
            <a class="btn" href="{{ route('admin.bonus.company-burden-print', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId]) }}" target="_blank" rel="noopener noreferrer">会社負担一覧</a>
          </div>
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

          $summary['transfer_amount_calc'] = is_numeric($summary['transfer_amount'] ?? null) ? (float) $summary['transfer_amount'] : 0.0;
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
          ['label' => '賞与税率', 'key' => 'bonus_tax', 'total' => false, 'readonly' => true],
          ['label' => '賞与額', 'key' => 'bonus_amo', 'total' => false],
          ['label' => '調整手当', 'key' => 'allowance_amo_5', 'total' => false],
          ['label' => '課税支給合計', 'key' => 'taxation_sum', 'total' => true],
          ['label' => '非課税支給合計', 'key' => 'not_taxation_sum', 'total' => true],
          ['label' => '支給合計', 'key' => 'supply_sum', 'total' => true],
          ],
          '控除' => [
          ['label' => '健康保険料', 'key' => 'kenpo', 'total' => false],
          ['label' => '介護保険料', 'key' => 'kaigo', 'total' => false],
          ['label' => '子ども支援金', 'key' => 'child_support_funds', 'total' => false],
          ['label' => '厚生年金保険料', 'key' => 'kounen', 'total' => false],
          ['label' => '雇用保険料', 'key' => 'koyou', 'total' => false],
          ['label' => '労保対象合計', 'key' => 'rouho_target_sum', 'total' => true, 'readonly' => true],
          ['label' => '所得税', 'key' => 'income_tax', 'total' => false],
          ['label' => '住民税', 'key' => 'resident_tax', 'total' => false],
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
                  <h3 class="section-title">{{ $title }}</h3>
                  <table class="kv">
                    @foreach ($items as $item)
                    <tr class="{{ !empty($item['total']) ? 'total-row' : '' }}">
                      <td>{{ $item['label'] }}</td>
                      <td>
                        @if ($item['key'] === 'transfer_amount_calc')
                        <span>{{ number_format($summary['transfer_amount_calc'], 0) }}</span>
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
                  <h3 class="section-title">{{ $title }}</h3>
                  <table class="kv">
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
          @php
          $bonusSheetRows = [];
          $bonusSheetRow = (array) ($selectedRow['summary'] ?? []);
          if ($bonusSheetRow !== []) {
          $bonusSheetRow['staff_name'] = (string) ($selectedRow['staff_name'] ?? '');
          $bonusSheetRow['section_name'] = (string) ($selectedRow['store_name'] ?? '');
          $bonusSheetRow['office_name'] = (string) ($selectedRow['company_name'] ?? '');
          $bonusSheetRow['tax_category'] = (string) (($selectedRow['staff_master']['tax_category'] ?? '') ?: ($bonusSheetRow['tax_category'] ?? ''));
          $bonusSheetRow['supply_month'] = (string) ($bonusSheetRow['supply_month'] ?? $selectedPaymentDate ?? '');
          $bonusSheetRows = [$bonusSheetRow];
          }
          @endphp
          @if (!empty($bonusSheetRows))
          <section class="panel" style="margin-top:16px;">
            @include('shared.payroll.payslip_item', [
            'rawRows' => $bonusSheetRows,
            'targetStaffName' => $selectedRow['staff_name'] ?? '',
            'storeName' => $selectedRow['store_name'] ?? '',
            'companyName' => $selectedRow['company_name'] ?? '',
            'targetTaxCategory' => $selectedRow['staff_master']['tax_amount'] ?? '',
            'isBonus' => true,
            ])
          </section>
          @endif
          @else
          <div class="empty">対象スタッフがいません。</div>
          @endif
        </main>
      </div>
    </section>
  </div>
  @include('admin_v2.work.bonus.page_script')
</body>

</html>
