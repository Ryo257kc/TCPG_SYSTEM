@php
$fuyoRowsByYear = [];
foreach (($fuyoRows ?? []) as $row) {
$year = trim((string)($row['_registration_year'] ?? ''));
if ($year === '') {
$registrationDate = trim((string)($row['_raw_registration_date'] ?? $row['registration_date'] ?? ''));
if ($registrationDate !== '') {
$timestamp = strtotime($registrationDate);
$year = $timestamp !== false ? date('Y', $timestamp) : substr($registrationDate, 0, 4);
}
}
$year = $year !== '' ? $year : '年未設定';
$fuyoRowsByYear[$year][] = $row;
}

$fuyoValue = function (array $row, string $key): string {
return trim((string)($row[$key] ?? ''));
};

$fuyoDateValue = function (array $row, string $key): string {
$raw = trim((string)($row['_raw_' . $key] ?? $row[$key] ?? ''));
return $raw !== '' ? substr($raw, 0, 10) : '';
};

$fuyoAgeValue = function (array $row): string {
$age = trim((string)($row['_age'] ?? ''));
if ($age !== '') {
return $age;
}

$raw = trim((string)($row['_raw_fuyo_birthday'] ?? $row['fuyo_birthday'] ?? ''));
if ($raw === '') {
return '';
}

try {
$birthday = new \DateTimeImmutable(substr($raw, 0, 10));
return (string)$birthday->diff(new \DateTimeImmutable('today'))->y;
} catch (\Throwable) {
return '';
}
};

$fuyoChecked = function (array $row, string $key): bool {
$value = $row[$key] ?? null;
if (is_bool($value)) {
return $value;
}
$text = strtolower(trim((string)$value));
return in_array($text, ['1', 'true', 'yes', 'on'], true);
};

$fuyoBoolText = function (array $row, string $key) use ($fuyoChecked): string {
return $fuyoChecked($row, $key) ? 'あり' : '---';
};

$fuyoMoneyValue = function (array $row, string $key): string {
$value = str_replace(',', '', trim((string)($row[$key] ?? '')));
if ($value === '') {
return '';
}
return is_numeric($value) ? number_format((float)$value) : trim((string)($row[$key] ?? ''));
};
@endphp

<style>
  .fuyo-year-block {
    border: 1px solid #b8cbe6;
    border-radius: 8px;
    background: #f4f8fd;
    padding: 10px;
  }

  .fuyo-year-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    padding: 6px 10px;
    border-left: 5px solid #1f4f8f;
    background: #e7f0fb;
    color: #173d69;
    font-size: 16px;
    font-weight: 800;
  }

  .fuyo-year-count {
    color: #5b708f;
    font-size: 12px;
    font-weight: 700;
  }

  .fuyo-person-card {
    margin-top: 8px;
  }

  .fuyo-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .fuyo-address {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    min-height: 0 !important;
    height: 2.6em;
    resize: vertical;
  }

  .fuyo-readonly-card {
    background: #fff;
  }

  .fuyo-person-card .fuyo-title-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-bottom: 6px;
  }
</style>

<div class="related-card">
  <div class="related-header">
    <h3>扶養</h3>
    <span>{{ number_format(count($fuyoRows ?? [])) }} 件</span>
  </div>

  <div class="staff-tab-panels">
    @if(session('status'))
    <div class="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
    <div class="error">
      @foreach ($errors->all() as $error)
      <div>{{ $error }}</div>
      @endforeach
    </div>
    @endif

    <div class="master-create" id="fuyo-create">
      <button type="button" class="btn-secondary master-toggle-btn" data-toggle-target="fuyo-create">＋新規登録</button>
      <form method="post" action="{{ route('admin.master.staff.fuyo.store') }}" class="info-block master-toggle-body">
        @csrf
        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
        <input type="hidden" name="q" value="{{ $keyword }}">
        <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
        <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

        <div class="info-block-title">新規登録</div>
        <div class="info-block-grid fuyo-grid">
          <label class="detail-field detail-field-compact">
            <span>年末設定年</span>
            <input type="date" name="registration_date" required>
          </label>
          <label class="detail-field detail-field-compact">
            <span>控除対象</span>
            <input type="checkbox" name="deduction_target" value="1">
          </label>
          <label class="detail-field detail-field-compact">
            <span>寡婦</span>
            <input type="checkbox" name="widow" value="1">
          </label>

          <label class="detail-field detail-field-compact">
            <span>フリガナ</span>
            <input type="text" name="fuyo_name_furi">
          </label>
          <label class="detail-field detail-field-compact">
            <span>続柄</span>
            <select name="fuyo_relationship">
              <option value=""></option>
              @foreach(($fuyoRelationshipOptions ?? []) as $option)
              <option value="{{ $option }}">{{ $option }}</option>
              @endforeach
            </select>
          </label>
          <label class="detail-field detail-field-compact">
            <span>性別</span>
            <select name="fuyo_sex">
              <option value=""></option>
              @foreach(($fuyoSexOptions ?? []) as $option)
              <option value="{{ $option }}">{{ $option }}</option>
              @endforeach
            </select>
          </label>

          <label class="detail-field detail-field-compact">
            <span>氏名</span>
            <input type="text" name="fuyo_name">
          </label>
          <label class="detail-field detail-field-compact">
            <span>マイナンバー</span>
            <input type="text" name="fuyo_my_number" maxlength="12">
          </label>
          <label class="detail-field detail-field-compact">
            <span>居住</span>
            <select name="kyojyu">
              <option value=""></option>
              @foreach(($kyojyuOptions ?? []) as $option)
              <option value="{{ $option }}">{{ $option }}</option>
              @endforeach
            </select>
          </label>

          <label class="detail-field detail-field-compact">
            <span>生年月日</span>
            <input type="date" name="fuyo_birthday">
          </label>
          <label class="detail-field detail-field-compact">
            <span>年齢</span>
          </label>
          <label class="detail-field detail-field-compact">
            <span>翌年収入見積</span>
            <input type="text" name="fuyo_shunyu">
          </label>

          <label class="detail-field detail-field-compact">
            <span>障害状態</span>
            <select name="failure_judgment">
              <option value=""></option>
              @foreach(($failureJudgmentOptions ?? []) as $option)
              <option value="{{ $option }}">{{ $option }}</option>
              @endforeach
            </select>
          </label>
          <label class="detail-field detail-field-compact">
            <span>障害手帳</span>
            <input type="text" name="failure_notebook">
          </label>


          <label class="detail-field detail-field-wide detail-field-textarea">
            <span>住所</span>
            <textarea name="fuyo_address" rows="1" class="fuyo-address"></textarea>
          </label>
        </div>
        <div class="detail-actions">
          <button type="submit" class="btn-primary">登録</button>
        </div>
      </form>
    </div>

    @if($fuyoRowsByYear !== [])
    @foreach($fuyoRowsByYear as $year => $yearRows)
    @php $yearFormId = 'fuyo-latest-' . $loop->index; @endphp
    <div class="fuyo-year-block">
      <div class="fuyo-year-heading">
        <span>{{ $year }}年</span>
        <span class="fuyo-year-count">{{ number_format(count($yearRows)) }} 件</span>
      </div>
      @foreach($yearRows as $row)
      @php $isLatestFuyo = $loop->parent->first; @endphp
      @if($isLatestFuyo)
      <form method="post" action="{{ route('admin.master.staff.fuyo.update') }}" class="info-block fuyo-person-card master-editable" id="{{ $yearFormId }}">
        @csrf
        <input type="hidden" name="fuyo_no" value="{{ $row['fuyo_no'] ?? '' }}">
        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
        <input type="hidden" name="q" value="{{ $keyword }}">
        <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

        <div class="fuyo-title-row">
          <button type="button" class="btn-secondary master-edit-btn" data-edit-target="{{ $yearFormId }}">編集</button>
        </div>
        <div class="info-block-grid fuyo-grid">
          <label class="detail-field detail-field-compact">
            <span>年末設定年</span>
            <div class="detail-value">{{ $fuyoDateValue($row, 'registration_date') !== '' ? $fuyoDateValue($row, 'registration_date') : '---' }}</div>
            <input type="date" name="registration_date" value="{{ $fuyoDateValue($row, 'registration_date') }}" required>
          </label>
          <label class="detail-field detail-field-compact">
            <span>控除対象</span>
            <div class="detail-value">{{ $fuyoBoolText($row, 'deduction_target') }}</div>
            <input type="checkbox" name="deduction_target" value="1" @checked($fuyoChecked($row, 'deduction_target' ))>
          </label>
          <label class="detail-field detail-field-compact">
            <span>寡婦</span>
            <div class="detail-value">{{ $fuyoBoolText($row, 'widow') }}</div>
            <input type="checkbox" name="widow" value="1" @checked($fuyoChecked($row, 'widow' ))>
          </label>

          <label class="detail-field detail-field-compact">
            <span>フリガナ</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_name_furi') !== '' ? $fuyoValue($row, 'fuyo_name_furi') : '---' }}</div>
            <input type="text" name="fuyo_name_furi" value="{{ $fuyoValue($row, 'fuyo_name_furi') }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>続柄</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_relationship') !== '' ? $fuyoValue($row, 'fuyo_relationship') : '---' }}</div>
            <select name="fuyo_relationship">
              <option value=""></option>
              @foreach(($fuyoRelationshipOptions ?? []) as $option)
              <option value="{{ $option }}" @selected($fuyoValue($row, 'fuyo_relationship') === $option)>{{ $option }}</option>
              @endforeach
            </select>
          </label>
          <label class="detail-field detail-field-compact">
            <span>性別</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_sex') !== '' ? $fuyoValue($row, 'fuyo_sex') : '---' }}</div>
            <select name="fuyo_sex">
              <option value=""></option>
              @foreach(($fuyoSexOptions ?? []) as $option)
              <option value="{{ $option }}" @selected($fuyoValue($row, 'fuyo_sex') === $option)>{{ $option }}</option>
              @endforeach
            </select>
          </label>

          <label class="detail-field detail-field-compact">
            <span>氏名</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_name') !== '' ? $fuyoValue($row, 'fuyo_name') : '---' }}</div>
            <input type="text" name="fuyo_name" value="{{ $fuyoValue($row, 'fuyo_name') }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>マイナンバー</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_my_number') !== '' ? $fuyoValue($row, 'fuyo_my_number') : '---' }}</div>
            <input type="text" name="fuyo_my_number" value="{{ $fuyoValue($row, 'fuyo_my_number') }}" maxlength="12">
          </label>
          <label class="detail-field detail-field-compact">
            <span>居住</span>
            <div class="detail-value">{{ $fuyoValue($row, 'kyojyu') !== '' ? $fuyoValue($row, 'kyojyu') : '---' }}</div>
            <select name="kyojyu">
              <option value=""></option>
              @foreach(($kyojyuOptions ?? []) as $option)
              <option value="{{ $option }}" @selected($fuyoValue($row, 'kyojyu') === $option)>{{ $option }}</option>
              @endforeach
            </select>
          </label>

          <label class="detail-field detail-field-compact">
            <span>生年月日</span>
            <div class="detail-value">{{ $fuyoDateValue($row, 'fuyo_birthday') !== '' ? $fuyoDateValue($row, 'fuyo_birthday') : '---' }}</div>
            <input type="date" name="fuyo_birthday" value="{{ $fuyoDateValue($row, 'fuyo_birthday') }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>年齢</span>
            <div class="detail-value">{{ $fuyoAgeValue($row) !== '' ? $fuyoAgeValue($row) : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>翌年収入見積</span>
            <div class="detail-value">{{ $fuyoMoneyValue($row, 'fuyo_shunyu') !== '' ? $fuyoMoneyValue($row, 'fuyo_shunyu') : '---' }}</div>
            <input type="text" name="fuyo_shunyu" value="{{ $fuyoMoneyValue($row, 'fuyo_shunyu') }}">
          </label>

          <label class="detail-field detail-field-compact">
            <span>障害状態</span>
            <div class="detail-value">{{ $fuyoValue($row, 'failure_judgment') !== '' ? $fuyoValue($row, 'failure_judgment') : '---' }}</div>
            <select name="failure_judgment">
              <option value=""></option>
              @foreach(($failureJudgmentOptions ?? []) as $option)
              <option value="{{ $option }}" @selected($fuyoValue($row, 'failure_judgment') === $option)>{{ $option }}</option>
              @endforeach
            </select>
          </label>
          <label class="detail-field detail-field-compact">
            <span>障害手帳</span>
            <div class="detail-value">{{ $fuyoValue($row, 'failure_notebook') !== '' ? $fuyoValue($row, 'failure_notebook') : '---' }}</div>
            <input type="text" name="failure_notebook" value="{{ $fuyoValue($row, 'failure_notebook') }}">
          </label>

          <label class="detail-field detail-field-wide detail-field-textarea">
            <span>住所</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_address') !== '' ? $fuyoValue($row, 'fuyo_address') : '---' }}</div>
            <textarea name="fuyo_address" rows="1" class="fuyo-address">{{ $fuyoValue($row, 'fuyo_address') }}</textarea>
          </label>
        </div>
        <div class="detail-actions master-edit-actions">
          <button type="submit" class="btn-primary btn-small">保存</button>
          <button type="submit" class="btn-secondary btn-small" formaction="{{ route('admin.master.staff.fuyo.delete') }}" onclick="return confirm('この扶養情報を削除します。');">削除</button>
        </div>
      </form>
      @else
      <div class="info-block fuyo-person-card fuyo-readonly-card">
        <div class="info-block-grid fuyo-grid">
          <label class="detail-field detail-field-compact">
            <span>年末設定年</span>
            <div class="detail-value">{{ $fuyoDateValue($row, 'registration_date') !== '' ? $fuyoDateValue($row, 'registration_date') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>控除対象</span>
            <div class="detail-value">{{ $fuyoBoolText($row, 'deduction_target') }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>寡婦</span>
            <div class="detail-value">{{ $fuyoBoolText($row, 'widow') }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>フリガナ</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_name_furi') !== '' ? $fuyoValue($row, 'fuyo_name_furi') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>続柄</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_relationship') !== '' ? $fuyoValue($row, 'fuyo_relationship') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>性別</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_sex') !== '' ? $fuyoValue($row, 'fuyo_sex') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>氏名</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_name') !== '' ? $fuyoValue($row, 'fuyo_name') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>マイナンバー</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_my_number') !== '' ? $fuyoValue($row, 'fuyo_my_number') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>居住</span>
            <div class="detail-value">{{ $fuyoValue($row, 'kyojyu') !== '' ? $fuyoValue($row, 'kyojyu') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>生年月日</span>
            <div class="detail-value">{{ $fuyoDateValue($row, 'fuyo_birthday') !== '' ? $fuyoDateValue($row, 'fuyo_birthday') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>年齢</span>
            <div class="detail-value">{{ $fuyoAgeValue($row) !== '' ? $fuyoAgeValue($row) : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>翌年収入見積</span>
            <div class="detail-value">{{ $fuyoMoneyValue($row, 'fuyo_shunyu') !== '' ? $fuyoMoneyValue($row, 'fuyo_shunyu') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>障害状態</span>
            <div class="detail-value">{{ $fuyoValue($row, 'failure_judgment') !== '' ? $fuyoValue($row, 'failure_judgment') : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>障害手帳</span>
            <div class="detail-value">{{ $fuyoValue($row, 'failure_notebook') !== '' ? $fuyoValue($row, 'failure_notebook') : '---' }}</div>
          </label>

          <label class="detail-field detail-field-wide detail-field-textarea">
            <span>住所</span>
            <div class="detail-value">{{ $fuyoValue($row, 'fuyo_address') !== '' ? $fuyoValue($row, 'fuyo_address') : '---' }}</div>
          </label>
        </div>
      </div>
      @endif
      @endforeach
    </div>
    @endforeach
    @else
    <div class="staff-empty">データがありません</div>
    @endif
  </div>
</div>
