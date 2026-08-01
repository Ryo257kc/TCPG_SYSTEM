@php
$employmentPairs = [
    [['_company_name', '会社名'], ['_store_name', '店舗名']],
    [['staff_division', '雇用区分'], ['employment_status', '就業状況']],
    [['nyu_date', '入社日'], ['tai_date', '退社日']],
    [['tax_amount', '税額'], ['__none', '']],
];

$bankPairs = [
    [['bank_name_1', '銀行名1'], ['bank_branch_1', '支店名1']],
    [['account_type', '口座種別1'], ['account_num', '口座番号1']],
    [['bank_name_2', '銀行名2'], ['bank_branch_2', '支店名2']],
    [['account_type2', '口座種別2'], ['account_num2', '口座番号2']],
];

$rightsPairs = [
    [['is_admin', '全権管理者'], ['__none', '']],
    [['oushin_staff', '往診スタッフ'], ['front_staff', '店舗システム']],
    [['is_accounting_user', '往診事務'], ['is_payment_check_user', '事務所']],
    [['is_visit_management_user', '往診管理'], ['is_view_only_user', '往診売上']],
    [['is_store_management_user', '店舗管理'], ['is_daily_report_user', '店舗日報']],
];

$singleFields = [
'staff_code',
'password',
];

$usedFields = [
'No',
'employment',
'employment_status',
'section',
'post_num', 'address_furi', 'address',
'home_tel', 'mobile_tel', 'head_house', 'relationship', 'spouse',
'password',
];

$booleanFields = [
'syaho', 'koyou', 'yukyu', 'has_fixed_term', 'trial', 'spouse',
'is_admin',
'oushin_staff', 'front_staff',
'is_accounting_user', 'is_payment_check_user',
'is_visit_management_user', 'is_view_only_user',
'is_store_management_user', 'is_daily_report_user',
];

$displayValue = static function (string $field, array $row) use ($booleanFields): string {
$value = trim((string) ($row[$field] ?? ''));
if ($value === '') {
return '---';
}

if (in_array($field, $booleanFields, true)) {
$normalized = mb_strtolower($value);
$isChecked = !in_array($normalized, ['0', 'false', 'no', 'off', 'なし', '無', 'null'], true);
return $isChecked ? '☑' : '☐';
}

return $value;
};

$valueClass = static function (string $field, array $row) use ($booleanFields): string {
$base = trim((string) ($row[$field] ?? '')) === '' ? 'detail-value-empty' : '';
if (in_array($field, $booleanFields, true)) {
return trim('detail-value-bool ' . $base);
}

return $base;
};

$workDisplayValue = static function (string $field, array $row) use ($displayValue): string {
$value = trim((string) ($row[$field] ?? ''));
if ($value === '') {
return $displayValue($field, $row);
}

if (is_numeric($value)) {
$numeric = (float) $value;
if (abs($numeric) < 0.0000001) {
  return '-' ;
  }

  $formatted=rtrim(rtrim(number_format($numeric, 4, '.' , '' ), '0' ), '.' );
  return $formatted==='' ? '-' : $formatted;
  }

  if ($value==='0' || $value==='0.0' || $value==='0.00' ) {
  return '-' ;
  }

  return $displayValue($field, $row);
  };

  $addressValue=trim((string) ($selectedRow['address'] ?? '' ));

  $staffDivisionOptions=[ '役員' , '兼務役員' , '正社員' , 'パート' , 'アルバイト' , '業務委託' ,
  ];

  @endphp

  <form method="post" action="{{ route('admin.master.staff.update') }}" class="staff-info-form" id="staff-info-form">
  @csrf
  <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
  <input type="hidden" name="q" value="{{ $keyword }}">
  <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
  <input type="hidden" name="tab" value="staff">

  <div class="staff-info-view">
    <div class="staff-info-sections">
      <section class="info-block">
        <div class="info-block-title">名前</div>
        @php
        $usedFields[] = 'staff_id';
        $usedFields[] = 'staff_name_furi';
        $usedFields[] = 'staff_name';
        $usedFields[] = 'display_name_ja';
        $usedFields[] = 'sex';
        $usedFields[] = 'birthday';
        $usedFields[] = 'my_number';
        $usedFields[] = '_age';
        @endphp
        <div class="name-block-grid">
          @if(array_key_exists('staff_id', $selectedRow))
          <label class="detail-field detail-field-compact name-block-wide">
            <span>スタッフID</span>
            <div class="detail-value {{ ($selectedRow['staff_id'] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow['staff_id'] ?? '') !== '' ? $selectedRow['staff_id'] : '---' }}</div>
          </label>
          @endif

          @if(array_key_exists('staff_name_furi', $selectedRow))
          <label class="detail-field detail-field-compact name-block-wide">
            <span>氏名カナ</span>
            <div class="detail-value {{ ($selectedRow['staff_name_furi'] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow['staff_name_furi'] ?? '') !== '' ? $selectedRow['staff_name_furi'] : '---' }}</div>
          </label>
          @endif

          @foreach([[['staff_name', '氏名'], ['display_name_ja', '表示名']], [['sex', '性別'], ['birthday', '生年月日']], [['my_number', 'マイナンバー'], ['_age', '年齢']]] as [$leftItem, $rightItem])
          @php
          [$leftField, $leftLabel] = $leftItem;
          [$rightField, $rightLabel] = $rightItem;
          $leftExists = array_key_exists($leftField, $selectedRow);
          $rightExists = array_key_exists($rightField, $selectedRow);
          @endphp
          @if($leftExists || $rightExists)
          <div class="detail-pair detail-pair-inline detail-pair-wide">
            @if($leftExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $leftLabel }}</span>
              <div class="detail-value {{ ($selectedRow[$leftField] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow[$leftField] ?? '') !== '' ? $selectedRow[$leftField] : '---' }}</div>
            </label>
            @endif
            @if($rightExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $rightLabel }}</span>
              <div class="detail-value {{ ($selectedRow[$rightField] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow[$rightField] ?? '') !== '' ? $selectedRow[$rightField] : '---' }}</div>
            </label>
            @endif
          </div>
          @endif
          @endforeach
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">住所</div>
        <div class="address-block-grid">
          @if(array_key_exists('post_num', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>郵便番号</span>
            <div class="detail-value {{ $selectedRow['post_num'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['post_num'] !== '' ? $selectedRow['post_num'] : '---' }}</div>
          </label>
          @endif

          @if(array_key_exists('address_furi', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight address-block-wide">
            <span>住所カナ</span>
            <div class="detail-value {{ $selectedRow['address_furi'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['address_furi'] !== '' ? $selectedRow['address_furi'] : '---' }}</div>
          </label>
          @endif

          <label class="detail-field detail-field-compact detail-field-tight address-block-wide">
            <span>住所</span>
            <div class="detail-value {{ $addressValue === '' ? 'detail-value-empty' : '' }}">{{ $addressValue !== '' ? $addressValue : '---' }}</div>
          </label>

          @if(array_key_exists('home_tel', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>電話番号</span>
            <div class="detail-value {{ $selectedRow['home_tel'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['home_tel'] !== '' ? $selectedRow['home_tel'] : '---' }}</div>
          </label>
          @endif

          @if(array_key_exists('mobile_tel', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>携帯番号</span>
            <div class="detail-value {{ $selectedRow['mobile_tel'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['mobile_tel'] !== '' ? $selectedRow['mobile_tel'] : '---' }}</div>
          </label>
          @endif

          <div class="detail-pair detail-pair-wide detail-pair-inline-3">
            @if(array_key_exists('spouse', $selectedRow))
            <label class="detail-field detail-field-compact detail-field-tight">
              <span>配偶者</span>
              <div class="{{ in_array('spouse', $booleanFields, true) ? 'detail-value-bool detail-value-bool-tight' : 'detail-value ' . $valueClass('spouse', $selectedRow) }}">{{ $displayValue('spouse', $selectedRow) }}</div>
            </label>
            @endif

            @if(array_key_exists('head_house', $selectedRow))
            <label class="detail-field detail-field-compact detail-field-tight">
              <span>世帯主</span>
              <div class="detail-value {{ $selectedRow['head_house'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['head_house'] !== '' ? $selectedRow['head_house'] : '---' }}</div>
            </label>
            @endif

            @if(array_key_exists('relationship', $selectedRow))
            <label class="detail-field detail-field-compact detail-field-tight">
              <span>続柄</span>
              <div class="detail-value {{ $selectedRow['relationship'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['relationship'] !== '' ? $selectedRow['relationship'] : '---' }}</div>
            </label>
            @endif
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">所属・雇用</div>
        <div class="info-block-grid">
          @foreach($employmentPairs as [$topItem, $bottomItem])
          @php
          [$topField, $topLabel] = $topItem;
          [$bottomField, $bottomLabel] = $bottomItem;
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
          @endphp
          @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
            @php $topIsBoolean = in_array($topField, $booleanFields, true); @endphp
            <label class="detail-field detail-field-compact">
              <span>{{ $topLabel }}</span>
              <div class="{{ $topIsBoolean ? 'detail-value-bool-rights' : 'detail-value ' . $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
            </label>
            @endif
            @if($bottomExists)
            @php $bottomIsBoolean = in_array($bottomField, $booleanFields, true); @endphp
            <label class="detail-field detail-field-compact">
              <span>{{ $bottomLabel }}</span>
              <div class="{{ $bottomIsBoolean ? 'detail-value-bool-rights' : 'detail-value ' . $valueClass($bottomField, $selectedRow) }}">{{ $displayValue($bottomField, $selectedRow) }}</div>
            </label>
            @endif
          </div>
          @endif
          @endforeach
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">メモ</div>
        @php $usedFields[] = 'memo'; @endphp
        @php $memoValue = trim((string) ($selectedRow['memo'] ?? '')); @endphp
        <label class="detail-field detail-field-wide detail-field-textarea detail-field-textarea-no-label">
          <div class="detail-value detail-value-textarea {{ $memoValue === '' ? 'detail-value-empty' : '' }}">{{ $memoValue !== '' ? $memoValue : '---' }}</div>
        </label>
      </section>

      <section class="info-block">
        <div class="info-block-title">契約条件</div>
        <div class="contract-block-grid">
          @foreach([['business_content', '業務内容']] as [$wideField, $wideLabel])
          @php $usedFields[] = $wideField; @endphp
          @if(array_key_exists($wideField, $selectedRow))
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>{{ $wideLabel }}</span>
            <div class="detail-value {{ ($selectedRow[$wideField] ?? '') === '' ? 'detail-value-empty' : '' }}">
              {{ ($selectedRow[$wideField] ?? '') !== '' ? $selectedRow[$wideField] : '---' }}
            </div>
          </label>
          @endif
          @endforeach

          @php
          $usedFields[] = 'has_fixed_term';
          $usedFields[] = 'fixed_term_detail';
          @endphp
          @if(array_key_exists('has_fixed_term', $selectedRow) || array_key_exists('fixed_term_detail', $selectedRow))
          <label class="detail-field detail-field-compact contract-block-wide contract-inline-field">
            <span>期間の定め</span>
            <div class="contract-inline-values">
              @if(array_key_exists('has_fixed_term', $selectedRow))
              <div class="detail-value {{ $valueClass('has_fixed_term', $selectedRow) }}">
                {{ $displayValue('has_fixed_term', $selectedRow) }}
              </div>
              @endif
              @if(array_key_exists('fixed_term_detail', $selectedRow))
              <div class="detail-value contract-inline-text {{ ($selectedRow['fixed_term_detail'] ?? '') === '' ? 'detail-value-empty' : '' }}">
                {{ ($selectedRow['fixed_term_detail'] ?? '') !== '' ? $selectedRow['fixed_term_detail'] : '---' }}
              </div>
              @endif
            </div>
          </label>
          @endif

          @php
          $usedFields[] = 'work_schedule_1';
          $usedFields[] = 'work_schedule_2';
          @endphp
          @if(array_key_exists('work_schedule_1', $selectedRow))
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>勤務時間</span>
            <div class="detail-value {{ $valueClass('work_schedule_1', $selectedRow) }}">
              {{ $displayValue('work_schedule_1', $selectedRow) }}
            </div>
          </label>
          @endif

          @if(array_key_exists('work_schedule_2', $selectedRow))
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>勤務時間</span>
            <div class="detail-value {{ $valueClass('work_schedule_2', $selectedRow) }}">
              {{ $displayValue('work_schedule_2', $selectedRow) }}
            </div>
          </label>
          @endif
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">勤務条件</div>
        <div class="info-block-grid">
          @foreach([['trial', '試用期間'], ['yukyu', '有休']] as [$field, $label])
          @php $usedFields[] = $field; @endphp
          @if(array_key_exists($field, $selectedRow))
          <label class="detail-field detail-field-compact detail-field-work">
            <span>{{ $label }}</span>
            <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
          </label>
          @endif
          @endforeach

          @php $usedFields[] = 'yukyu_month'; @endphp
          @if(array_key_exists('yukyu_month', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-work detail-field-wide">
            <span>有休加算月</span>
            <div class="detail-value {{ $valueClass('yukyu_month', $selectedRow) }}">{{ $workDisplayValue('yukyu_month', $selectedRow) }}</div>
          </label>
          @endif

          @php
          $workLeftFields = [['working_time', '所定労働時間(日)'], ['weekly_working_time', '所定労働時間(週)'], ['year_working_time', '所定労働時間(月)']];
          $workRightFields = [['car_km', '車通勤km'], ['traffic_day', '日額交通費'], ['traffic_day_tuika', '追加交通費日額']];
          foreach (array_merge(array_column($workLeftFields, 0), array_column($workRightFields, 0), ['percentage_1', 'percentage_2']) as $field) {
          $usedFields[] = $field;
          }
          @endphp

          <div class="detail-pair">
            @foreach($workLeftFields as [$field, $label])
            @if(array_key_exists($field, $selectedRow))
            <label class="detail-field detail-field-compact detail-field-work">
              <span>{{ $label }}</span>
              <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
            </label>
            @endif
            @endforeach
          </div>

          <div class="detail-pair">
            @foreach($workRightFields as [$field, $label])
            @if(array_key_exists($field, $selectedRow))
            <label class="detail-field detail-field-compact detail-field-work">
              <span>{{ $label }}</span>
              <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
            </label>
            @endif
            @endforeach
          </div>

          <div class="detail-pair detail-pair-wide detail-pair-inline">
            @foreach([['percentage_1', '業務委託割合'], ['percentage_2', '業務委託割合']] as [$field, $label])
            @if(array_key_exists($field, $selectedRow))
            <label class="detail-field detail-field-compact detail-field-work">
              <span>{{ $label }}</span>
              <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
            </label>
            @endif
            @endforeach
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">振込口座</div>
        <div class="info-block-grid">
          @php $usedFields[] = 'transfer_purpose'; @endphp
          @if(array_key_exists('transfer_purpose', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-wide">
            <span>振込先</span>
            <div class="detail-value {{ $valueClass('transfer_purpose', $selectedRow) }}">{{ $displayValue('transfer_purpose', $selectedRow) }}</div>
          </label>
          @endif

          @foreach(array_slice($bankPairs, 0, 2) as [$topItem, $bottomItem])
          @php
          [$topField, $topLabel] = $topItem;
          [$bottomField, $bottomLabel] = $bottomItem;
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
          @endphp
          @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $topLabel }}</span>
              <div class="detail-value {{ $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
            </label>
            @endif
            @if($bottomExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $bottomLabel }}</span>
              <div class="detail-value {{ $valueClass($bottomField, $selectedRow) }}">{{ $displayValue($bottomField, $selectedRow) }}</div>
            </label>
            @endif
          </div>
          @endif
          @endforeach

          <div class="detail-divider detail-divider-wide"></div>

          @foreach(array_slice($bankPairs, 2, 2) as [$topItem, $bottomItem])
          @php
          [$topField, $topLabel] = $topItem;
          [$bottomField, $bottomLabel] = $bottomItem;
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
          @endphp
          @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $topLabel }}</span>
              <div class="detail-value {{ $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
            </label>
            @endif
            @if($bottomExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $bottomLabel }}</span>
              <div class="detail-value {{ $valueClass($bottomField, $selectedRow) }}">{{ $displayValue($bottomField, $selectedRow) }}</div>
            </label>
            @endif
          </div>
          @endif
          @endforeach
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">変更履歴</div>
        @php $usedFields[] = 'change_history'; @endphp
        @php $changeHistoryValue = trim((string) ($selectedRow['change_history'] ?? '')); @endphp
        <label class="detail-field detail-field-wide detail-field-textarea detail-field-textarea-no-label">
          <div class="detail-value detail-value-textarea {{ $changeHistoryValue === '' ? 'detail-value-empty' : '' }}">{{ $changeHistoryValue !== '' ? $changeHistoryValue : '---' }}</div>
        </label>
      </section>

      <section class="info-block">
        <div class="info-block-title">権限・その他</div>
        <div class="info-block-grid">
          @foreach($rightsPairs as [$topItem, $bottomItem])
          @php
          [$topField, $topLabel] = $topItem;
          [$bottomField, $bottomLabel] = $bottomItem;
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
          @endphp
          @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $topLabel }}</span>
              <div class="detail-value {{ $valueClass($topField, $selectedRow) }} {{ in_array($topField, $booleanFields, true) ? 'detail-value-bool-rights' : '' }}">{{ $displayValue($topField, $selectedRow) }}</div>
            </label>
            @endif
            @if($bottomExists)
            <label class="detail-field detail-field-compact">
              <span>{{ $bottomLabel }}</span>
              <div class="detail-value {{ $valueClass($bottomField, $selectedRow) }} {{ in_array($bottomField, $booleanFields, true) ? 'detail-value-bool-rights' : '' }}">{{ $displayValue($bottomField, $selectedRow) }}</div>
            </label>
            @endif
          </div>
          @endif
          @endforeach

          @foreach($singleFields as $field)
          @php $usedFields[] = $field; @endphp
          @if(array_key_exists($field, $selectedRow))
          <label class="detail-field">
            <span>{{ $field === 'staff_code' ? 'スタッフコード' : 'パスワード' }}</span>
            <div class="detail-value {{ $selectedRow[$field] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow[$field] !== '' ? $selectedRow[$field] : '---' }}</div>
          </label>
          @endif
          @endforeach
        </div>
      </section>

    </div>
  </div>

  <div class="staff-info-edit" hidden>
    <div class="staff-info-sections">
      <section class="info-block">
        <div class="info-block-title">名前</div>
        <div class="name-block-grid">
          <label class="detail-field detail-field-compact name-block-wide">
            <span>スタッフID</span>
            <div class="staff-display-value">{{ $selectedRow['staff_id'] !== '' ? $selectedRow['staff_id'] : '---' }}</div>
          </label>
          <label class="detail-field detail-field-compact name-block-wide">
            <span>氏名カナ</span>
            <input type="text" name="staff_name_furi" value="{{ $selectedRow['staff_name_furi'] ?? '' }}">
          </label>
          <div class="detail-pair detail-pair-inline detail-pair-wide">
            <label class="detail-field detail-field-compact">
              <span>氏名</span>
              <input type="text" name="staff_name" value="{{ $selectedRow['staff_name'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>表示名</span>
              <input type="text" name="display_name_ja" value="{{ $selectedRow['display_name_ja'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair detail-pair-inline detail-pair-wide">
            <label class="detail-field detail-field-compact">
              <span>性別</span>
              <input type="text" name="sex" value="{{ $selectedRow['sex'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>生年月日</span>
              <input type="date" name="birthday" value="{{ $selectedRow['_raw_birthday'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair detail-pair-inline detail-pair-wide">
            <label class="detail-field detail-field-compact">
              <span>マイナンバー</span>
              <input type="text" name="my_number" value="{{ $selectedRow['my_number'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>年齢</span>
              <div class="staff-display-value">{{ ($selectedRow['_age'] ?? '') !== '' ? $selectedRow['_age'] : '---' }}</div>
            </label>
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">住所</div>
        <div class="address-block-grid">
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>郵便番号</span>
            <input type="text" name="post_num" value="{{ $selectedRow['post_num'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact detail-field-tight address-block-wide">
            <span>住所カナ</span>
            <input type="text" name="address_furi" value="{{ $selectedRow['address_furi'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact detail-field-tight address-block-wide">
            <span>住所</span>
            <input type="text" name="address" value="{{ $selectedRow['address'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>電話番号</span>
            <input type="text" name="home_tel" value="{{ $selectedRow['home_tel'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>携帯番号</span>
            <input type="text" name="mobile_tel" value="{{ $selectedRow['mobile_tel'] ?? '' }}">
          </label>
          <div class="detail-pair detail-pair-wide detail-pair-inline-3">
            <label class="detail-field detail-field-compact detail-field-tight">
              <span>配偶者</span>
              <div class="checkbox-line"><input type="checkbox" name="spouse" value="1" @checked((string)($selectedRow['spouse'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['spouse'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> あり</div>
            </label>
            <label class="detail-field detail-field-compact detail-field-tight">
              <span>世帯主</span>
              <input type="text" name="head_house" value="{{ $selectedRow['head_house'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact detail-field-tight">
              <span>続柄</span>
              <input type="text" name="relationship" value="{{ $selectedRow['relationship'] ?? '' }}">
            </label>
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">所属・雇用</div>
        <div class="info-block-grid">
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>会社名</span>
              <div class="staff-display-value">{{ ($selectedRow['_company_name'] ?? '') !== '' ? $selectedRow['_company_name'] : '---' }}</div>
            </label>
            <label class="detail-field detail-field-compact">
              <span>店舗</span>
              <select name="section">
                <option value=""></option>
                @foreach(($storeOptions ?? []) as $store)
                <option value="{{ $store['store_code'] }}" @selected(($selectedRow['section'] ?? '' )===$store['store_code'])>{{ $store['store_name'] !== '' ? $store['store_name'] : $store['store_code'] }}</option>
                @endforeach
              </select>
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>雇用区分</span>
              <select name="staff_division">
                <option value=""></option>
                @foreach($staffDivisionOptions as $option)
                <option value="{{ $option }}" @selected(($selectedRow['staff_division'] ?? '' )===$option)>{{ $option }}</option>
                @endforeach
              </select>
            </label>
            <label class="detail-field detail-field-compact">
              <span>就業状況</span>
              <select name="employment">
                <option value=""></option>
                @foreach(['在職', '退職', '転籍', '育児休業中', '産後休業中', '介護休業中', '傷病休業中', '採用予定'] as $option)
                <option value="{{ $option }}" @selected(($selectedRow['employment'] ?? '') === $option)>{{ $option }}</option>
                @endforeach
              </select>
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>入社日</span>
              <input type="date" name="nyu_date" value="{{ $selectedRow['_raw_nyu_date'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>退社日</span>
              <input type="date" name="tai_date" value="{{ $selectedRow['_raw_tai_date'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>税額</span>
              <input type="text" name="tax_amount" value="{{ $selectedRow['tax_amount'] ?? '' }}">
            </label>
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">メモ</div>
        <label class="detail-field detail-field-wide detail-field-textarea detail-field-textarea-no-label">
          <textarea name="memo">{{ $selectedRow['memo'] ?? '' }}</textarea>
        </label>
      </section>

      <section class="info-block">
        <div class="info-block-title">契約条件</div>
        <div class="contract-block-grid">
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>業務内容</span>
            <input type="text" name="business_content" value="{{ $selectedRow['business_content'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact contract-block-wide contract-inline-field">
            <span>期間の定め</span>
            <div class="contract-inline-values">
              <div class="checkbox-line"><input type="checkbox" name="has_fixed_term" value="1" @checked((string)($selectedRow['has_fixed_term'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['has_fixed_term'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> あり</div>
              <input type="text" name="fixed_term_detail" class="contract-inline-text" value="{{ $selectedRow['fixed_term_detail'] ?? '' }}">
            </div>
          </label>
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>勤務時間</span>
            <input type="text" name="work_schedule_1" value="{{ $selectedRow['work_schedule_1'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>勤務時間</span>
            <input type="text" name="work_schedule_2" value="{{ $selectedRow['work_schedule_2'] ?? '' }}">
          </label>
          <label class="detail-field detail-field-compact">
            <span>試用期間</span>
            <div class="checkbox-line"><input type="checkbox" name="trial" value="1" @checked((string)($selectedRow['trial'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['trial'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> あり</div>
          </label>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">勤務条件</div>
        <div class="info-block-grid">
          <label class="detail-field detail-field-compact">
            <span>有休</span>
            <div class="checkbox-line"><input type="checkbox" name="yukyu" value="1" @checked((string)($selectedRow['yukyu'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['yukyu'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> あり</div>
          </label>
          <label class="detail-field detail-field-compact">
            <span>有休加算月</span>
            <input type="text" name="yukyu_month" value="{{ $selectedRow['yukyu_month'] ?? '' }}">
          </label>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>所定労働時間(日)</span>
              <input type="text" name="working_time" value="{{ $selectedRow['working_time'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>所定労働時間(週)</span>
              <input type="text" name="weekly_working_time" value="{{ $selectedRow['weekly_working_time'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>所定労働時間(月)</span>
              <input type="text" name="year_working_time" value="{{ $selectedRow['year_working_time'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>車通勤km</span>
              <input type="text" name="car_km" value="{{ $selectedRow['car_km'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>日額交通費</span>
              <input type="text" name="traffic_day" value="{{ $selectedRow['traffic_day'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>追加交通費日額</span>
              <input type="text" name="traffic_day_tuika" value="{{ $selectedRow['traffic_day_tuika'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair detail-pair-inline detail-pair-wide">
            <label class="detail-field detail-field-compact">
              <span>業務委託割合</span>
              <input type="text" name="percentage_1" value="{{ $selectedRow['percentage_1'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>業務委託割合</span>
              <input type="text" name="percentage_2" value="{{ $selectedRow['percentage_2'] ?? '' }}">
            </label>
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">振込口座</div>
        <div class="info-block-grid">
          <label class="detail-field detail-field-compact detail-field-wide">
            <span>振込先</span>
            <input type="text" name="transfer_purpose" value="{{ $selectedRow['transfer_purpose'] ?? '' }}">
          </label>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>銀行名1</span>
              <input type="text" name="bank_name_1" value="{{ $selectedRow['bank_name_1'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>支店名1</span>
              <input type="text" name="bank_branch_1" value="{{ $selectedRow['bank_branch_1'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>口座種別1</span>
              <input type="text" name="account_type" value="{{ $selectedRow['account_type'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>口座番号1</span>
              <input type="text" name="account_num" value="{{ $selectedRow['account_num'] ?? '' }}">
            </label>
          </div>
          <div class="detail-divider detail-divider-wide"></div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>銀行名2</span>
              <input type="text" name="bank_name_2" value="{{ $selectedRow['bank_name_2'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>支店名2</span>
              <input type="text" name="bank_branch_2" value="{{ $selectedRow['bank_branch_2'] ?? '' }}">
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>口座種別2</span>
              <input type="text" name="account_type2" value="{{ $selectedRow['account_type2'] ?? '' }}">
            </label>
            <label class="detail-field detail-field-compact">
              <span>口座番号2</span>
              <input type="text" name="account_num2" value="{{ $selectedRow['account_num2'] ?? '' }}">
            </label>
          </div>
        </div>
      </section>

      <section class="info-block">
        <div class="info-block-title">変更履歴</div>
        <label class="detail-field detail-field-wide detail-field-textarea detail-field-textarea-no-label">
          <textarea name="change_history">{{ $selectedRow['change_history'] ?? '' }}</textarea>
        </label>
      </section>

      <section class="info-block">
        <div class="info-block-title">権限・その他</div>
        <div class="info-block-grid">
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>全権管理者</span>
              <div class="checkbox-line"><input type="checkbox" name="is_admin" value="1" @checked((string)($selectedRow['is_admin'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_admin'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>往診スタッフ</span>
              <div class="checkbox-line"><input type="checkbox" name="oushin_staff" value="1" @checked((string)($selectedRow['oushin_staff'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['oushin_staff'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
            <label class="detail-field detail-field-compact">
              <span>店舗システム</span>
              <div class="checkbox-line"><input type="checkbox" name="front_staff" value="1" @checked((string)($selectedRow['front_staff'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['front_staff'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>往診事務</span>
              <div class="checkbox-line"><input type="checkbox" name="is_accounting_user" value="1" @checked((string)($selectedRow['is_accounting_user'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_accounting_user'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
            <label class="detail-field detail-field-compact">
              <span>事務所</span>
              <div class="checkbox-line"><input type="checkbox" name="is_payment_check_user" value="1" @checked((string)($selectedRow['is_payment_check_user'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_payment_check_user'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>往診管理</span>
              <div class="checkbox-line"><input type="checkbox" name="is_visit_management_user" value="1" @checked((string)($selectedRow['is_visit_management_user'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_visit_management_user'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
            <label class="detail-field detail-field-compact">
              <span>往診売上</span>
              <div class="checkbox-line"><input type="checkbox" name="is_view_only_user" value="1" @checked((string)($selectedRow['is_view_only_user'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_view_only_user'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
          </div>
          <div class="detail-pair">
            <label class="detail-field detail-field-compact">
              <span>店舗管理</span>
              <div class="checkbox-line"><input type="checkbox" name="is_store_management_user" value="1" @checked((string)($selectedRow['is_store_management_user'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_store_management_user'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
            <label class="detail-field detail-field-compact">
              <span>店舗日報</span>
              <div class="checkbox-line"><input type="checkbox" name="is_daily_report_user" value="1" @checked((string)($selectedRow['is_daily_report_user'] ?? '' ) !=='' && !in_array(mb_strtolower((string)($selectedRow['is_daily_report_user'] ?? '' )), ['0','false','no','off','なし','無','null'], true))> 可</div>
            </label>
          </div>
          <label class="detail-field detail-field-compact detail-field-wide">
            <span>パスワード</span>
            <input type="text" name="password" value="{{ $selectedRow['password'] ?? '' }}">
          </label>
        </div>
      </section>

    </div>

  </div>
  </form>
