@php
  $rate2 = static function ($value): string {
      if ($value === null || $value === '') {
          return '---';
      }
      return is_numeric($value) ? number_format((float) $value, 2, '.', '') : (string) $value;
  };

  $doubleRate = static function ($value): string {
      if ($value === null || $value === '' || ! is_numeric($value)) {
          return '---';
      }
      return number_format(((float) $value) * 2, 2, '.', '');
  };

  $rateInput = static function ($value): string {
      if ($value === null || $value === '' || ! is_numeric($value)) {
          return '';
      }
      return number_format((float) $value, 2, '.', '');
  };

  $syahoSeed = $selectedSyahoRow ?? [];
@endphp

<form method="post" action="{{ route('admin.master.company.update') }}" class="company-detail-form company-info-form" id="syaho-company-form">
  @csrf
  <input type="hidden" name="company_id" value="{{ $selectedRow['company_id'] }}">
  <input type="hidden" name="q" value="{{ $keyword }}">
  <input type="hidden" name="tab" value="syaho">

  <div class="company-info-view company-view">
    <div class="detail-grid">
      <div class="detail-field">
        <span>健保事業所記号</span>
        <div class="company-display-value {{ $selectedRow['health_office_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['health_office_code'] !== '' ? $selectedRow['health_office_code'] : '---' }}</div>
      </div>

      <div class="detail-field">
        <span>年金事業所番号</span>
        <div class="company-display-value {{ $selectedRow['pension_office_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['pension_office_code'] !== '' ? $selectedRow['pension_office_code'] : '---' }}</div>
      </div>
      <div class="detail-field">
        <span>事業所番号（保険料納入告知番号）</span>
        <div class="company-display-value {{ $selectedRow['office_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['office_number'] !== '' ? $selectedRow['office_number'] : '---' }}</div>
      </div>

      <div class="detail-field-wide company-person-stack">
        <div class="detail-field">
          <span>保険者番号</span>
          <div class="company-display-value {{ $selectedRow['insurer_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['insurer_number'] !== '' ? $selectedRow['insurer_number'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>保険者名称</span>
          <div class="company-display-value {{ $selectedRow['insurer_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['insurer_name'] !== '' ? $selectedRow['insurer_name'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>保険者住所</span>
          <div class="company-display-value {{ $selectedRow['insurer_address'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['insurer_address'] !== '' ? $selectedRow['insurer_address'] : '---' }}</div>
        </div>
      </div>
    </div>

    <div class="detail-actions">
      <button type="button" class="btn-secondary" onclick="toggleCompanyInfoEdit('syaho-company-form', true)">編集</button>
    </div>
  </div>

  <div class="company-info-edit company-edit" hidden>
    <div class="detail-grid">
      <label class="detail-field">
        <span>健保事業所記号</span>
        <input type="text" name="health_office_code" value="{{ $selectedRow['health_office_code'] }}">
      </label>
      <label class="detail-field">
        <span>年金事業所番号</span>
        <input type="text" name="pension_office_code" value="{{ $selectedRow['pension_office_code'] }}">
      </label>
      <label class="detail-field">
        <span>事業所番号（保険料納入告知番号）</span>
        <input type="text" name="office_number" value="{{ $selectedRow['office_number'] }}">
      </label>
      <div class="detail-field-wide company-person-stack">
        <label class="detail-field">
          <span>保険者番号</span>
          <input type="text" name="insurer_number" value="{{ $selectedRow['insurer_number'] }}">
        </label>
        <label class="detail-field">
          <span>保険者名称</span>
          <input type="text" name="insurer_name" value="{{ $selectedRow['insurer_name'] }}">
        </label>
        <label class="detail-field">
          <span>保険者住所</span>
          <input type="text" name="insurer_address" value="{{ $selectedRow['insurer_address'] }}">
        </label>
      </div>
    </div>

    <div class="detail-actions">
      <button type="submit">保存</button>
      <button type="button" class="btn-secondary" onclick="toggleCompanyInfoEdit('syaho-company-form', false)">取消</button>
    </div>
  </div>
</form>

  <div class="current-rate-card" id="syaho-current-card">
    <div class="rate-history-header">
      <h3>現在料率</h3>
      <div class="current-rate-actions current-rate-view">
        @if($selectedSyahoRow)
          <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('syaho-current-card', 'editing')">編集</button>
        @endif
        <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('syaho-current-card', 'creating')">新規追加</button>
      </div>
    </div>

    <div class="current-rate-view">
      @if($selectedSyahoRow)
        <div class="current-rate-meta">
          <div class="current-rate-meta-item">
            <span>支払基礎日数</span>
            <strong>{{ ($selectedSyahoRow['basic_payment_days'] ?? '') !== '' ? $selectedSyahoRow['basic_payment_days'] : '---' }}</strong>
          </div>
        </div>

        <div class="current-rate-summary current-rate-summary-single">
          <table class="current-rate-table">
            <thead>
              <tr>
                <th>区分</th>
                <th>適用日</th>
                <th>給与</th>
                <th>賞与</th>
                <th>給与合計</th>
                <th>賞与合計</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th>健康保険</th>
                <td>{{ ($selectedSyahoRow['kenpo_apply_date'] ?? '') !== '' ? $selectedSyahoRow['kenpo_apply_date'] : '---' }}</td>
                <td>{{ $rate2($selectedSyahoRow['kenpo_kyuyo'] ?? '') }}</td>
                <td>{{ $rate2($selectedSyahoRow['kenpo_shoyo'] ?? '') }}</td>
                <td>{{ $doubleRate($selectedSyahoRow['kenpo_kyuyo'] ?? '') }}</td>
                <td>{{ $doubleRate($selectedSyahoRow['kenpo_shoyo'] ?? '') }}</td>
              </tr>
              <tr>
                <th>介護保険</th>
                <td>{{ ($selectedSyahoRow['kaigo_apply_date'] ?? '') !== '' ? $selectedSyahoRow['kaigo_apply_date'] : '---' }}</td>
                <td>{{ $rate2($selectedSyahoRow['kaigo_kyuyo'] ?? '') }}</td>
                <td>{{ $rate2($selectedSyahoRow['kaigo_shoyo'] ?? '') }}</td>
                <td>{{ $doubleRate($selectedSyahoRow['kaigo_kyuyo'] ?? '') }}</td>
                <td>{{ $doubleRate($selectedSyahoRow['kaigo_shoyo'] ?? '') }}</td>
              </tr>
              <tr>
                <th>厚生年金</th>
                <td>{{ ($selectedSyahoRow['kou_apply_date'] ?? '') !== '' ? $selectedSyahoRow['kou_apply_date'] : '---' }}</td>
                <td>{{ $rate2($selectedSyahoRow['kou_kyuyo'] ?? '') }}</td>
                <td>{{ $rate2($selectedSyahoRow['kou_shoyo'] ?? '') }}</td>
                <td>{{ $doubleRate($selectedSyahoRow['kou_kyuyo'] ?? '') }}</td>
                <td>{{ $doubleRate($selectedSyahoRow['kou_shoyo'] ?? '') }}</td>
              </tr>
              <tr>
                <th>児童手当拠出金</th>
                <td>{{ ($selectedSyahoRow['jidou_apply_date'] ?? '') !== '' ? $selectedSyahoRow['jidou_apply_date'] : '---' }}</td>
                <td>{{ $rate2($selectedSyahoRow['jidou_kyuyo'] ?? '') }}</td>
                <td>{{ $rate2($selectedSyahoRow['jidou_shoyo'] ?? '') }}</td>
                <td>---</td>
                <td>---</td>
              </tr>
            </tbody>
          </table>
        </div>
      @else
        <div class="company-empty">現在料率がありません</div>
      @endif
    </div>

    @if($selectedSyahoRow)
      <form id="syaho-delete-form" method="post" action="{{ route('admin.master.company.syaho.delete') }}" hidden>
        @csrf
        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
        <input type="hidden" name="q" value="{{ $keyword }}">
        <input type="hidden" name="syaho_no" value="{{ $selectedSyahoRow['syaho_no'] ?? '' }}">
      </form>

      <form method="post" action="{{ route('admin.master.company.syaho.update') }}" class="current-rate-edit">
        @csrf
        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
        <input type="hidden" name="q" value="{{ $keyword }}">
        <input type="hidden" name="syaho_no" value="{{ $selectedSyahoRow['syaho_no'] ?? '' }}">

        <table class="current-rate-table current-rate-table-edit">
          <thead>
            <tr>
              <th>区分</th>
              <th>適用日</th>
              <th>給与</th>
              <th>賞与</th>
              <th>給与合計</th>
              <th>賞与合計</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>健康保険</th>
              <td><input type="date" name="kenpo_apply_date" value="{{ $selectedSyahoRow['kenpo_apply_date'] ?? '' }}"></td>
              <td><input type="number" step="0.01" name="kenpo_kyuyo" value="{{ $rateInput($selectedSyahoRow['kenpo_kyuyo'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="kenpo_shoyo" value="{{ $rateInput($selectedSyahoRow['kenpo_shoyo'] ?? '') }}"></td>
              <td>{{ $doubleRate($selectedSyahoRow['kenpo_kyuyo'] ?? '') }}</td>
              <td>{{ $doubleRate($selectedSyahoRow['kenpo_shoyo'] ?? '') }}</td>
            </tr>
            <tr>
              <th>介護保険</th>
              <td><input type="date" name="kaigo_apply_date" value="{{ $selectedSyahoRow['kaigo_apply_date'] ?? '' }}"></td>
              <td><input type="number" step="0.01" name="kaigo_kyuyo" value="{{ $rateInput($selectedSyahoRow['kaigo_kyuyo'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="kaigo_shoyo" value="{{ $rateInput($selectedSyahoRow['kaigo_shoyo'] ?? '') }}"></td>
              <td>{{ $doubleRate($selectedSyahoRow['kaigo_kyuyo'] ?? '') }}</td>
              <td>{{ $doubleRate($selectedSyahoRow['kaigo_shoyo'] ?? '') }}</td>
            </tr>
            <tr>
              <th>厚生年金</th>
              <td><input type="date" name="kou_apply_date" value="{{ $selectedSyahoRow['kou_apply_date'] ?? '' }}"></td>
              <td><input type="number" step="0.01" name="kou_kyuyo" value="{{ $rateInput($selectedSyahoRow['kou_kyuyo'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="kou_shoyo" value="{{ $rateInput($selectedSyahoRow['kou_shoyo'] ?? '') }}"></td>
              <td>{{ $doubleRate($selectedSyahoRow['kou_kyuyo'] ?? '') }}</td>
              <td>{{ $doubleRate($selectedSyahoRow['kou_shoyo'] ?? '') }}</td>
            </tr>
            <tr>
              <th>児童手当拠出金</th>
              <td><input type="date" name="jidou_apply_date" value="{{ $selectedSyahoRow['jidou_apply_date'] ?? '' }}"></td>
              <td><input type="number" step="0.01" name="jidou_kyuyo" value="{{ $rateInput($selectedSyahoRow['jidou_kyuyo'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="jidou_shoyo" value="{{ $rateInput($selectedSyahoRow['jidou_shoyo'] ?? '') }}"></td>
              <td>---</td>
              <td>---</td>
            </tr>
          </tbody>
        </table>

        <div class="current-rate-meta current-rate-meta-edit">
          <label class="detail-field">
            <span>支払基礎日数</span>
            <input type="text" name="basic_payment_days" value="{{ $selectedSyahoRow['basic_payment_days'] ?? '' }}">
          </label>
        </div>

        <div class="detail-actions detail-actions-spread">
          <button type="submit" form="syaho-delete-form" class="btn-secondary btn-danger-soft" onclick="return confirm('現在料率を削除しますか？');">削除</button>
          <div class="detail-actions-right">
            <button type="submit">保存</button>
            <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('syaho-current-card', '')">取消</button>
          </div>
        </div>
      </form>
    @endif

    <form method="post" action="{{ route('admin.master.company.syaho.store') }}" class="current-rate-create">
      @csrf
      <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">

      <table class="current-rate-table current-rate-table-edit">
        <thead>
          <tr>
            <th>区分</th>
            <th>適用日</th>
            <th>給与</th>
            <th>賞与</th>
            <th>給与合計</th>
            <th>賞与合計</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>健康保険</th>
            <td><input type="date" name="kenpo_apply_date" value="{{ $syahoSeed['kenpo_apply_date'] ?? '' }}"></td>
            <td><input type="number" step="0.01" name="kenpo_kyuyo" value="{{ $rateInput($syahoSeed['kenpo_kyuyo'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="kenpo_shoyo" value="{{ $rateInput($syahoSeed['kenpo_shoyo'] ?? '') }}"></td>
            <td>{{ $doubleRate($syahoSeed['kenpo_kyuyo'] ?? '') }}</td>
            <td>{{ $doubleRate($syahoSeed['kenpo_shoyo'] ?? '') }}</td>
          </tr>
          <tr>
            <th>介護保険</th>
            <td><input type="date" name="kaigo_apply_date" value="{{ $syahoSeed['kaigo_apply_date'] ?? '' }}"></td>
            <td><input type="number" step="0.01" name="kaigo_kyuyo" value="{{ $rateInput($syahoSeed['kaigo_kyuyo'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="kaigo_shoyo" value="{{ $rateInput($syahoSeed['kaigo_shoyo'] ?? '') }}"></td>
            <td>{{ $doubleRate($syahoSeed['kaigo_kyuyo'] ?? '') }}</td>
            <td>{{ $doubleRate($syahoSeed['kaigo_shoyo'] ?? '') }}</td>
          </tr>
          <tr>
            <th>厚生年金</th>
            <td><input type="date" name="kou_apply_date" value="{{ $syahoSeed['kou_apply_date'] ?? '' }}"></td>
            <td><input type="number" step="0.01" name="kou_kyuyo" value="{{ $rateInput($syahoSeed['kou_kyuyo'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="kou_shoyo" value="{{ $rateInput($syahoSeed['kou_shoyo'] ?? '') }}"></td>
            <td>{{ $doubleRate($syahoSeed['kou_kyuyo'] ?? '') }}</td>
            <td>{{ $doubleRate($syahoSeed['kou_shoyo'] ?? '') }}</td>
          </tr>
          <tr>
            <th>児童手当拠出金</th>
            <td><input type="date" name="jidou_apply_date" value="{{ $syahoSeed['jidou_apply_date'] ?? '' }}"></td>
            <td><input type="number" step="0.01" name="jidou_kyuyo" value="{{ $rateInput($syahoSeed['jidou_kyuyo'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="jidou_shoyo" value="{{ $rateInput($syahoSeed['jidou_shoyo'] ?? '') }}"></td>
            <td>---</td>
            <td>---</td>
          </tr>
        </tbody>
      </table>

      <div class="current-rate-meta current-rate-meta-edit">
        <label class="detail-field">
          <span>支払基礎日数</span>
          <input type="text" name="basic_payment_days" value="{{ $syahoSeed['basic_payment_days'] ?? '' }}" placeholder="暦日基準">
        </label>
      </div>

      <div class="detail-actions detail-actions-right">
        <button type="submit">追加</button>
        <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('syaho-current-card', '')">取消</button>
      </div>
    </form>
  </div>

  <div class="rate-history">
    <div class="rate-history-header">
      <h3>履歴</h3>
      <span>{{ count($syahoRows) }}件</span>
    </div>
    @if($syahoRows !== [])
      <div class="rate-table-wrap">
        <table class="rate-table">
          <thead>
            <tr>
              <th>健保適用日</th>
              <th>健保給与</th>
              <th>健保賞与</th>
              <th>介護適用日</th>
              <th>介護給与</th>
              <th>介護賞与</th>
              <th>厚年適用日</th>
              <th>厚年給与</th>
              <th>厚年賞与</th>
              <th>児童適用日</th>
              <th>児童給与</th>
              <th>児童賞与</th>
              <th>支払基礎日数</th>
            </tr>
          </thead>
          <tbody>
            @foreach($syahoRows as $row)
              <tr>
                <td>{{ $row['kenpo_apply_date'] !== '' ? $row['kenpo_apply_date'] : '---' }}</td>
                <td>{{ $rate2($row['kenpo_kyuyo']) }}</td>
                <td>{{ $rate2($row['kenpo_shoyo']) }}</td>
                <td>{{ $row['kaigo_apply_date'] !== '' ? $row['kaigo_apply_date'] : '---' }}</td>
                <td>{{ $rate2($row['kaigo_kyuyo']) }}</td>
                <td>{{ $rate2($row['kaigo_shoyo']) }}</td>
                <td>{{ $row['kou_apply_date'] !== '' ? $row['kou_apply_date'] : '---' }}</td>
                <td>{{ $rate2($row['kou_kyuyo']) }}</td>
                <td>{{ $rate2($row['kou_shoyo']) }}</td>
                <td>{{ $row['jidou_apply_date'] !== '' ? $row['jidou_apply_date'] : '---' }}</td>
                <td>{{ $rate2($row['jidou_kyuyo']) }}</td>
                <td>{{ $rate2($row['jidou_shoyo']) }}</td>
                <td>{{ $row['basic_payment_days'] !== '' ? $row['basic_payment_days'] : '---' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="company-empty">データがありません</div>
    @endif
  </div>
</div>
