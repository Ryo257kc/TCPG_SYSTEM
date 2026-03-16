<div class="staff-detail-panel">
  <div class="panel-title">スタッフ詳細</div>
  @if($selectedRow)
    <div class="staff-tab-bar">
      <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'staff']) }}" class="staff-tab {{ $selectedTab === 'staff' ? 'is-active' : '' }}">スタッフ情報</a>
      <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'shift']) }}" class="staff-tab {{ $selectedTab === 'shift' ? 'is-active' : '' }}">基本シフト</a>
      <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'kihon']) }}" class="staff-tab {{ $selectedTab === 'kihon' ? 'is-active' : '' }}">給与マスタ</a>
      <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'fuyo']) }}" class="staff-tab {{ $selectedTab === 'fuyo' ? 'is-active' : '' }}">扶養</a>
      <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'resident']) }}" class="staff-tab {{ $selectedTab === 'resident' ? 'is-active' : '' }}">住民税</a>
    </div>
    <div class="staff-tab-panels">
      @if($selectedTab === 'staff')
        @include('admin_v2.master.staff.partials.tabs.staff_info')
      @elseif($selectedTab === 'shift')
        @include('admin_v2.master.staff.partials.tabs.basic_shifts')
      @elseif($selectedTab === 'kihon')
        @include('admin_v2.master.staff.partials.tabs.payroll_master')
      @elseif($selectedTab === 'fuyo')
        @include('admin_v2.master.staff.partials.tabs.dependents')
      @else
        @include('admin_v2.master.staff.partials.tabs.resident_tax')
      @endif
    </div>
  @else
    <div class="staff-empty">表示対象のスタッフがありません</div>
  @endif
</div>
