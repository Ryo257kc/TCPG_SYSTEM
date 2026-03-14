<div class="company-list-panel">
  <div class="panel-title">会社一覧</div>
  <div class="company-list">
    @forelse($rows as $row)
      <a class="company-list-item {{ $selectedCompanyId === $row['company_id'] ? 'company-list-item-active' : '' }}" href="{{ route('admin.master.company', ['q' => $keyword, 'company_id' => $row['company_id']]) }}">
        <div class="company-list-main">
          <span class="company-list-code">{{ $row['company_id'] }}</span>
          <span class="company-list-name">{{ $row['company_name'] !== '' ? $row['company_name'] : '会社名未設定' }}</span>
        </div>
        <div class="company-list-sub">
          <span>{{ $row['office_number'] !== '' ? $row['office_number'] : '事業所番号未設定' }}</span>
          <span>{{ $row['phone'] !== '' ? $row['phone'] : 'TEL未設定' }}</span>
        </div>
      </a>
    @empty
      <div class="company-empty">データがありません</div>
    @endforelse
  </div>
</div>
