<div class="staff-list-panel">
  <div class="panel-title">スタッフ一覧</div>
  <div class="staff-list">
    @forelse($rows as $row)
      <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $row['staff_id'], 'tab' => $selectedTab]) }}" class="staff-list-item {{ $selectedStaffId === $row['staff_id'] ? 'staff-list-item-active' : '' }}">
        <div class="staff-list-main">
          <span class="staff-list-id">{{ $row['staff_id'] }}</span>
          <span class="staff-list-name">{{ $row['staff_name'] }}</span>
        </div>
        <div class="staff-list-sub">
          <span>{{ $row['store_name'] !== '' ? $row['store_name'] : '---' }}</span>
          <span>{{ $row['employment_status'] !== '' ? $row['employment_status'] : '---' }}</span>
        </div>
      </a>
    @empty
      <div class="staff-empty">スタッフがありません</div>
    @endforelse
  </div>
</div>
