<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TCPG SYSTEM - 給与計算</title>
  <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin_v2/payroll.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin_v2/payslip_item.css') }}">

  <style>
    .sales-inline-table {
      table-layout: fixed;
    }

    .edit-input {
      width: 82px;
    }

    .kv tr.attendance-group-end td {
      border-bottom: 2px solid #4a5a75;
    }
  </style>
</head>

<body>
  @include('admin_v2.shared.global_nav')
  @php require resource_path('views/admin_v2/work/payroll/page_state_runtime.php'); @endphp
  <div class="wrap">
    <div class="top">
      <div class="title">TCPG SYSTEM 給与計算</div>
    </div>
    <div id="status-message" class="status" style="display:none;"></div>
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

        <label for="report_type">帳票</label>
        <select id="report_type" name="report_type">
          <option value=""></option>
          <option value="transfer-list">振込先一覧</option>
          <option value="wage-ledger">賃金台帳</option>
          <option value="personal-wage-ledger">個人賃金台帳</option>
          <option value="outsource-reward-ledger">委託報酬</option>
          <option value="company-burden">会社負担一覧</option>
          <option value="outsource-menu-sales">委託メニュー売上</option>
          <option value="home-visit-sales-detail">往診売上詳細</option>
          <option value="home-visit-sales">往診個人別売上</option>
        </select>

        <button class="btn btn-primary" type="submit">表示</button>
        <button class="btn" type="button" id="payroll-create-btn">給与データ作成</button>
        <button class="btn" type="button" id="payroll-sales-btn">売上集計</button>
        @if ($attendanceLinkMonth !== '')
        <a class="btn" href="{{ route('admin.attendance.index', ['month' => $attendanceLinkMonth, 'company_id' => $selectedCompanyId, 'staff_id' => $selectedStaffId]) }}">勤怠管理へ</a>
        @else
        <span class="btn" style="opacity:.45; cursor:not-allowed;" title="対応する勤怠月がありません">勤怠管理へ</span>
        @endif
      </form>

      <div class="create-inline" id="payroll-create-inline" hidden>
        <div class="create-inline-head">
          <div class="create-inline-title">給与データ作成</div>
          <div class="create-inline-actions">
            <button type="button" class="btn" id="payroll-create-select-all-btn">全選択</button>
            <button type="button" class="btn" id="payroll-create-clear-btn">解除</button>
            <button type="button" class="btn" id="payroll-create-close-btn">閉じる</button>
          </div>
        </div>
        <div class="create-inline-body">
          <div class="create-inline-row">
            <label for="payroll-create-payment-date">作成日</label>
            <input id="payroll-create-payment-date" type="date">
            <button type="button" class="btn" id="payroll-create-show-btn">表示</button>
            <button type="button" class="btn btn-primary" id="payroll-create-submit-btn">作成</button>
            <button type="button" class="btn" id="payroll-delete-submit-btn">削除</button>
          </div>
          <div class="create-inline-note">未作成と作成済を一覧に表示します。作成は未作成のみ、削除は作成済のみ対象です。</div>
          <div class="create-inline-list" id="payroll-create-list"></div>
          <div class="create-inline-empty" id="payroll-create-empty" hidden>対象者はありません。</div>
        </div>
      </div>

      <div class="create-inline sales-inline" id="payroll-sales-inline" hidden>
        <div class="create-inline-head">
          <div class="create-inline-title">売上集計</div>
          <div class="create-inline-actions">
            <button type="button" class="btn btn-primary" id="payroll-sales-reflect-all-btn">全員取込</button>
            <button type="button" class="btn" id="payroll-sales-close-btn">閉じる</button>
          </div>
        </div>
        <div class="create-inline-body">
          <div class="create-inline-note">表示中の給与データへ、支給月の前月の日報売上を反映します。</div>
          <div class="sales-inline-table-wrap">
            <table class="sales-inline-table">
              <colgroup>
                <col style="width: 35px;">
                <col style="width: 32px;">
                <col style="width: 25px;">
                <col style="width: 20px;">
                <col style="width: 20px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
                <col style="width: 30px;">
              </colgroup>
              <thead>
                <tr>
                  <th class="sales-name-cell">スタッフ</th>
                  <th class="sales-total-cell">合計</th>
                  <th>操作</th>
                  <th>人数</th>
                  <th>距離</th>
                  <th>北在家</th>
                  <th>東加古川</th>
                  <th>播磨町</th>
                  <th>さくら鍼灸</th>
                  <th>織田鍼灸</th>
                  <th>宮本鍼灸</th>
                  <th>横井鍼灸</th>
                  <th>自費</th>
                  <th>未収金</th>
                </tr>
              </thead>
              <tbody id="payroll-sales-list"></tbody>
            </table>
          </div>
          <div class="create-inline-empty" id="payroll-sales-empty" hidden>対象データはありません。</div>
        </div>
      </div>

      <div class="count">対象者件数 {{ count($rows) }}</div>
      <div class="content">
        <aside class="staff-list">
          @php
          $currentStaffId = $selectedRow['staff_id'] ?? null;
          @endphp
          @forelse ($rows as $row)
          @php
          $isActive = $currentStaffId !== null && $currentStaffId === ($row['staff_id'] ?? null);
          $payrollConfirmed = ((int) (($row['summary']['edit_lock'] ?? 0))) === 1;
          @endphp
          <a class="staff-item {{ $isActive ? 'active' : '' }} {{ $payrollConfirmed ? 'payroll-confirmed' : 'payroll-unconfirmed' }}" href="{{ route('admin.payroll.index', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $row['staff_id']]) }}">
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
        <main class="main" id="payroll-main">
          @php
          if (!isset($selectedRow) || $selectedRow === null) {
          $selectedRow = count($rows) > 0 ? $rows[0] : null;
          }
          @endphp
          @if ($selectedRow)
          <div class="top-cards">
            @php
            $cardSummary = (array) (($selectedRow['summary'] ?? []) ?: []);

            $cardPaymentDate = $selectedPaymentDate !== ''
            ? str_replace('-', '/', $selectedPaymentDate)
            : '-';

            if (!isset($paymentDate) || $cardPaymentDate === '-' || $cardPaymentDate === '') {
            $raw = trim((string) ($cardSummary['supply_month'] ?? ''));
            if ($raw !== '') {
            $ts = strtotime($raw);
            $cardPaymentDate = $ts !== false ? date('Y/m/d', $ts) : ((preg_split('/[ T]/', $raw)[0] ?? $raw));
            }
            }
            $payrollConfirmed = ((int) ($cardSummary['edit_lock'] ?? 0)) === 1;
            $attendanceConfirmed = ((int) ($cardSummary['attendance_checked'] ?? 0)) === 1;
            $attendanceRecordExists = (bool) ($selectedRow['attendance_record_exists'] ?? false);
            @endphp
            <section class="card">
              <div class="k">対象者</div>
              <div class="v name-value">{{ $selectedRow['staff_id'] }} {{ $selectedRow['staff_name'] }}</div>
            </section>
            <section class="card">
              <div class="k">会社名</div>
              <div class="v name-value">{{ $selectedRow['company_name'] }}</div>
            </section>
            <section class="card">
              <div class="k">店舗名</div>
              <div class="v name-value">{{ $selectedRow['store_name'] }}</div>
            </section>
            <section class="card">
              <div class="k">支給日</div>
              <div class="v">{{ $cardPaymentDate }}</div>
            </section>
            <section class="card">
              <div class="k">雇用区分</div>
              <div class="v">{{ $selectedRow['division'] !== '' ? $selectedRow['division'] : 'N/A' }}</div>
            </section>
            <section class="card">
              <div class="k">給与確定状態</div>
              @if ($payrollConfirmed)
              <div class="card-status-actions">
                <button class="btn btn-primary" type="button" id="confirm-btn">解除</button>
              </div>
              @else
              @if (!$attendanceRecordExists || $attendanceConfirmed)
              <div class="card-status-actions">
                <button class="btn btn-primary" type="button" id="confirm-btn">確定</button>
              </div>
              @else
              <div class="card-status-note" id="attendance-confirm-note">勤怠未確定</div>
              @endif
              @endif
            </section>
          </div>
          <div class="actions">
            <button class="btn" type="button" id="edit-toggle-btn">編集</button>
            <button class="btn" type="button" id="attendance-reflect-btn">勤怠反映</button>
            <button class="btn" type="button" id="recalc-btn">再計算</button>
            <button class="btn" type="button" id="calc-home-visit-allowance-btn">往診手当</button>
            <button class="btn" type="button" id="calc-overtime-deduction-btn">割増・控除</button>
            <button class="btn" type="button" id="calc-koyou-btn">雇用保険</button>
            <button class="btn" type="button" id="calc-income-tax-btn">所得税</button>
          </div>
          <div class="sections" style="display:flex;flex-wrap:nowrap;gap:6px;align-items:flex-start;">
            <div style="flex:1.2 1 0;min-width:0;display:grid;gap:8px;">
              @foreach($attendance as $title => $items)
              <section class="card">
                <h2 class="section-title">{!! $title !!}</h2>
                <table class="kv">
                  @foreach($items as $it)
                  @php
                  [$k, $m] = $it;
                  $val = $m === -1 ? $t($k) : $n($k, $m);
                  $delta = ($k === 'holiday_true_num' && $m !== -1) ? $deltaFrom($k, $m) : '';
                  $isTotalRow = in_array($k, $totalRowKeys, true);
                  $rowClass = ($isTotalRow || in_array($k, $boldOnlyRowKeys ?? [], true)) ? 'total-row' : '';
                  // 日数系(欠勤日数)・時間系(遅刻早退時間)の区切りを太い下線で示す（2026-08-17）。
                  if (in_array($k, ['absence_num', 'late_time'], true)) {
                  $rowClass = trim($rowClass . ' attendance-group-end');
                  }
                  @endphp
                  <tr class="{{ $rowClass }}">
                    <td>{!! $l($k) !!}</td>
                    <td><span class="view-only">{{ $val }}@if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span>@if(!$isTotalRow)<input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}" data-key="{{ $k }}">@endif</td>
                  </tr>
                  @endforeach
                </table>
              </section>
              @endforeach
              @foreach($sales as $title => $items)
              <section class="card">
                <h2 class="section-title">{!! $title !!}</h2>
                <table class="kv">
                  @foreach($items as $it)
                  @php
                  [$k, $m] = $it;
                  $val = $m === -1 ? $t($k) : $n($k, $m);
                  $delta = ($k === 'holiday_true_num' && $m !== -1) ? $deltaFrom($k, $m) : '';
                  $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : '';
                  @endphp
                  <tr class="{{ $rowClass }}">
                    <td>{!! $l($k) !!}</td>
                    <td><span class="view-only">{{ $val }}@if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span>@if(!in_array($k, $totalRowKeys, true))<input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}" data-key="{{ $k }}">@endif</td>
                  </tr>
                  @endforeach
                </table>
              </section>
              @endforeach
            </div>
            <div style="flex:1.2 1 0;min-width:0;display:grid;gap:8px;">
              @foreach($supply as $title=>$items)
              <section class="card">
                <h2 class="section-title">{!! $title !!}</h2>
                <table class="kv">
                  @foreach($items as $it) @php [$k,$m]=$it; $val = $m===-1 ? $t($k) : $n($k,$m); $delta = $m===-1 ? '' : $deltaFrom($k,$m); $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; @endphp
                  <tr class="{{ $rowClass }}">
                    <td>{!! $l($k) !!}</td>
                    <td><span class="view-only">{{ $val }}@if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span>@if(!in_array($k, $totalRowKeys, true))<input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}" data-key="{{ $k }}">@endif</td>
                  </tr>
                  @endforeach
                </table>
              </section>
              @endforeach
            </div>
            <div style="flex:1.2 1 0;min-width:0;display:grid;gap:8px;">
              @foreach($mid as $title=>$items)
              <section class="card">
                <h2 class="section-title">{!! $title !!}</h2>
                <table class="kv">
                  @foreach($items as $it) @php [$k,$m]=$it; $val = $m===-1 ? $t($k) : $n($k,$m); $delta = $m===-1 ? '' : $deltaFrom($k,$m); $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; $isReadOnlyCalc = in_array($k, ['transfer_amount_calc'], true); @endphp
                  <tr class="{{ $rowClass }}">
                    <td>{!! $l($k) !!}</td>
                    <td><span class="view-only">{{ $val }}@if($delta !== '') <small style="display:block;color:#c15353;font-weight:700;">{{ $delta }}</small>@endif</span>@if(!in_array($k, $totalRowKeys, true) && !$isReadOnlyCalc)<input class="edit-input edit-only {{ $m===-1 ? 'text' : '' }}" type="text" value="{{ $val }}" data-key="{{ $k }}">@endif</td>
                  </tr>
                  @endforeach
                </table>
              </section>
              @endforeach
            </div>
            <div style="flex:1 1 0;min-width:0;display:grid;gap:8px;">
              @foreach($rightA as $title=>$items)
              @php
              $source = $summary;
              if ($title === $baseInfoTitle) {
              $source = $baseInfoView;
              } elseif ($title === $referenceTitle) {
              $source = array_merge($summary, $referenceView ?? []);
              }
              @endphp
              <section class="card readonly-card">
                <h2 class="section-title">{!! $title !!}</h2>
                <table class="kv">
                  @foreach($items as $it) @php
                  [$k,$m]=$it;
                  $val = $m===-1 ? $tFrom($source,$k) : $nFrom($source,$k,$m);
                  if (in_array($k, ['social_join', 'employment_join'], true)) {
                  $val = ((int)$val === 1) ? '有' : '無';
                  }
                  if ($k === 'yukyu_month') {
                  $v = trim((string) $val);
                  if ($v !== '' && is_numeric($v)) {
                  $val = ((string) ((int) $v)) . '月';
                  }
                  }
                  @endphp
                  @if ($k === 'memo')
                  <tr>
                    <td colspan="2" class="memo-wrap">
                      <div class="memo-label">{!! $l($k) !!}</div>
                      <div class="memo-value" title="{{ $val }}">{{ $val }}</div>
                    </td>
                  </tr>
                  @else
                  @php $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; @endphp
                  <tr class="{{ $rowClass }}">
                    <td>{!! $l($k) !!}</td>
                    <td>{{ $val }}</td>
                  </tr>
                  @endif
                  @endforeach
                </table>
              </section>
              @endforeach
            </div>
            <div style="flex:1 1 0;min-width:0;display:grid;gap:8px;">
              @foreach($rightB as $title=>$items)
              @php
              $source = $title === $masterTitle
              ? $kihonMasterView
              : ($title === $shahoTitle ? $shahoView : $residentView);
              @endphp
              <section class="card readonly-card">
                <h2 class="section-title">{!! $title !!}</h2>
                <table class="kv">
                  @foreach($items as $it) @php
                  [$k,$m]=$it;
                  $val = $m===-1 ? $tFrom($source,$k) : $nFrom($source,$k,$m);
                  @endphp
                  @php $rowClass = in_array($k, $totalRowKeys, true) ? 'total-row' : ''; @endphp
                  <tr class="{{ $rowClass }}">
                    <td>{!! $l($k) !!}</td>
                    <td>{{ $val }}</td>
                  </tr>
                  @endforeach
                </table>
              </section>
              @endforeach
            </div>
          </div>
          <div class="summary-grid">
            <section class="memo-card">
              <div class="memo-label">{!! $l('kyuyo_memo') !!}</div>
              <div class="memo-value" title="{{ $t('kyuyo_memo') }}">{{ $t('kyuyo_memo') }}</div>
              <textarea class="memo-input edit-input text" data-key="kyuyo_memo">{{ $t('kyuyo_memo') }}</textarea>
            </section>
            <section class="total-card">
              <div class="k">支給合計</div>
              <div class="v">{{ number_format($supplySum, 0) }}</div>
            </section>
            <section class="total-card">
              <div class="k">その他合計</div>
              <div class="v">{{ number_format($otherSum, 0) }}</div>
            </section>
            <section class="total-card">
              <div class="k">控除合計</div>
              <div class="v">{{ number_format($deductionSum, 0) }}</div>
            </section>
            <section class="total-card">
              <div class="k">差引支給額</div>
              <div class="v">{{ number_format($netPay, 0) }}</div>
            </section>
          </div>
          @php
          $payrollSheetRows = [];
          $sheetRow = (array) ($selectedRow['summary'] ?? []);
          if ($sheetRow !== []) {
          $sheetRow['staff_name'] = (string) ($selectedRow['staff_name'] ?? '');
          $sheetRow['section_name'] = (string) ($selectedRow['store_name'] ?? '');
          $sheetRow['office_name'] = (string) ($selectedRow['company_name'] ?? '');
          $sheetRow['tax_category'] = (string) (($selectedRow['staff_master']['tax_category'] ?? '') ?: ($sheetRow['tax_category'] ?? ''));
          $sheetRow['supply_month'] = (string) ($sheetRow['supply_month'] ?? $selectedPaymentDate ?? '');
          $payrollSheetRows = [$sheetRow];
          }
          @endphp
          @if (!empty($payrollSheetRows))
          <section class="panel" style="margin-top:16px;">
            @include('shared.payroll.payslip_item', [
            'rawRows' => $payrollSheetRows,
            'targetStaffName' => $selectedRow['staff_name'] ?? '',
            'storeName' => $selectedRow['store_name'] ?? '',
            'companyName' => $selectedRow['company_name'] ?? '',
            'targetTaxCategory' => $selectedRow['staff_master']['tax_amount'] ?? '',
            'isBonus' => false,
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
  @include('admin_v2.work.payroll.page_script')

</body>

</html>
