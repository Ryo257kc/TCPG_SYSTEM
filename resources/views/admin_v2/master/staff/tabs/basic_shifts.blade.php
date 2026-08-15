@php
  $weekOptions = $weekOptions ?? ['月', '火', '水', '木', '金', '土', '日'];
  $shiftRows = $shiftRows ?? [];
@endphp

<div class="related-card">
  <div class="related-header">
    <h3>基本シフト</h3>
    <span>{{ number_format(count($shiftRows)) }} 件</span>
  </div>

  <div class="staff-tab-panels">
    @if($shiftRows === [])
    <form method="post" action="{{ route('admin.master.staff.basic_shift.create_week') }}" class="info-block">
      @csrf
      <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="employment_filter" value="{{ $employmentFilter }}">
      <input type="hidden" name="company_filter" value="{{ $companyFilter }}">

      <div class="info-block-title">新規登録</div>
      <p>基本シフトは1名につき月〜日の7日分をセットで作成します。作成後は下の一覧から曜日ごとに編集してください。</p>
      <div class="detail-actions">
        <button type="submit" class="btn-primary">7日分を作成</button>
      </div>
    </form>
    @endif

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
