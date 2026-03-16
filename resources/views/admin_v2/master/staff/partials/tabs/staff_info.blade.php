@php
  $employmentPairs = [
      ['_company_name', '_store_name'],
      ['staff_division', 'employment_status'],
      ['nyu_date', 'tai_date'],
      ['tax_amount', 'submission'],
  ];

  $insurancePairs = [
      ['syaho_num', 'koyou_num'],
      ['syaho_seiri_num', 'kiso_nenkin_num'],
      ['syaho', 'koyou'],
      ['syaho_date', 'koyou_date'],
  ];

  $bankPairs = [
      ['bank_name_1', 'bank_branch_1'],
      ['account_type', 'account_num'],
      ['bank_name_2', 'bank_branch_2'],
      ['account_type2', 'account_num2'],
  ];

  $rightsPairs = [
      ['oushin_staff', 'front_staff'],
      ['is_accounting_user', 'is_payment_check_user'],
      ['is_visit_management_user', 'is_view_only_user'],
      ['is_store_management_user', 'is_daily_report_user'],
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
      'post_num', 'address_furi', 'address', 'address1', 'address2', 'building',
      'home_tel', 'mobile_tel', 'head_house', 'relationship', 'spouse',
  ];

  $booleanFields = [
      'syaho', 'koyou', 'yukyu', 'has_fixed_term', 'trial', 'spouse',
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
              return '-';
          }

          $formatted = rtrim(rtrim(number_format($numeric, 4, '.', ''), '0'), '.');
          return $formatted === '' ? '-' : $formatted;
      }

      if ($value === '0' || $value === '0.0' || $value === '0.00') {
          return '-';
      }

      return $displayValue($field, $row);
  };

  $addressValue = trim(implode(' ', array_filter([
      $selectedRow['address'] ?? '',
      $selectedRow['address1'] ?? '',
      $selectedRow['address2'] ?? '',
      $selectedRow['building'] ?? '',
  ], fn ($value) => trim((string) $value) !== '')));
@endphp

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
          <span>{{ $fieldLabels['staff_id'] ?? 'staff_id' }}</span>
          <div class="detail-value {{ ($selectedRow['staff_id'] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow['staff_id'] ?? '') !== '' ? $selectedRow['staff_id'] : '---' }}</div>
        </label>
      @endif

      @if(array_key_exists('staff_name_furi', $selectedRow))
        <label class="detail-field detail-field-compact name-block-wide">
          <span>{{ $fieldLabels['staff_name_furi'] ?? 'staff_name_furi' }}</span>
          <div class="detail-value {{ ($selectedRow['staff_name_furi'] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow['staff_name_furi'] ?? '') !== '' ? $selectedRow['staff_name_furi'] : '---' }}</div>
        </label>
      @endif

      @foreach([['staff_name', 'display_name_ja'], ['sex', 'birthday'], ['my_number', '_age']] as [$leftField, $rightField])
        @php
          $leftExists = array_key_exists($leftField, $selectedRow);
          $rightExists = array_key_exists($rightField, $selectedRow);
        @endphp
        @if($leftExists || $rightExists)
          <div class="detail-pair detail-pair-inline detail-pair-wide">
            @if($leftExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$leftField] ?? $leftField }}</span>
                <div class="detail-value {{ ($selectedRow[$leftField] ?? '') === '' ? 'detail-value-empty' : '' }}">{{ ($selectedRow[$leftField] ?? '') !== '' ? $selectedRow[$leftField] : '---' }}</div>
              </label>
            @endif
            @if($rightExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$rightField] ?? $rightField }}</span>
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
          <span>{{ $fieldLabels['post_num'] ?? 'post_num' }}</span>
          <div class="detail-value {{ $selectedRow['post_num'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['post_num'] !== '' ? $selectedRow['post_num'] : '---' }}</div>
        </label>
      @endif

      @if(array_key_exists('address_furi', $selectedRow))
        <label class="detail-field detail-field-compact detail-field-tight address-block-wide">
          <span>{{ $fieldLabels['address_furi'] ?? 'address_furi' }}</span>
          <div class="detail-value {{ $selectedRow['address_furi'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['address_furi'] !== '' ? $selectedRow['address_furi'] : '---' }}</div>
        </label>
      @endif

      <label class="detail-field detail-field-compact detail-field-tight address-block-wide">
        <span>住所</span>
        <div class="detail-value {{ $addressValue === '' ? 'detail-value-empty' : '' }}">{{ $addressValue !== '' ? $addressValue : '---' }}</div>
      </label>

      @if(array_key_exists('home_tel', $selectedRow))
        <label class="detail-field detail-field-compact detail-field-tight">
          <span>{{ $fieldLabels['home_tel'] ?? 'home_tel' }}</span>
          <div class="detail-value {{ $selectedRow['home_tel'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['home_tel'] !== '' ? $selectedRow['home_tel'] : '---' }}</div>
        </label>
      @endif

      @if(array_key_exists('mobile_tel', $selectedRow))
        <label class="detail-field detail-field-compact detail-field-tight">
          <span>{{ $fieldLabels['mobile_tel'] ?? 'mobile_tel' }}</span>
          <div class="detail-value {{ $selectedRow['mobile_tel'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['mobile_tel'] !== '' ? $selectedRow['mobile_tel'] : '---' }}</div>
        </label>
      @endif

      <div class="detail-pair detail-pair-wide detail-pair-inline-3">
        @if(array_key_exists('spouse', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>{{ $fieldLabels['spouse'] ?? 'spouse' }}</span>
            <div class="{{ in_array('spouse', $booleanFields, true) ? 'detail-value-bool detail-value-bool-tight' : 'detail-value ' . $valueClass('spouse', $selectedRow) }}">{{ $displayValue('spouse', $selectedRow) }}</div>
          </label>
        @endif

        @if(array_key_exists('head_house', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>{{ $fieldLabels['head_house'] ?? 'head_house' }}</span>
            <div class="detail-value {{ $selectedRow['head_house'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['head_house'] !== '' ? $selectedRow['head_house'] : '---' }}</div>
          </label>
        @endif

        @if(array_key_exists('relationship', $selectedRow))
          <label class="detail-field detail-field-compact detail-field-tight">
            <span>{{ $fieldLabels['relationship'] ?? 'relationship' }}</span>
            <div class="detail-value {{ $selectedRow['relationship'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['relationship'] !== '' ? $selectedRow['relationship'] : '---' }}</div>
          </label>
        @endif
      </div>
    </div>
  </section>

  <section class="info-block">
    <div class="info-block-title">所属・雇用</div>
    <div class="info-block-grid">
      @foreach($employmentPairs as [$topField, $bottomField])
        @php
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
                <span>{{ $fieldLabels[$topField] ?? $topField }}</span>
                <div class="{{ $topIsBoolean ? 'detail-value-bool-rights' : 'detail-value ' . $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
              </label>
            @endif
            @if($bottomExists)
              @php $bottomIsBoolean = in_array($bottomField, $booleanFields, true); @endphp
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$bottomField] ?? $bottomField }}</span>
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
      @foreach(['business_content'] as $wideField)
        @php $usedFields[] = $wideField; @endphp
        @if(array_key_exists($wideField, $selectedRow))
          <label class="detail-field detail-field-compact contract-block-wide">
            <span>{{ $fieldLabels[$wideField] ?? $wideField }}</span>
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
          <span>{{ $fieldLabels['work_schedule_1'] ?? 'work_schedule_1' }}</span>
          <div class="detail-value {{ $valueClass('work_schedule_1', $selectedRow) }}">
            {{ $displayValue('work_schedule_1', $selectedRow) }}
          </div>
        </label>
      @endif

      @if(array_key_exists('work_schedule_2', $selectedRow))
        <label class="detail-field detail-field-compact contract-block-wide">
          <span>{{ $fieldLabels['work_schedule_2'] ?? 'work_schedule_2' }}</span>
          <div class="detail-value {{ $valueClass('work_schedule_2', $selectedRow) }}">
            {{ $displayValue('work_schedule_2', $selectedRow) }}
          </div>
        </label>
      @endif
    </div>
  </section>

  <section class="info-block">
    <div class="info-block-title">保険</div>
    <div class="info-block-grid">
      @foreach($insurancePairs as [$topField, $bottomField])
        @php
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
                <span>{{ $fieldLabels[$topField] ?? $topField }}</span>
                <div class="{{ $topIsBoolean ? 'detail-value-bool-rights' : 'detail-value ' . $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
              </label>
            @endif
            @if($bottomExists)
              @php $bottomIsBoolean = in_array($bottomField, $booleanFields, true); @endphp
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$bottomField] ?? $bottomField }}</span>
                <div class="{{ $bottomIsBoolean ? 'detail-value-bool-rights' : 'detail-value ' . $valueClass($bottomField, $selectedRow) }}">{{ $displayValue($bottomField, $selectedRow) }}</div>
              </label>
            @endif
          </div>
        @endif
      @endforeach
    </div>
  </section>

  <section class="info-block">
    <div class="info-block-title">勤務条件</div>
    <div class="info-block-grid">
      @foreach(['trial', 'yukyu'] as $field)
        @php $usedFields[] = $field; @endphp
        @if(array_key_exists($field, $selectedRow))
          <label class="detail-field detail-field-compact detail-field-work">
            <span>{{ $fieldLabels[$field] ?? $field }}</span>
            <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
          </label>
        @endif
      @endforeach

      @php $usedFields[] = 'yukyu_month'; @endphp
      @if(array_key_exists('yukyu_month', $selectedRow))
        <label class="detail-field detail-field-compact detail-field-work detail-field-wide">
          <span>{{ $fieldLabels['yukyu_month'] ?? 'yukyu_month' }}</span>
          <div class="detail-value {{ $valueClass('yukyu_month', $selectedRow) }}">{{ $workDisplayValue('yukyu_month', $selectedRow) }}</div>
        </label>
      @endif

      @php
        $workLeftFields = ['working_time', 'weekly_working_time', 'year_working_time'];
        $workRightFields = ['car_km', 'traffic_day', 'traffic_day_tuika'];
        foreach (array_merge($workLeftFields, $workRightFields, ['percentage_1', 'percentage_2']) as $field) {
            $usedFields[] = $field;
        }
      @endphp

      <div class="detail-pair">
        @foreach($workLeftFields as $field)
          @if(array_key_exists($field, $selectedRow))
            <label class="detail-field detail-field-compact detail-field-work">
              <span>{{ $fieldLabels[$field] ?? $field }}</span>
              <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
            </label>
          @endif
        @endforeach
      </div>

      <div class="detail-pair">
        @foreach($workRightFields as $field)
          @if(array_key_exists($field, $selectedRow))
            <label class="detail-field detail-field-compact detail-field-work">
              <span>{{ $fieldLabels[$field] ?? $field }}</span>
              <div class="detail-value {{ $valueClass($field, $selectedRow) }}">{{ $workDisplayValue($field, $selectedRow) }}</div>
            </label>
          @endif
        @endforeach
      </div>

      <div class="detail-pair detail-pair-wide detail-pair-inline">
        @foreach(['percentage_1', 'percentage_2'] as $field)
          @if(array_key_exists($field, $selectedRow))
            <label class="detail-field detail-field-compact detail-field-work">
              <span>{{ $fieldLabels[$field] ?? $field }}</span>
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
          <span>{{ $fieldLabels['transfer_purpose'] ?? 'transfer_purpose' }}</span>
          <div class="detail-value {{ $valueClass('transfer_purpose', $selectedRow) }}">{{ $displayValue('transfer_purpose', $selectedRow) }}</div>
        </label>
      @endif

      @foreach([['bank_name_1', 'bank_branch_1'], ['account_type', 'account_num']] as [$topField, $bottomField])
        @php
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
        @endphp
        @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$topField] ?? $topField }}</span>
                <div class="detail-value {{ $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
              </label>
            @endif
            @if($bottomExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$bottomField] ?? $bottomField }}</span>
                <div class="detail-value {{ $valueClass($bottomField, $selectedRow) }}">{{ $displayValue($bottomField, $selectedRow) }}</div>
              </label>
            @endif
          </div>
        @endif
      @endforeach

      <div class="detail-divider detail-divider-wide"></div>

      @foreach([['bank_name_2', 'bank_branch_2'], ['account_type2', 'account_num2']] as [$topField, $bottomField])
        @php
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
        @endphp
        @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$topField] ?? $topField }}</span>
                <div class="detail-value {{ $valueClass($topField, $selectedRow) }}">{{ $displayValue($topField, $selectedRow) }}</div>
              </label>
            @endif
            @if($bottomExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$bottomField] ?? $bottomField }}</span>
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
      @foreach($rightsPairs as [$topField, $bottomField])
        @php
          $usedFields[] = $topField;
          $usedFields[] = $bottomField;
          $topExists = array_key_exists($topField, $selectedRow);
          $bottomExists = array_key_exists($bottomField, $selectedRow);
        @endphp
        @if($topExists || $bottomExists)
          <div class="detail-pair">
            @if($topExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$topField] ?? $topField }}</span>
                <div class="detail-value {{ $valueClass($topField, $selectedRow) }} {{ in_array($topField, $booleanFields, true) ? 'detail-value-bool-rights' : '' }}">{{ $displayValue($topField, $selectedRow) }}</div>
              </label>
            @endif
            @if($bottomExists)
              <label class="detail-field detail-field-compact">
                <span>{{ $fieldLabels[$bottomField] ?? $bottomField }}</span>
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
            <span>{{ $fieldLabels[$field] ?? $field }}</span>
            <div class="detail-value {{ $selectedRow[$field] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow[$field] !== '' ? $selectedRow[$field] : '---' }}</div>
          </label>
        @endif
      @endforeach
    </div>
  </section>

  <section class="info-block info-block-wide">
    <div class="info-block-title">その他項目</div>
    <div class="detail-grid">
      @foreach($selectedRow as $field => $value)
        @continue(in_array($field, $usedFields, true))
        <label class="detail-field">
          <span>{{ $fieldLabels[$field] ?? $field }}</span>
          <div class="detail-value {{ $value === '' ? 'detail-value-empty' : '' }}">{{ $value !== '' ? $value : '---' }}</div>
        </label>
      @endforeach
    </div>
  </section>
</div>
