@php
  $weekOptions = ['月', '火', '水', '木', '金', '土', '日'];
  $shiftRows = $shiftRows ?? [];
@endphp

<div class="related-card">
  <div class="related-header">
    <h3>基本シフト</h3>
    <span>{{ number_format(count($shiftRows)) }} 件</span>
  </div>

  <div class="staff-tab-panels">
    <form method="post" action="{{ route('admin.master.staff.basic_shift.store') }}" class="info-block">
      @csrf
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="info-block-title">新規登録</div>
      <div class="info-block-grid">
        <label class="detail-field detail-field-compact">
          <span>曜日</span>
          <select name="week" required>
            <option value=""></option>
            @foreach($weekOptions as $week)
              <option value="{{ $week }}">{{ $week }}</option>
            @endforeach
          </select>
        </label>
        <label class="detail-field detail-field-compact">
          <span>始業</span>
          <input type="time" name="shift_start" step="900">
        </label>
        <label class="detail-field detail-field-compact">
          <span>退出</span>
          <input type="time" name="shift_exit" step="900">
        </label>
        <label class="detail-field detail-field-compact">
          <span>入出</span>
          <input type="time" name="shift_in_out" step="900">
        </label>
        <label class="detail-field detail-field-compact">
          <span>終業</span>
          <input type="time" name="shift_end" step="900">
        </label>
        <label class="detail-field detail-field-compact">
          <span>勤務店舗</span>
          <select name="shop_code">
            <option value=""></option>
            @foreach(($storeOptions ?? []) as $store)
              <option value="{{ $store['store_code'] }}">{{ $store['store_name'] !== '' ? $store['store_name'] : $store['store_code'] }}</option>
            @endforeach
          </select>
        </label>
      </div>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">登録</button>
      </div>
    </form>

    @if($shiftRows !== [])
      <div class="staff-content-table-wrap">
        <table>
          <thead>
            <tr>
              <th>曜日</th>
              <th>始業</th>
              <th>退出</th>
              <th>入出</th>
              <th>終業</th>
              <th>勤務店舗</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            @foreach($shiftRows as $row)
              @php $formId = 'basic-shift-form-' . ($row['shift_no'] ?? ''); @endphp
              <tr>
                <td>
                  <select form="{{ $formId }}" name="week">
                    @foreach($weekOptions as $week)
                      <option value="{{ $week }}" @selected(($row['week'] ?? '') === $week)>{{ $week }}</option>
                    @endforeach
                  </select>
                </td>
                <td><input form="{{ $formId }}" type="time" name="shift_start" step="900" value="{{ $row['shift_start'] ?? '' }}"></td>
                <td><input form="{{ $formId }}" type="time" name="shift_exit" step="900" value="{{ $row['shift_exit'] ?? '' }}"></td>
                <td><input form="{{ $formId }}" type="time" name="shift_in_out" step="900" value="{{ $row['shift_in_out'] ?? '' }}"></td>
                <td><input form="{{ $formId }}" type="time" name="shift_end" step="900" value="{{ $row['shift_end'] ?? '' }}"></td>
                <td>
                  <select form="{{ $formId }}" name="shop_code">
                    <option value=""></option>
                    @foreach(($storeOptions ?? []) as $store)
                      <option value="{{ $store['store_code'] }}" @selected(($row['shop_code'] ?? '') === $store['store_code'])>{{ $store['store_name'] !== '' ? $store['store_name'] : $store['store_code'] }}</option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <form id="{{ $formId }}" method="post" action="{{ route('admin.master.staff.basic_shift.update') }}">
                    @csrf
                    <input type="hidden" name="shift_no" value="{{ $row['shift_no'] ?? '' }}">
                    <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
                    <input type="hidden" name="q" value="{{ $keyword }}">
                    <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">
                  </form>
                  <button form="{{ $formId }}" type="submit" name="_action" value="register" class="btn-primary">保存</button>
                  <button form="{{ $formId }}" type="submit" name="_action" value="clear" class="btn-secondary" formnovalidate>クリア</button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="staff-empty">データがありません</div>
    @endif
  </div>
</div>
