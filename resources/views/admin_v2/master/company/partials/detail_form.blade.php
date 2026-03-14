<div class="company-detail-panel" id="company-detail-panel">
  <div class="panel-title">会社詳細</div>
  @if($selectedRow)
    <form method="post" action="{{ route('admin.master.company.update') }}" class="company-detail-form">
      @csrf
      <input type="hidden" name="company_id" value="{{ $selectedRow['company_id'] }}">
      <input type="hidden" name="q" value="{{ $keyword }}">

      <div class="detail-grid">
        <div class="detail-section detail-field-wide">
          <span>基本情報</span>
        </div>

        <label class="detail-field">
          <span>会社名</span>
          <div class="company-view detail-value {{ $selectedRow['company_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_name'] !== '' ? $selectedRow['company_name'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="company_name" value="{{ $selectedRow['company_name'] }}" required></div>
        </label>
        <label class="detail-field">
          <span>会社名カナ</span>
          <div class="company-view detail-value {{ $selectedRow['company_name_kana'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_name_kana'] !== '' ? $selectedRow['company_name_kana'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="company_name_kana" value="{{ $selectedRow['company_name_kana'] }}"></div>
        </label>

        <label class="detail-field">
          <span>事業所番号</span>
          <div class="company-view detail-value {{ $selectedRow['office_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['office_number'] !== '' ? $selectedRow['office_number'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="office_number" value="{{ $selectedRow['office_number'] }}"></div>
        </label>
        <label class="detail-field">
          <span>法人番号</span>
          <div class="company-view detail-value {{ $selectedRow['corporate_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['corporate_number'] !== '' ? $selectedRow['corporate_number'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="corporate_number" value="{{ $selectedRow['corporate_number'] }}"></div>
        </label>

        <label class="detail-field detail-field-wide">
          <span>会社住所</span>
          <div class="company-view detail-value {{ $selectedRow['company_address'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_address'] !== '' ? $selectedRow['company_address'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="company_address" value="{{ $selectedRow['company_address'] }}"></div>
        </label>

        <label class="detail-field">
          <span>TEL</span>
          <div class="company-view detail-value {{ $selectedRow['phone'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['phone'] !== '' ? $selectedRow['phone'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="phone" value="{{ $selectedRow['phone'] }}"></div>
        </label>
        <label class="detail-field">
          <span>FAX</span>
          <div class="company-view detail-value {{ $selectedRow['fax'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['fax'] !== '' ? $selectedRow['fax'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="fax" value="{{ $selectedRow['fax'] }}"></div>
        </label>

        <div class="detail-section detail-field-wide">
          <span>代表者情報</span>
        </div>

        <label class="detail-field">
          <span>役職名</span>
          <div class="company-view detail-value {{ $selectedRow['ceo_title'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['ceo_title'] !== '' ? $selectedRow['ceo_title'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="ceo_title" value="{{ $selectedRow['ceo_title'] }}"></div>
        </label>
        <label class="detail-field">
          <span>代表者名カナ</span>
          <div class="company-view detail-value {{ $selectedRow['ceo_name_kana'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['ceo_name_kana'] !== '' ? $selectedRow['ceo_name_kana'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="ceo_name_kana" value="{{ $selectedRow['ceo_name_kana'] }}"></div>
        </label>

        <label class="detail-field detail-field-wide">
          <span>代表者名</span>
          <div class="company-view detail-value {{ $selectedRow['ceo_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['ceo_name'] !== '' ? $selectedRow['ceo_name'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="ceo_name" value="{{ $selectedRow['ceo_name'] }}"></div>
        </label>

        <div class="detail-section detail-field-wide">
          <span>社会保険</span>
        </div>

        <label class="detail-field">
          <span>健保事業所記号</span>
          <div class="company-view detail-value {{ $selectedRow['health_office_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['health_office_code'] !== '' ? $selectedRow['health_office_code'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="health_office_code" value="{{ $selectedRow['health_office_code'] }}"></div>
        </label>
        <label class="detail-field">
          <span>年金事業所番号</span>
          <div class="company-view detail-value {{ $selectedRow['pension_office_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['pension_office_code'] !== '' ? $selectedRow['pension_office_code'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="pension_office_code" value="{{ $selectedRow['pension_office_code'] }}"></div>
        </label>

        <label class="detail-field">
          <span>保険者番号</span>
          <div class="company-view detail-value {{ $selectedRow['insurer_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['insurer_number'] !== '' ? $selectedRow['insurer_number'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="insurer_number" value="{{ $selectedRow['insurer_number'] }}"></div>
        </label>
        <label class="detail-field">
          <span>保険者名</span>
          <div class="company-view detail-value {{ $selectedRow['insurer_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['insurer_name'] !== '' ? $selectedRow['insurer_name'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="insurer_name" value="{{ $selectedRow['insurer_name'] }}"></div>
        </label>

        <label class="detail-field">
          <span>ハローワーク番号</span>
          <div class="company-view detail-value {{ $selectedRow['employment_office_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['employment_office_number'] !== '' ? $selectedRow['employment_office_number'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="employment_office_number" value="{{ $selectedRow['employment_office_number'] }}"></div>
        </label>

        <label class="detail-field detail-field-wide">
          <span>保険者住所</span>
          <div class="company-view detail-value {{ $selectedRow['insurer_address'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['insurer_address'] !== '' ? $selectedRow['insurer_address'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="insurer_address" value="{{ $selectedRow['insurer_address'] }}"></div>
        </label>

        <div class="detail-section detail-field-wide">
          <span>労働保険</span>
        </div>

        <label class="detail-field">
          <span>労災保険番号</span>
          <div class="company-view detail-value {{ $selectedRow['labor_insurance_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_insurance_number'] !== '' ? $selectedRow['labor_insurance_number'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="labor_insurance_number" value="{{ $selectedRow['labor_insurance_number'] }}"></div>
        </label>
        <label class="detail-field">
          <span>労保適用区分</span>
          <div class="company-view detail-value {{ $selectedRow['labor_install_category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_install_category'] !== '' ? $selectedRow['labor_install_category'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="labor_install_category" value="{{ $selectedRow['labor_install_category'] }}"></div>
        </label>

        <label class="detail-field">
          <span>労保設置日</span>
          <div class="company-view detail-value {{ $selectedRow['labor_install_date'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_install_date'] !== '' ? $selectedRow['labor_install_date'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="labor_install_date" value="{{ $selectedRow['labor_install_date'] }}"></div>
        </label>
        <label class="detail-field">
          <span>労働局区分</span>
          <div class="company-view detail-value {{ $selectedRow['labor_office_category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_office_category'] !== '' ? $selectedRow['labor_office_category'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="labor_office_category" value="{{ $selectedRow['labor_office_category'] }}"></div>
        </label>

        <label class="detail-field">
          <span>労災業種区分</span>
          <div class="company-view detail-value {{ $selectedRow['labor_industry_category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['labor_industry_category'] !== '' ? $selectedRow['labor_industry_category'] : '---' }}</div>
          <div class="company-edit"><input type="text" name="labor_industry_category" value="{{ $selectedRow['labor_industry_category'] }}"></div>
        </label>
      </div>

      <div class="detail-actions">
        <button type="button" class="btn-secondary company-view" onclick="toggleCompanyEdit(true)">編集</button>
        <button type="submit" class="company-edit">保存</button>
        <button type="button" class="btn-secondary company-edit" onclick="toggleCompanyEdit(false)">取消</button>
      </div>
    </form>
  @else
    <div class="company-empty">表示対象の会社がありません</div>
  @endif
</div>
