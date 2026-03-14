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
  </div>

  <div class="detail-actions">
    <button type="button" class="btn-secondary company-view" onclick="toggleCompanyEdit(true)">編集</button>
    <button type="submit" class="company-edit">保存</button>
    <button type="button" class="btn-secondary company-edit" onclick="toggleCompanyEdit(false)">取消</button>
  </div>
</form>
