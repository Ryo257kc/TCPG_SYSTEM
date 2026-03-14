<div class="store-detail-panel" id="store-detail-panel">
  <div class="panel-title">店舗詳細</div>
  @if($selectedRow)
    <form method="post" action="{{ route('admin.master.store.update') }}" class="store-detail-form">
      @csrf
      <input type="hidden" name="store_code" value="{{ $selectedRow['store_code'] }}">
      <input type="hidden" name="q" value="{{ $keyword }}">

      <div class="detail-grid">
        <label class="detail-field">
          <span>店舗コード</span>
          <div class="store-view detail-value">{{ $selectedRow['store_code'] !== '' ? $selectedRow['store_code'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" value="{{ $selectedRow['store_code'] }}" disabled>
          </div>
        </label>
        <label class="detail-field">
          <span>店舗名</span>
          <div class="store-view detail-value {{ $selectedRow['store_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['store_name'] !== '' ? $selectedRow['store_name'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="store_name" value="{{ $selectedRow['store_name'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>業態</span>
          <div class="store-view detail-value {{ $selectedRow['business_type'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['business_type'] !== '' ? $selectedRow['business_type'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="business_type" value="{{ $selectedRow['business_type'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>訪問エリア</span>
          <div class="store-view detail-value {{ $selectedRow['visit_area'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['visit_area'] !== '' ? $selectedRow['visit_area'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="visit_area" value="{{ $selectedRow['visit_area'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>会社</span>
          <div class="store-view detail-value {{ $selectedRow['company_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['company_name'] !== '' ? $selectedRow['company_name'] : '未設定' }}</div>
          <div class="store-edit">
            <select name="company_id">
              <option value="">(未設定)</option>
              @foreach($companyOptions as $company)
                <option value="{{ $company['company_id'] }}" @selected($company['company_id'] === $selectedRow['company_id'])>{{ $company['company_name'] }}</option>
              @endforeach
            </select>
          </div>
        </label>
        <label class="detail-field">
          <span>略称</span>
          <div class="store-view detail-value {{ $selectedRow['store_short_name'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['store_short_name'] !== '' ? $selectedRow['store_short_name'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="store_short_name" value="{{ $selectedRow['store_short_name'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>郵便番号</span>
          <div class="store-view detail-value {{ $selectedRow['postal_code'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['postal_code'] !== '' ? $selectedRow['postal_code'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="postal_code" value="{{ $selectedRow['postal_code'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>住所カナ</span>
          <div class="store-view detail-value {{ $selectedRow['address_kana'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['address_kana'] !== '' ? $selectedRow['address_kana'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="address_kana" value="{{ $selectedRow['address_kana'] }}">
          </div>
        </label>
        <label class="detail-field detail-field-wide">
          <span>店舗住所</span>
          <div class="store-view detail-value {{ $selectedRow['store_address'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['store_address'] !== '' ? $selectedRow['store_address'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="store_address" value="{{ $selectedRow['store_address'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>分類</span>
          <div class="store-view detail-value {{ $selectedRow['category'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['category'] !== '' ? $selectedRow['category'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="category" value="{{ $selectedRow['category'] }}">
          </div>
        </label>
        <label class="detail-field">
          <span>電話</span>
          <div class="store-view detail-value {{ $selectedRow['phone'] === '' ? 'detail-value-empty' : '' }}">{{ $selectedRow['phone'] !== '' ? $selectedRow['phone'] : '---' }}</div>
          <div class="store-edit">
            <input type="text" name="phone" value="{{ $selectedRow['phone'] }}">
          </div>
        </label>
        <label class="detail-field detail-field-check">
          <span>閉店</span>
          <div class="store-view detail-value">{{ (int) ($selectedRow['is_closed'] ?? 0) === 1 ? '閉店' : '営業中' }}</div>
          <div class="store-edit detail-check-wrap">
            <input type="hidden" name="is_closed" value="0">
            <input type="checkbox" name="is_closed" value="1" @checked((int) ($selectedRow['is_closed'] ?? 0) === 1)>
          </div>
        </label>
      </div>

      <div class="detail-actions">
        <button type="button" class="btn-secondary store-view" onclick="toggleStoreEdit(true)">編集</button>
        <button type="submit" class="store-edit">保存</button>
        <button type="button" class="btn-secondary store-edit" onclick="toggleStoreEdit(false)">取消</button>
      </div>
    </form>
  @else
    <div class="store-empty">表示対象の店舗がありません</div>
  @endif
</div>
