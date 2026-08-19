@php
$kihonFieldGroups = [
'基本給' => ['monthly_salary', 'hourly_pay', 'hourly_salary', 'executive_remu'],
'手当' => ['position_allow', 'duties_allow', 'qualification_allow', 'claim_allow', 'traffic_pay', 'adjustment_add', 'rent_subsidies', 'rent_pay', 'fixed_overtime'],
'控除' => ['adjustment_pay'],
];

$kihonLabels = [
'decision_date' => '決定日',
'monthly_salary' => '月給',
'hourly_pay' => '時給換算',
'hourly_salary' => '時給',
'executive_remu' => '役員報酬',
'position_allow' => '役職手当',
'duties_allow' => '職務手当',
'qualification_allow' => '資格手当',
'claim_allow' => '請求手当',
'traffic_pay' => '交通費',
'adjustment_add' => '調整手当',
'rent_subsidies' => '家族手当',
'rent_pay' => '家賃補助',
'adjustment_pay' => '欠勤控除',
'fixed_overtime' => '固定残業手当',
];

$formatKihonNumber = function ($value): string {
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

$formatDecisionDate = function (array $row, int $fallback): string {
$raw = trim((string)($row['_raw_decision_date'] ?? ''));
if ($raw !== '') {
$timestamp = strtotime($raw);
if ($timestamp !== false) {
return date('Y年n月j日決定', $timestamp);
}
return $raw;
}
return '履歴 ' . $fallback;
};
@endphp

<div class="related-card">
  <div class="related-header">
    <h3>給与マスタ</h3>
    <span>{{ number_format(count($kihonRows ?? [])) }} 件</span>
  </div>

  <div class="staff-tab-panels">
    @if(session('status'))
    <div class="status">{{ session('status') }}</div>
    @endif

    <div class="master-create" id="master-create">
      <button type="button" class="btn-secondary master-toggle-btn" data-toggle-target="master-create">＋新規登録</button>
      <form method="post" action="{{ route('admin.master.staff.kihon.store') }}" class="info-block payroll-master-block master-toggle-body">
        @csrf
        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
        <input type="hidden" name="q" value="{{ $keyword }}">
        <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
        <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

        <div class="info-block-title">新規登録</div>
        <div class="payroll-master-group-grid">
          <label class="detail-field detail-field-compact">
            <span>{{ $kihonLabels['decision_date'] }}</span>
            <input type="date" name="decision_date" required>
          </label>
        </div>
        @foreach($kihonFieldGroups as $groupLabel => $groupFields)
        <div class="payroll-master-group">
          <div class="payroll-master-group-label">{{ $groupLabel }}</div>
          <div class="payroll-master-group-grid">
            @foreach($groupFields as $field)
            <label class="detail-field detail-field-compact">
              <span>{{ $kihonLabels[$field] ?? $field }}</span>
              <input type="text" name="{{ $field }}">
            </label>
            @endforeach
          </div>
        </div>
        @endforeach
        <div class="detail-actions">
          <button type="submit" class="btn-primary">登録</button>
        </div>
      </form>
    </div>

    @if(($kihonRows ?? []) !== [])
    @foreach($kihonRows as $index => $row)
    @php
    $isLatest = $index === 0;
    $title = $formatDecisionDate($row, $index + 1);
    @endphp

    @if($isLatest)
    <form method="post" action="{{ route('admin.master.staff.kihon.update') }}" class="info-block payroll-master-block master-editable" id="payroll-master-latest">
      @csrf
      <input type="hidden" name="kihon_no" value="{{ $row['kihon_no'] ?? '' }}">
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="payroll-master-title-row">
        <span class="payroll-master-title">{{ $title }}</span>
        <span class="payroll-master-badge">最新</span>
        <button type="button" class="btn-secondary master-edit-btn" data-edit-target="payroll-master-latest">編集</button>
      </div>
      <div class="payroll-master-group-grid">
        <label class="detail-field detail-field-compact">
          <span>{{ $kihonLabels['decision_date'] }}</span>
          <div class="payroll-master-value">{{ str_replace('-', '/', (string) ($row['_raw_decision_date'] ?? '---')) }}</div>
          <input type="date" name="decision_date" value="{{ $row['_raw_decision_date'] ?? '' }}" required>
        </label>
      </div>
      @foreach($kihonFieldGroups as $groupLabel => $groupFields)
      <div class="payroll-master-group">
        <div class="payroll-master-group-label">{{ $groupLabel }}</div>
        <div class="payroll-master-group-grid">
          @foreach($groupFields as $field)
          <label class="detail-field detail-field-compact">
            <span>{{ $kihonLabels[$field] ?? $field }}</span>
            <div class="payroll-master-value">{{ $formatKihonNumber($row[$field] ?? '') !== '' ? $formatKihonNumber($row[$field] ?? '') : '---' }}</div>
            <input type="text" name="{{ $field }}" value="{{ $formatKihonNumber($row[$field] ?? '') }}">
          </label>
          @endforeach
        </div>
      </div>
      @endforeach
      <div class="detail-actions master-edit-actions">
        <button type="submit" class="btn-primary">保存</button>
        <button type="submit" class="btn-secondary" formaction="{{ route('admin.master.staff.kihon.delete') }}" onclick="return confirm('この給与マスタ履歴を削除します。');">削除</button>
      </div>
    </form>
    @else
    <div class="info-block payroll-master-block payroll-master-history">
      <div class="payroll-master-title-row">
        <span class="payroll-master-title">{{ $title }}</span>
      </div>
      @foreach($kihonFieldGroups as $groupLabel => $groupFields)
      <div class="payroll-master-group">
        <div class="payroll-master-group-label">{{ $groupLabel }}</div>
        <div class="payroll-master-group-grid">
          @foreach($groupFields as $field)
          <label class="detail-field detail-field-compact">
            <span>{{ $kihonLabels[$field] ?? $field }}</span>
            <div class="payroll-master-value">{{ $formatKihonNumber($row[$field] ?? '') !== '' ? $formatKihonNumber($row[$field] ?? '') : '---' }}</div>
          </label>
          @endforeach
        </div>
      </div>
      @endforeach
    </div>
    @endif
    @endforeach
    @else
    <div class="staff-empty">データがありません</div>
    @endif
  </div>
</div>
