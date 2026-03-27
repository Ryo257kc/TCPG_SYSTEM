@php
  $rate2 = static function ($value): string {
      if ($value === null || $value === '') {
          return '---';
      }
      return is_numeric($value) ? number_format((float) $value, 2, '.', '') : (string) $value;
  };

  $sumRate = static function ($left, $right): string {
      if ((! is_numeric($left) || $left === '' || $left === null) && (! is_numeric($right) || $right === '' || $right === null)) {
          return '---';
      }
      return number_format((float) ($left ?: 0) + (float) ($right ?: 0), 2, '.', '');
  };

  $rateInput = static function ($value): string {
      if ($value === null || $value === '' || ! is_numeric($value)) {
          return '';
      }
      return number_format((float) $value, 2, '.', '');
  };

  $rouhoSeed = $selectedRouhoRow ?? [];
@endphp

<form method="post" action="{{ route('admin.master.company.update') }}" class="company-detail-form company-info-form company-info-form-rouho" id="rouho-company-form">
  @csrf
  <input type="hidden" name="company_id" value="{{ $selectedRow['company_id'] }}">
  <input type="hidden" name="q" value="{{ $keyword }}">
  <input type="hidden" name="tab" value="rouho">

  <div class="company-info-view company-view">
    <div class="detail-grid">
      <div class="detail-field">
        <span>労働保険番号</span>
        <div class="company-display-value {{ $selectedRow['labor_insurance_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_insurance_number'] !== '' ? $selectedRow['labor_insurance_number'] : '---' }}</div>
      </div>

      <div class="detail-field">
        <span>労働保険適用区分</span>
        <div class="company-display-value {{ $selectedRow['labor_install_category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_install_category'] !== '' ? $selectedRow['labor_install_category'] : '---' }}</div>
      </div>
      <div class="detail-field">
        <span>労保成立日</span>
        <div class="company-display-value {{ $selectedRow['labor_install_date'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_install_date'] !== '' ? $selectedRow['labor_install_date'] : '---' }}</div>
      </div>

      <div class="detail-field-wide company-person-stack">
        <div class="detail-field">
          <span>労働事業所区分</span>
          <div class="company-display-value {{ $selectedRow['labor_office_category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_office_category'] !== '' ? $selectedRow['labor_office_category'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>労働産業分類</span>
          <div class="company-display-value {{ $selectedRow['labor_industry_category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_industry_category'] !== '' ? $selectedRow['labor_industry_category'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>雇用保険事業者番号</span>
          <div class="company-display-value {{ $selectedRow['employment_office_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['employment_office_number'] !== '' ? $selectedRow['employment_office_number'] : '---' }}</div>
        </div>
      </div>
    </div>

    <div class="detail-actions">
      <button type="button" class="btn-secondary" onclick="toggleCompanyInfoEdit('rouho-company-form', true)">編集</button>
    </div>
  </div>

  <div class="company-info-edit company-edit" hidden>
    <div class="detail-grid">
      <label class="detail-field">
        <span>労働保険番号</span>
        <input type="text" name="labor_insurance_number" value="{{ $selectedRow['labor_insurance_number'] }}">
      </label>

      <label class="detail-field">
        <span>労働保険適用区分</span>
        <select name="labor_install_category">
          <option value=""></option>
          <option value="一般事業所" @selected(($selectedRow['labor_install_category'] ?? '') === '一般事業所')>一般事業所</option>
          <option value="農林水産業・清酒製造業" @selected(($selectedRow['labor_install_category'] ?? '') === '農林水産業・清酒製造業')>農林水産業・清酒製造業</option>
          <option value="建設業" @selected(($selectedRow['labor_install_category'] ?? '') === '建設業')>建設業</option>
        </select>
      </label>
      <label class="detail-field">
        <span>労保成立日</span>
        <input type="date" name="labor_install_date" value="{{ $selectedRow['labor_install_date'] }}">
      </label>

      <div class="detail-field-wide company-person-stack">
        <label class="detail-field">
          <span>労働事業所区分</span>
          <select name="labor_office_category">
            <option value=""></option>
            <option value="1 個別" @selected(($selectedRow['labor_office_category'] ?? '') === '1 個別')>1 個別</option>
            <option value="2 委託" @selected(($selectedRow['labor_office_category'] ?? '') === '2 委託')>2 委託</option>
          </select>
        </label>
        <label class="detail-field">
          <span>労働産業分類</span>
          <input type="text" name="labor_industry_category" value="{{ $selectedRow['labor_industry_category'] }}">
        </label>
        <label class="detail-field">
          <span>雇用保険事業者番号</span>
          <input type="text" name="employment_office_number" value="{{ $selectedRow['employment_office_number'] }}">
        </label>
      </div>
    </div>

    <div class="detail-actions">
      <button type="submit">保存</button>
      <button type="button" class="btn-secondary" onclick="toggleCompanyInfoEdit('rouho-company-form', false)">取消</button>
    </div>
  </div>
</form>

<div class="company-detail-form">
<div class="current-rate-card" id="rouho-current-card">
  <div class="rate-history-header">
    <h3>現在料率</h3>
    <div class="current-rate-actions current-rate-view">
      @if($selectedRouhoRow)
        <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('rouho-current-card', 'editing')">編集</button>
      @endif
      <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('rouho-current-card', 'creating')">新規追加</button>
    </div>
  </div>

  <div class="current-rate-view">
    @if($selectedRouhoRow)
      <div class="current-rate-meta current-rate-meta-grid">
        <div class="current-rate-meta-item">
          <span>適用日</span>
          <strong>{{ ($selectedRouhoRow['rou_apply_date'] ?? '') !== '' ? $selectedRouhoRow['rou_apply_date'] : '---' }}</strong>
        </div>
        <div class="current-rate-meta-item">
          <span>業種</span>
          <strong>{{ ($selectedRouhoRow['rou_industry'] ?? '') !== '' ? $selectedRouhoRow['rou_industry'] : '---' }}</strong>
        </div>
        <div class="current-rate-meta-item">
          <span>集計期間</span>
          <strong>{{ ($selectedRouhoRow['aggregate_period'] ?? '') !== '' ? $selectedRouhoRow['aggregate_period'] : '---' }}</strong>
        </div>
      </div>

      <div class="current-rate-summary">
        <table class="current-rate-table">
          <thead>
            <tr>
              <th>区分</th>
              <th>本人</th>
              <th>事業主</th>
              <th>合計</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>一般拠出金</th>
              <td>{{ $rate2($selectedRouhoRow['general_st'] ?? '') }}</td>
              <td>{{ $rate2($selectedRouhoRow['general_of'] ?? '') }}</td>
              <td>{{ $sumRate($selectedRouhoRow['general_st'] ?? '', $selectedRouhoRow['general_of'] ?? '') }}</td>
            </tr>
            <tr>
              <th>農林・清酒製造業等</th>
              <td>{{ $rate2($selectedRouhoRow['nourin_st'] ?? '') }}</td>
              <td>{{ $rate2($selectedRouhoRow['nourin_of'] ?? '') }}</td>
              <td>{{ $sumRate($selectedRouhoRow['nourin_st'] ?? '', $selectedRouhoRow['nourin_of'] ?? '') }}</td>
            </tr>
            <tr>
              <th>建設業</th>
              <td>{{ $rate2($selectedRouhoRow['kensetu_st'] ?? '') }}</td>
              <td>{{ $rate2($selectedRouhoRow['kensetu_of'] ?? '') }}</td>
              <td>{{ $sumRate($selectedRouhoRow['kensetu_st'] ?? '', $selectedRouhoRow['kensetu_of'] ?? '') }}</td>
            </tr>
          </tbody>
        </table>

        <div class="current-rate-sidecard">
          <span>労災保険率</span>
          <strong>{{ $rate2($selectedRouhoRow['rousai'] ?? '') }}</strong>
        </div>
      </div>
    @else
      <div class="company-empty">現在料率がありません</div>
    @endif
  </div>

  @if($selectedRouhoRow)
    <form id="rouho-delete-form" method="post" action="{{ route('admin.master.company.rouho.delete') }}" hidden>
      @csrf
      <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="rou_no" value="{{ $selectedRouhoRow['rou_no'] ?? '' }}">
    </form>

    <form method="post" action="{{ route('admin.master.company.rouho.update') }}" class="current-rate-edit">
      @csrf
      <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
      <input type="hidden" name="q" value="{{ $keyword }}">
      <input type="hidden" name="rou_no" value="{{ $selectedRouhoRow['rou_no'] ?? '' }}">

      <div class="current-rate-meta current-rate-meta-grid current-rate-meta-edit-grid">
        <div class="current-rate-meta-item">
          <span>適用日</span>
          <input type="date" name="rou_apply_date" value="{{ $selectedRouhoRow['rou_apply_date'] ?? '' }}">
        </div>
        <div class="current-rate-meta-item">
          <span>業種</span>
          <input type="text" name="rou_industry" value="{{ $selectedRouhoRow['rou_industry'] ?? '' }}">
        </div>
        <div class="current-rate-meta-item">
          <span>集計期間</span>
          <input type="text" name="aggregate_period" value="{{ $selectedRouhoRow['aggregate_period'] ?? '' }}">
        </div>
      </div>

      <div class="current-rate-summary">
        <table class="current-rate-table current-rate-table-edit">
          <thead>
            <tr>
              <th>区分</th>
              <th>本人</th>
              <th>事業主</th>
              <th>合計</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>一般拠出金</th>
              <td><input type="number" step="0.01" name="general_st" value="{{ $rateInput($selectedRouhoRow['general_st'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="general_of" value="{{ $rateInput($selectedRouhoRow['general_of'] ?? '') }}"></td>
              <td>{{ $sumRate($selectedRouhoRow['general_st'] ?? '', $selectedRouhoRow['general_of'] ?? '') }}</td>
            </tr>
            <tr>
              <th>農林・清酒製造業等</th>
              <td><input type="number" step="0.01" name="nourin_st" value="{{ $rateInput($selectedRouhoRow['nourin_st'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="nourin_of" value="{{ $rateInput($selectedRouhoRow['nourin_of'] ?? '') }}"></td>
              <td>{{ $sumRate($selectedRouhoRow['nourin_st'] ?? '', $selectedRouhoRow['nourin_of'] ?? '') }}</td>
            </tr>
            <tr>
              <th>建設業</th>
              <td><input type="number" step="0.01" name="kensetu_st" value="{{ $rateInput($selectedRouhoRow['kensetu_st'] ?? '') }}"></td>
              <td><input type="number" step="0.01" name="kensetu_of" value="{{ $rateInput($selectedRouhoRow['kensetu_of'] ?? '') }}"></td>
              <td>{{ $sumRate($selectedRouhoRow['kensetu_st'] ?? '', $selectedRouhoRow['kensetu_of'] ?? '') }}</td>
            </tr>
          </tbody>
        </table>

        <div class="current-rate-sidecard current-rate-sidecard-edit">
          <span>労災保険率</span>
          <input type="number" step="0.01" name="rousai" value="{{ $rateInput($selectedRouhoRow['rousai'] ?? '') }}">
        </div>
      </div>

      <div class="detail-actions detail-actions-spread">
        <button type="submit" form="rouho-delete-form" class="btn-secondary btn-danger-soft" onclick="return confirm('現在料率を削除しますか？');">削除</button>
        <div class="detail-actions-right">
          <button type="submit">保存</button>
          <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('rouho-current-card', '')">取消</button>
        </div>
      </div>
    </form>
  @endif

  <form method="post" action="{{ route('admin.master.company.rouho.store') }}" class="current-rate-create">
    @csrf
    <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">
    <input type="hidden" name="q" value="{{ $keyword }}">

    <div class="current-rate-meta current-rate-meta-grid current-rate-meta-edit-grid">
      <div class="current-rate-meta-item">
        <span>適用日</span>
        <input type="date" name="rou_apply_date" value="{{ $rouhoSeed['rou_apply_date'] ?? '' }}">
      </div>
      <div class="current-rate-meta-item">
        <span>業種</span>
        <input type="text" name="rou_industry" value="{{ $rouhoSeed['rou_industry'] ?? '' }}">
      </div>
      <div class="current-rate-meta-item">
        <span>集計期間</span>
        <input type="text" name="aggregate_period" value="{{ $rouhoSeed['aggregate_period'] ?? '' }}">
      </div>
    </div>

    <div class="current-rate-summary">
      <table class="current-rate-table current-rate-table-edit">
        <thead>
          <tr>
            <th>区分</th>
            <th>本人</th>
            <th>事業主</th>
            <th>合計</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th>一般拠出金</th>
            <td><input type="number" step="0.01" name="general_st" value="{{ $rateInput($rouhoSeed['general_st'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="general_of" value="{{ $rateInput($rouhoSeed['general_of'] ?? '') }}"></td>
            <td>{{ $sumRate($rouhoSeed['general_st'] ?? '', $rouhoSeed['general_of'] ?? '') }}</td>
          </tr>
          <tr>
            <th>農林・清酒製造業等</th>
            <td><input type="number" step="0.01" name="nourin_st" value="{{ $rateInput($rouhoSeed['nourin_st'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="nourin_of" value="{{ $rateInput($rouhoSeed['nourin_of'] ?? '') }}"></td>
            <td>{{ $sumRate($rouhoSeed['nourin_st'] ?? '', $rouhoSeed['nourin_of'] ?? '') }}</td>
          </tr>
          <tr>
            <th>建設業</th>
            <td><input type="number" step="0.01" name="kensetu_st" value="{{ $rateInput($rouhoSeed['kensetu_st'] ?? '') }}"></td>
            <td><input type="number" step="0.01" name="kensetu_of" value="{{ $rateInput($rouhoSeed['kensetu_of'] ?? '') }}"></td>
            <td>{{ $sumRate($rouhoSeed['kensetu_st'] ?? '', $rouhoSeed['kensetu_of'] ?? '') }}</td>
          </tr>
        </tbody>
      </table>

      <div class="current-rate-sidecard current-rate-sidecard-edit">
        <span>労災保険率</span>
        <input type="number" step="0.01" name="rousai" value="{{ $rateInput($rouhoSeed['rousai'] ?? '') }}">
      </div>
    </div>

    <div class="detail-actions detail-actions-right">
      <button type="submit">追加</button>
      <button type="button" class="btn-secondary" onclick="toggleCurrentRateMode('rouho-current-card', '')">取消</button>
    </div>
  </form>

<div class="rate-history">
  <div class="rate-history-header">
    <h3>履歴</h3>
    <span>{{ count($rouhoRows) }}件</span>
  </div>
  @if($rouhoRows !== [])
    <div class="rate-table-wrap">
      <table class="rate-table">
        <thead>
          <tr>
            <th>適用日</th>
            <th>業種</th>
            <th>集計期間</th>
            <th>一般本人</th>
            <th>一般事業主</th>
            <th>農林本人</th>
            <th>農林事業主</th>
            <th>建設本人</th>
            <th>建設事業主</th>
            <th>労災保険率</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rouhoRows as $row)
            <tr>
              <td>{{ $row['rou_apply_date'] !== '' ? $row['rou_apply_date'] : '---' }}</td>
              <td>{{ $row['rou_industry'] !== '' ? $row['rou_industry'] : '---' }}</td>
              <td>{{ $row['aggregate_period'] !== '' ? $row['aggregate_period'] : '---' }}</td>
              <td>{{ $rate2($row['general_st']) }}</td>
              <td>{{ $rate2($row['general_of']) }}</td>
              <td>{{ $rate2($row['nourin_st']) }}</td>
              <td>{{ $rate2($row['nourin_of']) }}</td>
              <td>{{ $rate2($row['kensetu_st']) }}</td>
              <td>{{ $rate2($row['kensetu_of']) }}</td>
              <td>{{ $rate2($row['rousai']) }}</td>
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
</div>
