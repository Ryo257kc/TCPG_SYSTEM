<form method="post" action="{{ route('admin.master.company.update') }}" class="company-detail-form company-info-form" id="company-info-form">
  @csrf
  <input type="hidden" name="company_id" value="{{ $selectedRow['company_id'] }}">
  <input type="hidden" name="q" value="{{ $keyword }}">
  <input type="hidden" name="tab" value="company">

  <div class="company-info-view company-view" id="company-info-view">
    <div class="detail-grid">
      <div class="detail-field">
        <span>会社名</span>
        <div class="company-display-value {{ $selectedRow['company_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_name'] !== '' ? $selectedRow['company_name'] : '---' }}</div>
      </div>
      <div class="detail-field">
        <span>法人番号</span>
        <div class="company-display-value {{ $selectedRow['corporate_number'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['corporate_number'] !== '' ? $selectedRow['corporate_number'] : '---' }}</div>
      </div>
      <div class="detail-field">
        <span>郵便番号</span>
        <div class="company-display-value {{ $selectedRow['postal_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['postal_code'] !== '' ? $selectedRow['postal_code'] : '---' }}</div>
      </div>
      <div class="detail-field detail-field-wide">
        <span>会社住所</span>
        <div class="company-display-value {{ $selectedRow['company_address'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_address'] !== '' ? $selectedRow['company_address'] : '---' }}</div>
      </div>

      <div class="detail-field">
        <span>TEL</span>
        <div class="company-display-value {{ $selectedRow['phone'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['phone'] !== '' ? $selectedRow['phone'] : '---' }}</div>
      </div>
      <div class="detail-field">
        <span>FAX</span>
        <div class="company-display-value {{ $selectedRow['fax'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['fax'] !== '' ? $selectedRow['fax'] : '---' }}</div>
      </div>
      <div class="detail-field-wide company-person-stack">
        <div class="detail-field">
          <span>役職</span>
          <div class="company-display-value {{ $selectedRow['ceo_title'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['ceo_title'] !== '' ? $selectedRow['ceo_title'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>代表者名カナ</span>
          <div class="company-display-value {{ $selectedRow['ceo_name_kana'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['ceo_name_kana'] !== '' ? $selectedRow['ceo_name_kana'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>代表者名</span>
          <div class="company-display-value {{ $selectedRow['ceo_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['ceo_name'] !== '' ? $selectedRow['ceo_name'] : '---' }}</div>
        </div>
        <div class="detail-field">
          <span>設立日</span>
          <div class="company-display-value {{ $selectedRow['established_date'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['established_date'] !== '' ? $selectedRow['established_date'] : '---' }}</div>
        </div>
      </div>
    </div>

    <div class="detail-actions">
      <button type="button" class="btn-secondary" onclick="toggleCompanyEdit(true)">編集</button>
    </div>
  </div>

  <div class="company-info-edit company-edit" id="company-info-edit" hidden>
    <div class="detail-grid">
      <div class="detail-section detail-field-wide">
        <span>基本情報を編集</span>
      </div>

      <label class="detail-field">
        <span>会社名</span>
        <input type="text" name="company_name" value="{{ $selectedRow['company_name'] }}" required>
      </label>
      <label class="detail-field">
        <span>法人番号</span>
        <input type="text" name="corporate_number" value="{{ $selectedRow['corporate_number'] }}">
      </label>
      <label class="detail-field">
        <span>郵便番号</span>
        <input type="text" name="postal_code" value="{{ $selectedRow['postal_code'] }}">
      </label>
      <label class="detail-field detail-field-wide">
        <span>会社住所</span>
        <input type="text" name="company_address" value="{{ $selectedRow['company_address'] }}">
      </label>

      <label class="detail-field">
        <span>TEL</span>
        <input type="text" name="phone" value="{{ $selectedRow['phone'] }}">
      </label>
      <label class="detail-field">
        <span>FAX</span>
        <input type="text" name="fax" value="{{ $selectedRow['fax'] }}">
      </label>
      <div class="detail-field-wide company-person-stack">
        <label class="detail-field">
          <span>役職</span>
          <input type="text" name="ceo_title" value="{{ $selectedRow['ceo_title'] }}">
        </label>
        <label class="detail-field">
          <span>代表者名カナ</span>
          <input type="text" name="ceo_name_kana" value="{{ $selectedRow['ceo_name_kana'] }}">
        </label>
        <label class="detail-field">
          <span>代表者名</span>
          <input type="text" name="ceo_name" value="{{ $selectedRow['ceo_name'] }}">
        </label>
        <label class="detail-field">
          <span>設立日</span>
          <input type="date" name="established_date" value="{{ $selectedRow['established_date'] }}">
        </label>
      </div>
    </div>

    <div class="detail-actions">
      <button type="submit">保存</button>
      <button type="button" class="btn-secondary" onclick="toggleCompanyEdit(false)">取消</button>
    </div>
  </div>
</form>
