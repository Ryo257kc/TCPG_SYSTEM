<div class="store-list-panel">
  <div class="panel-title">店舗一覧</div>
  <div class="store-list">
    @forelse($rows as $row)
      <a
        class="store-list-item {{ $selectedStoreCode === $row['store_code'] ? 'store-list-item-active' : '' }}"
        href="{{ route('admin.master.store', ['q' => $keyword, 'store_code' => $row['store_code']]) }}"
      >
        <div class="store-list-main">
          <span class="store-list-code">{{ $row['store_code'] }}</span>
          <span class="store-list-name">{{ $row['store_name'] !== '' ? $row['store_name'] : '名称未設定' }}</span>
        </div>
        <div class="store-list-sub">
          <span>{{ $row['company_name'] !== '' ? $row['company_name'] : '会社未設定' }}</span>
          @if((int) ($row['is_closed'] ?? 0) === 1)
            <span class="store-list-badge">閉店</span>
          @endif
        </div>
      </a>
    @empty
      <div class="store-empty">データがありません</div>
    @endforelse
  </div>
</div>