@php
  $mayorSeed = $selectedMayorRow ?? [];
@endphp

<div class="current-rate-card mayor-tax-card" id="mayor-current-card">
  <div class="rate-history-header">
    <h3>特別徴収</h3>
    <div class="current-rate-actions">
      <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('mayor-current-card', 'creating')">新規追加</button>
    </div>
  </div>

  @if($mayorRows !== [])
    <div class="rate-table-wrap">
      <table class="rate-table mayor-tax-table">
        <thead>
          <tr>
            <th>市町村名</th>
            <th>指定番号</th>
            <th>市区町村コード</th>
            <th>市区町村</th>
            <th>電話番号</th>
            <th class="action-cell">操作</th>
          </tr>
        </thead>
        <tbody>
          @foreach($mayorRows as $row)
            <tr class="mayor-row" id="mayor-row-{{ $row['mayor_no'] }}">
              <td>{{ $row['mayor'] !== '' ? $row['mayor'] : '---' }}</td>
              <td>{{ $row['specified_num'] !== '' ? $row['specified_num'] : '---' }}</td>
              <td>{{ $row['city_cord'] !== '' ? $row['city_cord'] : '---' }}</td>
              <td>{{ $row['city_hall'] !== '' ? $row['city_hall'] : '---' }}</td>
              <td>{{ $row['city_hall_tel'] !== '' ? $row['city_hall_tel'] : '---' }}</td>
              <td class="action-cell">
                <button type="button" class="btn-secondary" onclick="toggleMayorRowEdit('mayor-row-{{ $row['mayor_no'] }}', true)">編集</button>
              </td>
            </tr>
            <tr class="mayor-row-edit">
              <td colspan="6">
                <form method="post" action="{{ route('admin.master.company.mayor.update') }}">
                  @csrf
                  <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
                  <input type="hidden" name="q" value="{{ $keyword }}">
                  <input type="hidden" name="mayor_no" value="{{ $row['mayor_no'] }}">

                  <div class="mayor-inline-grid">
                    <label class="detail-field">
                      <span>市町村名</span>
                      <input type="text" name="mayor" value="{{ $row['mayor'] }}">
                    </label>
                    <label class="detail-field">
                      <span>指定番号</span>
                      <input type="text" name="specified_num" value="{{ $row['specified_num'] }}">
                    </label>
                    <label class="detail-field">
                      <span>税務署</span>
                      <input type="text" name="tax_office" value="{{ $row['tax_office'] }}">
                    </label>
                    <label class="detail-field">
                      <span>市区町村コード</span>
                      <input type="text" name="city_cord" value="{{ $row['city_cord'] }}">
                    </label>
                    <label class="detail-field">
                      <span>郵便番号</span>
                      <input type="text" name="city_hall_post" value="{{ $row['city_hall_post'] }}">
                    </label>
                    <label class="detail-field">
                      <span>市区町村</span>
                      <input type="text" name="city_hall" value="{{ $row['city_hall'] }}">
                    </label>
                    <label class="detail-field">
                      <span>担当部署</span>
                      <input type="text" name="city_hall_department" value="{{ $row['city_hall_department'] }}">
                    </label>
                    <label class="detail-field">
                      <span>電話番号</span>
                      <input type="text" name="city_hall_tel" value="{{ $row['city_hall_tel'] }}">
                    </label>
                    <label class="detail-field detail-field-wide">
                      <span>市区町村住所</span>
                      <input type="text" name="city_hall_add" value="{{ $row['city_hall_add'] }}">
                    </label>
                  </div>

                  <div class="detail-actions detail-actions-spread">
                    <button
                      type="submit"
                      formaction="{{ route('admin.master.company.mayor.delete') }}"
                      class="btn-secondary btn-danger-soft"
                      onclick="return confirm('住民税データを削除しますか？');"
                    >削除</button>
                    <div class="detail-actions-right">
                      <button type="submit">保存</button>
                      <button type="button" class="btn-secondary" onclick="toggleMayorRowEdit('mayor-row-{{ $row['mayor_no'] }}', false)">取消</button>
                    </div>
                  </div>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="company-empty">データがありません</div>
  @endif

  <form method="post" action="{{ route('admin.master.company.mayor.store') }}" class="current-rate-create">
    @csrf
    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
    <input type="hidden" name="q" value="{{ $keyword }}">

    <div class="current-rate-meta current-rate-meta-grid current-rate-meta-edit-grid">
      <div class="current-rate-meta-item">
        <span>市町村名</span>
        <input type="text" name="mayor" value="{{ $mayorSeed['mayor'] ?? '' }}">
      </div>
      <div class="current-rate-meta-item">
        <span>指定番号</span>
        <input type="text" name="specified_num" value="{{ $mayorSeed['specified_num'] ?? '' }}">
      </div>
      <div class="current-rate-meta-item">
        <span>税務署</span>
        <input type="text" name="tax_office" value="{{ $mayorSeed['tax_office'] ?? '' }}">
      </div>
    </div>

    <div class="detail-grid">
      <label class="detail-field">
        <span>市区町村コード</span>
        <input type="text" name="city_cord" value="{{ $mayorSeed['city_cord'] ?? '' }}">
      </label>
      <label class="detail-field">
        <span>郵便番号</span>
        <input type="text" name="city_hall_post" value="{{ $mayorSeed['city_hall_post'] ?? '' }}">
      </label>
      <label class="detail-field">
        <span>市区町村</span>
        <input type="text" name="city_hall" value="{{ $mayorSeed['city_hall'] ?? '' }}">
      </label>
      <label class="detail-field">
        <span>担当部署</span>
        <input type="text" name="city_hall_department" value="{{ $mayorSeed['city_hall_department'] ?? '' }}">
      </label>
      <label class="detail-field">
        <span>電話番号</span>
        <input type="text" name="city_hall_tel" value="{{ $mayorSeed['city_hall_tel'] ?? '' }}">
      </label>
      <label class="detail-field detail-field-wide">
        <span>市区町村住所</span>
        <input type="text" name="city_hall_add" value="{{ $mayorSeed['city_hall_add'] ?? '' }}">
      </label>
    </div>

    <div class="detail-actions detail-actions-right">
      <button type="submit">追加</button>
      <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('mayor-current-card', '')">取消</button>
    </div>
  </form>
</div>
