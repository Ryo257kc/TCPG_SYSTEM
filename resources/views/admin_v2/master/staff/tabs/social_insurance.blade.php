@php
$shahoFields = [
'kenpo_monthly_amo' => '健保標準報酬月額',
'kounen_monthly_amo' => '厚年標準報酬月額',
'kenpo_toukyu' => '健保等級　　　　',
'kounen_toukyu' => '厚年等級　　　　',
];

$formatShahoNumber = function ($value): string {
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

$shahoIdValue = function (array $row): string {
foreach (['staff_shou_id', 'staff_shou_no', 'id'] as $column) {
$value = trim((string) ($row[$column] ?? ''));
if ($value !== '') {
return $value;
}
}
return '';
};

$formatApplyMonth = function (array $row, int $fallback): string {
$raw = trim((string)($row['_raw_raise_year'] ?? ''));
if ($raw !== '') {
$timestamp = strtotime($raw);
if ($timestamp !== false) {
return date('Y年n月適用', $timestamp);
}
return $raw;
}
return '履歴 ' . $fallback;
};
@endphp

<div class="related-card">
  <div class="related-header">
    <h3>社保・雇保</h3>
    <span>{{ number_format(count($shahoRows ?? [])) }} 件</span>
  </div>

  <div class="staff-tab-panels">
    @if(session('status'))
    <div class="status">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('admin.master.staff.insurance.update') }}" class="info-block social-insurance-block">
      @csrf
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="info-block-title">社保・雇保 基本情報</div>
      <div class="info-block-grid">
        <div class="detail-pair">
          <label class="detail-field detail-field-compact">
            <span>雇保加入</span>
            <div class="checkbox-line"><input type="checkbox" name="koyou" value="1" @checked((string)($selectedRow['koyou'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['koyou'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 加入</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>社保加入</span>
            <div class="checkbox-line"><input type="checkbox" name="syaho" value="1" @checked((string)($selectedRow['syaho'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['syaho'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 加入</div>
          </label>
        </div>
        <div class="detail-pair">
          <label class="detail-field detail-field-compact">
            <span>雇保加入日</span>
            <input type="date" name="koyou_date" value="{{ $selectedRow['_raw_koyou_date'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>社保加入日</span>
            <input type="date" name="syaho_date" value="{{ $selectedRow['_raw_syaho_date'] ?? '' }}">
          </label>
        </div>
        <div class="detail-pair">
          <label class="detail-field detail-field-compact">
            <span>健康保険番号</span>
            <input type="text" name="syaho_num" value="{{ $selectedRow['syaho_num'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>雇用保険番号</span>
            <input type="text" name="koyou_num" value="{{ $selectedRow['koyou_num'] ?? '' }}">
          </label>
        </div>
        <div class="detail-pair">
          <label class="detail-field detail-field-compact">
            <span>健保被保険者番号</span>
            <input type="text" name="syaho_seiri_num" value="{{ $selectedRow['syaho_seiri_num'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>基礎年金番号　　</span>
            <input type="text" name="kiso_nenkin_num" value="{{ $selectedRow['kiso_nenkin_num'] ?? '' }}">
          </label>
        </div>
      </div>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">保存</button>
      </div>
    </form>

    <form method="post" action="{{ route('admin.master.staff.shaho.store') }}" class="info-block social-insurance-block">
      @csrf
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="info-block-title">新規登録</div>
      <div class="info-block-grid">
        <label class="detail-field detail-field-compact">
          <span>適用年月　　　　</span>
          <input type="month" name="raise_year" required>
        </label>
        @foreach($shahoFields as $field => $label)
        <label class="detail-field detail-field-compact">
          <span>{{ $label }}</span>
          <input type="text" name="{{ $field }}">
        </label>
        @endforeach
      </div>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">登録</button>
      </div>
    </form>

    @if(($shahoRows ?? []) !== [])
    @foreach($shahoRows as $index => $row)
    @php
    $isLatest = $index === 0;
    $title = $formatApplyMonth($row, $index + 1);
    $idValue = $shahoIdValue($row);
    @endphp

    @if($isLatest)
    <form method="post" action="{{ route('admin.master.staff.shaho.update') }}" class="info-block social-insurance-block">
      @csrf
      <input type="hidden" name="staff_shou_id" value="{{ $idValue }}">
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="social-insurance-title-row">
        <span class="social-insurance-title">{{ $title }}</span>
        <span class="social-insurance-badge">最新</span>
      </div>
      <div class="info-block-grid">
        <label class="detail-field detail-field-compact">
          <span>適用年月　　　　</span>
          <input type="month" name="raise_year" value="{{ substr((string)($row['_raw_raise_year'] ?? ''), 0, 7) }}" required>
        </label>
        @foreach($shahoFields as $field => $label)
        <label class="detail-field detail-field-compact">
          <span>{{ $label }}</span>
          <input type="text" name="{{ $field }}" value="{{ $formatShahoNumber($row[$field] ?? '') }}">
        </label>
        @endforeach
      </div>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">保存</button>
        <button type="submit" class="btn-secondary" formaction="{{ route('admin.master.staff.shaho.delete') }}" onclick="return confirm('この社保履歴を削除します。');">削除</button>
      </div>
    </form>
    @else
    <div class="info-block social-insurance-block social-insurance-history">
      <div class="social-insurance-title-row">
        <span class="social-insurance-title">{{ $title }}</span>
      </div>
      <div class="info-block-grid">
        @foreach($shahoFields as $field => $label)
        <label class="detail-field detail-field-compact">
          <span>{{ $label }}</span>
          <div class="social-insurance-value">{{ $formatShahoNumber($row[$field] ?? '') !== '' ? $formatShahoNumber($row[$field] ?? '') : '---' }}</div>
        </label>
        @endforeach
      </div>
    </div>
    @endif
    @endforeach
    @else
    <div class="staff-empty">データがありません</div>
    @endif
  </div>
</div>
