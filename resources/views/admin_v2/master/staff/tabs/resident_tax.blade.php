@php
$residentMonths = [
'resident_tax1' => '06月　',
'resident_tax2' => '07月　',
'resident_tax3' => '08月　',
'resident_tax4' => '09月　',
'resident_tax5' => '10月　',
'resident_tax6' => '11月　',
'resident_tax7' => '12月　',
'resident_tax8' => '01月　',
'resident_tax9' => '02月　',
'resident_tax10' => '03月　',
'resident_tax11' => '04月　',
'resident_tax12' => '05月　',
];

$residentTotal = function (array $row) use ($residentMonths): float {
$total = 0.0;
foreach (array_keys($residentMonths) as $field) {
$value = str_replace(',', '', (string) ($row[$field] ?? ''));
if (is_numeric($value)) {
$total += (float) $value;
}
}
return $total;
};

$formatResidentNumber = function ($value): string {
$text = trim((string) $value);
if ($text === '') {
return '';
}

$normalized = str_replace(',', '', $text);
if (!is_numeric($normalized)) {
return $text;
}

$formatted = number_format((float) $normalized, 4);
return rtrim(rtrim($formatted, '0'), '.');
};

$formatTargetMonth = function (array $row, int $fallback): string {
$raw = trim((string)($row['_raw_target_month'] ?? ''));
if ($raw !== '') {
$timestamp = strtotime($raw);
if ($timestamp !== false) {
return date('Y年n月分', $timestamp);
}
return $raw;
}
return '履歴 ' . $fallback;
};

$submissionLabel = function (string $value) use ($residentSubmissionOptions): string {
if ($value === '') {
return '';
}
foreach (($residentSubmissionOptions ?? []) as $option) {
if ((string) ($option['value'] ?? '') === $value) {
return (string) ($option['label'] ?? $value);
}
}
return $value;
};
@endphp

<style>
  .resident-tax-block .resident-tax-submission-field {
    grid-column: span 2;
    grid-template-columns: max-content minmax(220px, 300px);
  }

  .resident-tax-block .resident-tax-submission-field select {
    width: 100%;
    max-width: none;
  }

  .resident-tax-memo {
    min-height: 0 !important;
    height: 4.8em;
    resize: vertical;
  }

  .resident-tax-memo-value {
    width: 100%;
    min-height: 4.8em;
    box-sizing: border-box;
  }

  .resident-tax-month-box {
    grid-column: 1 / -1;
    width: fit-content;
    max-width: 100%;
    padding: 8px 10px 10px;
    border: 1px solid #c8d9f0;
    border-radius: 8px;
    background: #f7fbff;
    overflow-x: auto;
  }

  .resident-tax-month-title {
    margin-bottom: 8px;
    color: #315f98;
    font-size: 13px;
    font-weight: 800;
  }

  .resident-tax-month-blocks {
    display: flex;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 6px 10px;
  }

  .resident-tax-month-box .detail-field {
    grid-template-columns: max-content 86px;
  }

  .resident-tax-month-box input[type="text"] {
    width: 100%;
    max-width: none;
  }
</style>

<div class="related-card">
  <div class="related-header">
    <h3>住民税</h3>
    <span>{{ number_format(count($residentRows ?? [])) }} 件</span>
  </div>

  <div class="staff-tab-panels">
    @if(session('status'))
    <div class="status">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('admin.master.staff.resident.store') }}" class="info-block resident-tax-block">
      @csrf
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="info-block-title">新規登録</div>
      <div class="info-block-grid">
        <label class="detail-field detail-field-compact">
          <span>住民税対象月</span>
          <input type="month" name="target_month" required>
        </label>
        <label class="detail-field detail-field-compact">
          <span>宛名番号　</span>
          <input type="text" name="addressee_no">
        </label>
        <label class="detail-field detail-field-compact resident-tax-submission-field">
          <span>住民税提出先</span>
          <select name="submission">
            <option value=""></option>
            @foreach(($residentSubmissionOptions ?? []) as $option)
            <option value="{{ $option['value'] ?? '' }}">{{ $option['label'] ?? ($option['value'] ?? '') }}</option>
            @endforeach
          </select>
        </label>
        <div class="resident-tax-month-box">
          <div class="resident-tax-month-title">月別住民税</div>
          <div class="resident-tax-month-blocks">
            @foreach($residentMonths as $field => $label)
            <label class="detail-field detail-field-compact">
              <span>{{ $label }}</span>
              <input type="text" name="{{ $field }}" @if($field==='resident_tax2' ) data-resident-tax-fill-source="1" @endif>
            </label>
            @endforeach
          </div>
        </div>
        <label class="detail-field detail-field-wide detail-field-textarea detail-field-textarea-no-label">
          <textarea name="memo" rows="3" class="resident-tax-memo" placeholder="備考"></textarea>
        </label>
      </div>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">登録</button>
      </div>
    </form>

    @if(($residentRows ?? []) !== [])
    @foreach($residentRows as $index => $row)
    @php
    $isLatest = $index === 0;
    $annualTotal = array_key_exists('_annual_total', $row) ? (float) $row['_annual_total'] : $residentTotal($row);
    $title = $formatTargetMonth($row, $index + 1);
    $memoValue = trim((string)($row['memo'] ?? ''));
    @endphp

    @if($isLatest)
    <form method="post" action="{{ route('admin.master.staff.resident.update') }}" class="info-block resident-tax-block">
      @csrf
      <input type="hidden" name="resident_no" value="{{ $row['resident_no'] ?? '' }}">
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="resident-tax-title-row">
        <span class="resident-tax-title">{{ $title }}</span>
        <span class="resident-tax-badge">最新</span>
      </div>
      <div class="info-block-grid">
        <label class="detail-field detail-field-compact">
          <span>住民税対象月</span>
          <input type="month" name="target_month" value="{{ substr((string)($row['_raw_target_month'] ?? ''), 0, 7) }}" required>
        </label>
        <label class="detail-field detail-field-compact">
          <span>年間合計</span>
          <div class="resident-tax-value">{{ number_format($annualTotal) }}</div>
        </label>
        <label class="detail-field detail-field-compact">
          <span>宛名番号　</span>
          <input type="text" name="addressee_no" value="{{ $row['addressee_no'] ?? '' }}">
        </label>
        @php
        $rowSubmission = trim((string) ($row['submission'] ?? ''));
        @endphp
        <label class="detail-field detail-field-compact resident-tax-submission-field">
          <span>住民税提出先</span>
          <select name="submission">
            <option value=""></option>
            @foreach(($residentSubmissionOptions ?? []) as $option)
            <option value="{{ $option['value'] ?? '' }}" @selected($rowSubmission !== '' && $rowSubmission === (string) ($option['value'] ?? ''))>{{ $option['label'] ?? ($option['value'] ?? '') }}</option>
            @endforeach
          </select>
        </label>
        <div class="resident-tax-month-box">
          <div class="resident-tax-month-title">月別住民税</div>
          <div class="resident-tax-month-blocks">
            @foreach($residentMonths as $field => $label)
            <label class="detail-field detail-field-compact">
              <span>{{ $label }}</span>
              <input type="text" name="{{ $field }}" value="{{ $formatResidentNumber($row[$field] ?? '') }}" @if($field==='resident_tax2' ) data-resident-tax-fill-source="1" @endif>
            </label>
            @endforeach
          </div>
        </div>
        <label class="detail-field detail-field-wide detail-field-textarea detail-field-textarea-no-label">
          <textarea name="memo" rows="3" class="resident-tax-memo" placeholder="備考">{{ $memoValue }}</textarea>
        </label>
      </div>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">保存</button>
        <button type="submit" class="btn-secondary" formaction="{{ route('admin.master.staff.resident.delete') }}" onclick="return confirm('この住民税履歴を削除します。');">削除</button>
      </div>
    </form>
    @else
    <div class="info-block resident-tax-block resident-tax-history">
      <div class="resident-tax-title-row">
        <span class="resident-tax-title">{{ $title }}</span>
      </div>
      <div class="info-block-grid">
        <label class="detail-field detail-field-compact">
          <span>年間合計</span>
          <div class="resident-tax-value">{{ number_format($annualTotal) }}</div>
        </label>
        <label class="detail-field detail-field-compact">
          <span>宛名番号</span>
          <div class="resident-tax-value">{{ ($row['addressee_no'] ?? '') !== '' ? $row['addressee_no'] : '---' }}</div>
        </label>
        <label class="detail-field detail-field-compact">
          <span>住民税提出先</span>
          <div class="resident-tax-value">{{ $submissionLabel(trim((string) ($row['submission'] ?? ''))) !== '' ? $submissionLabel(trim((string) ($row['submission'] ?? ''))) : '---' }}</div>
        </label>
        <div class="resident-tax-month-box">
          <div class="resident-tax-month-title">月別住民税</div>
          <div class="resident-tax-month-blocks">
            @foreach($residentMonths as $field => $label)
            <label class="detail-field detail-field-compact">
              <span>{{ $label }}</span>
              <div class="resident-tax-value">{{ $formatResidentNumber($row[$field] ?? '') !== '' ? $formatResidentNumber($row[$field] ?? '') : '---' }}</div>
            </label>
            @endforeach
          </div>
        </div>
        <label class="detail-field detail-field-wide detail-field-textarea">
          <span>備考</span>
          <div class="detail-value detail-value-textarea resident-tax-memo-value {{ $memoValue === '' ? 'detail-value-empty' : '' }}">{{ $memoValue !== '' ? $memoValue : '---' }}</div>
        </label>
      </div>
    </div>
    @endif
    @endforeach
    @else
    <div class="staff-empty">データがありません</div>
    @endif
  </div>
</div>

<script>
  document.querySelectorAll('[data-resident-tax-fill-source="1"]').forEach(function(source) {
    source.addEventListener('change', function() {
      var form = source.closest('form');
      if (!form || source.value === '') return;

      for (var i = 3; i <= 12; i += 1) {
        var target = form.querySelector('[name="resident_tax' + i + '"]');
        if (target) {
          target.value = source.value;
        }
      }
    });
  });
</script>
