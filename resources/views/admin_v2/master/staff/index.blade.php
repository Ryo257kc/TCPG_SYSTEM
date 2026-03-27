<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM スタッフマスタ</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-frame.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/app-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin_v2/staff.css') }}">
</head>
<body>
@include('admin_v2.shared.global_nav')
<div class="wrap">
    <div class="top">
        <div class="title">TCPG SYSTEM スタッフマスタ</div>
    </div>
    <section class="panel">
        <form method="get" class="filter-form">
            <input type="text" name="q" value="{{ $keyword }}" placeholder="ID / 氏名 / 店舗コードで検索">
            <select name="employment_filter">
                <option value="active" {{ ($employmentFilter ?? 'active') === 'active' ? 'selected' : '' }}>在籍</option>
                <option value="all" {{ ($employmentFilter ?? 'active') === 'all' ? 'selected' : '' }}>すべて</option>
                <option value="retired" {{ ($employmentFilter ?? 'active') === 'retired' ? 'selected' : '' }}>退職</option>
            </select>
            <button type="submit">検索</button>
        </form>
        <p class="meta-count">件数: {{ number_format($rowCount) }}</p>

        <div class="staff-layout">
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

            <div class="staff-detail-panel">
                <div class="panel-title">スタッフ詳細</div>
                @if($selectedRow)
                    <div class="staff-tab-bar">
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'staff']) }}" class="staff-tab {{ $selectedTab === 'staff' ? 'is-active' : '' }}">スタッフ情報</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'shift']) }}" class="staff-tab {{ $selectedTab === 'shift' ? 'is-active' : '' }}">基本シフト</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'kihon']) }}" class="staff-tab {{ $selectedTab === 'kihon' ? 'is-active' : '' }}">給与マスタ</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'fuyo']) }}" class="staff-tab {{ $selectedTab === 'fuyo' ? 'is-active' : '' }}">扶養</a>
                        <a href="{{ route('admin.master.staff', ['q' => $keyword, 'staff_id' => $selectedStaffId, 'tab' => 'resident']) }}" class="staff-tab {{ $selectedTab === 'resident' ? 'is-active' : '' }}">住民税</a>
                        @if($selectedTab === 'staff')
                            <div class="staff-tab-actions">
                                <button type="button" class="btn-secondary staff-tab-edit-button staff-tab-edit-only" onclick="toggleStaffInfoEdit(true)">編集</button>
                                <button type="submit" form="staff-info-form" class="btn-primary staff-tab-edit-button staff-tab-save-only" hidden>保存</button>
                                <button type="button" class="btn-secondary staff-tab-edit-button staff-tab-save-only" hidden onclick="toggleStaffInfoEdit(false)">取消</button>
                            </div>
                        @endif
                    </div>
                    <div class="staff-tab-panels">
                        @if($selectedTab === 'staff')
                            @include('admin_v2.master.staff.tabs.staff_info')
                        @elseif($selectedTab === 'shift')
                            @include('admin_v2.master.staff.tabs.basic_shifts')
                        @elseif($selectedTab === 'kihon')
                            @include('admin_v2.master.staff.tabs.payroll_master')
                        @elseif($selectedTab === 'fuyo')
                            @include('admin_v2.master.staff.tabs.dependents')
                        @else
                            @include('admin_v2.master.staff.tabs.resident_tax')
                        @endif
                    </div>
                @else
                    <div class="staff-empty">表示対象のスタッフがありません</div>
                @endif
            </div>
        </div>
    </section>
</div>
@include('admin_v2.master.staff.page_script')
</body>
</html>
